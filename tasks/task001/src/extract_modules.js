const mysql = require('mysql2/promise');
const fs = require('fs/promises');
const path = require('path');

async function extractModules() {
  // .env.local의 LEGACY_DB_URL 값 직접 하드코딩 또는 파싱 (127.0.0.1로 접속)
  const connection = await mysql.createConnection('mysql://root:root@127.0.0.1:3307/hr2');

  try {
    const [rows] = await connection.execute('SELECT module_srl, mid, browser_title FROM xe_modules');
    const mapping = {};
    for (const row of rows) {
      mapping[row.module_srl] = row.browser_title || row.mid || `게시판 ${row.module_srl}`;
    }
    
    await fs.writeFile(
      path.join(__dirname, '../modules.json'), 
      JSON.stringify(mapping, null, 2), 
      'utf8'
    );
    console.log('✅ 모듈(게시판) 매핑 데이터(modules.json) 추출 완료!');
  } catch (err) {
    console.error('오류 발생:', err);
  } finally {
    await connection.end();
  }
}

extractModules();
