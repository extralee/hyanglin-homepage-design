const mysql = require('mysql2/promise');
const path = require('path');
// 루트 경로에 있는 .env.local 파일을 불러옵니다.
require('dotenv').config({ path: path.resolve(__dirname, '../../../.env.local') });

async function checkDb() {
  const dbUrl = process.env.LEGACY_DB_URL;
  if (!dbUrl) {
    console.error("❌ Error: LEGACY_DB_URL이 .env.local에 설정되어 있지 않습니다.");
    process.exit(1);
  }

  try {
    console.log(`[DB 연결 시도] URL: ${dbUrl}`);
    const connection = await mysql.createConnection(dbUrl);
    
    console.log("✅ DB Connected successfully!");
    
    // 연결 검증용 단순 쿼리 (테이블 목록 조회)
    const [rows] = await connection.execute('SHOW TABLES');
    console.log(`📊 조회된 테이블 개수: ${rows.length}개`);
    
    await connection.end();
    console.log("🔒 DB 연결 종료");
  } catch (error) {
    console.error("❌ DB Connection failed:");
    console.error(error.message);
    process.exit(1);
  }
}

checkDb();
