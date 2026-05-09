const mysql = require('mysql2/promise');
const fs = require('fs/promises');
const path = require('path');
require('dotenv').config({ path: path.resolve(__dirname, '../../../.env.local') });

const { sanitizeText } = require('./utils/sanitizer');
const { convertHtmlToMarkdown } = require('./utils/html2md');

const OUTPUT_FILE = path.resolve(__dirname, '../output.jsonl');
const IS_SAMPLE = true; // 랜덤 샘플링 모드 켜기
const SAMPLE_SIZE = 1000;
const BATCH_SIZE = 500; // 전체 추출 시 Chunk 사이즈

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
    if (IS_SAMPLE) {
      console.log(`⚠️ (Test Mode) 전체 데이터 중 ${SAMPLE_SIZE}건을 랜덤 샘플링하여 추출합니다...`);
      const [rows] = await connection.execute(
        `SELECT document_srl, module_srl, title, content, nick_name, regdate, readed_count, voted_count, comment_count 
         FROM xe_documents 
         ORDER BY RAND() 
         LIMIT ?`,
        [SAMPLE_SIZE]
      );

      let batchLines = '';
      for (const row of rows) {
        let markdownContent = convertHtmlToMarkdown(row.content);
        let sanitizedContent = sanitizeText(markdownContent);

        const record = {
          id: row.document_srl,
          category_id: row.module_srl,
          title: row.title,
          author: row.nick_name,
          date: row.regdate,
          readed_count: row.readed_count || 0,
          voted_count: row.voted_count || 0,
          comment_count: row.comment_count || 0,
          content: sanitizedContent
        };
        batchLines += JSON.stringify(record) + '\n';
      }
      await fs.appendFile(OUTPUT_FILE, batchLines, 'utf8');
      totalExtracted = rows.length;
      
    } else {
      while (true) {
        const [rows] = await connection.execute(
          `SELECT document_srl, module_srl, title, content, nick_name, regdate, readed_count, voted_count, comment_count 
           FROM xe_documents 
           ORDER BY document_srl ASC 
           LIMIT ? OFFSET ?`,
          [BATCH_SIZE, offset]
        );

        if (rows.length === 0) break;

        let batchLines = '';
        for (const row of rows) {
          let markdownContent = convertHtmlToMarkdown(row.content);
          let sanitizedContent = sanitizeText(markdownContent);

          const record = {
            id: row.document_srl,
            category_id: row.module_srl,
            title: row.title,
            author: row.nick_name,
            date: row.regdate,
            readed_count: row.readed_count || 0,
            voted_count: row.voted_count || 0,
            comment_count: row.comment_count || 0,
            content: sanitizedContent
          };
          batchLines += JSON.stringify(record) + '\n';
        }

        await fs.appendFile(OUTPUT_FILE, batchLines, 'utf8');
        totalExtracted += rows.length;
        console.log(`⏳ ${totalExtracted}건 추출, 변환 및 마스킹 완료...`);
        offset += BATCH_SIZE;
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
