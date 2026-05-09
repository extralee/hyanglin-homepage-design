const mysql = require('mysql2/promise');
const fs = require('fs/promises');
const path = require('path');
require('dotenv').config({ path: path.resolve(__dirname, '../../../.env.local') });

const { sanitizeText } = require('./utils/sanitizer');
const { convertHtmlToMarkdown } = require('./utils/html2md');

const OUTPUT_FILE = path.resolve(__dirname, '../output.jsonl');
const BATCH_SIZE = 500; // Chunk 사이즈 (메모리 보호)

async function extractData() {
  const dbUrl = process.env.LEGACY_DB_URL;
  if (!dbUrl) {
    console.error("❌ LEGACY_DB_URL is missing.");
    process.exit(1);
  }

  const connection = await mysql.createConnection(dbUrl);
  
  // 기존 출력 파일이 있다면 초기화 (덮어쓰기)
  await fs.writeFile(OUTPUT_FILE, '', 'utf8');

  let offset = 0;
  let totalExtracted = 0;

  console.log("🚀 데이터 추출 및 JSONL 파이프라인 가동...");

  try {
    // 테이블 존재 여부 먼저 확인 (XE 기본 테이블: xe_documents)
    // 참고: 특정 게시판(free01)만 뽑으려면 WHERE module_srl = ? 조건이 필요하지만, 여기서는 전체 문서 대상
    while (true) {
      const [rows] = await connection.execute(
        `SELECT document_srl, module_srl, title, content, nick_name, regdate 
         FROM xe_documents 
         ORDER BY document_srl ASC 
         LIMIT ? OFFSET ?`,
        [BATCH_SIZE, offset]
      );

      if (rows.length === 0) {
        break; // 더 이상 가져올 데이터가 없으면 루프 종료
      }

      let batchLines = '';
      for (const row of rows) {
        // 1. HTML -> Markdown 변환
        let markdownContent = convertHtmlToMarkdown(row.content);
        
        // 2. 민감정보 정제 (마스킹 적용)
        let sanitizedContent = sanitizeText(markdownContent);

        // 3. JSONL 객체 생성 (Frontmatter 스키마로 쓸 정보)
        const record = {
          id: row.document_srl,
          category_id: row.module_srl,
          title: row.title,
          author: row.nick_name, // 실명 보존됨
          date: row.regdate,     // YYYYMMDDHHMMSS 형태
          content: sanitizedContent
        };

        batchLines += JSON.stringify(record) + '\n';
      }

      // 4. 추출된 Chunk를 파일에 추가 (Append) - OOM(Out of Memory) 방지
      await fs.appendFile(OUTPUT_FILE, batchLines, 'utf8');
      
      totalExtracted += rows.length;
      console.log(`⏳ ${totalExtracted}건 추출, 변환 및 마스킹 완료...`);
      offset += BATCH_SIZE;

      // [테스트 게이트] 전체 17,000건을 다 뽑으면 시간이 다소 걸릴 수 있으므로,
      // 기능 검증용으로 우선 1,000건만 뽑고 멈추도록 안전장치 설정.
      // (전체 마이그레이션 시 이 주석과 아래 로직을 지우면 됩니다.)
      if (totalExtracted >= 1000) {
        console.log("⚠️ (Test Mode) 1,000건 추출 완료로 임시 중단. 전체를 원하면 스크립트의 테스트 조건을 해제하세요.");
        break;
      }
    }

    console.log(`✅ 파이프라인 종료! 총 ${totalExtracted}건이 성공적으로 'output.jsonl'에 저장되었습니다.`);
  } catch (error) {
    console.error("❌ 추출 중 오류 발생:", error.message);
  } finally {
    await connection.end();
  }
}

extractData();
