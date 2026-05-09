# Task 001 - Micro-tasking Todo List

## Phase 1: 개발 환경 세팅 및 DB 연결 확인
- [x] Node.js 프로젝트 초기화 및 필수 의존성 설치
  - Target: `tasks/task001/package.json`
  - Action: `npm init -y` 실행 후 `mysql2`, `dotenv`, `turndown`(HTML->MD 변환) 설치
  - Validation: `ls -la node_modules/mysql2` 존재 확인
- [x] 레거시 DB(MySQL) 연결 테스트 스크립트 작성
  - Target: `tasks/task001/src/check-db.js`
  - Action: `.env.local`의 `LEGACY_DB_URL`을 이용해 DB에 접속하고 핑(Ping)을 날리는 코드 작성
  - Validation: `node src/check-db.js` 실행 시 "DB Connected" 메시지 출력 확인

## Phase 2: 추출 및 마스킹 로직 구현
- [x] 개인정보 정제기 (Sanitizer) 로직 작성
  - Target: `tasks/task001/src/utils/sanitizer.js`
  - Action: 이메일, 전화번호 정규식 기반 마스킹 함수(실명 제외) 작성 및 간단한 테스트 로직 포함
  - Validation: `node src/utils/sanitizer.js` 실행 시 샘플 텍스트가 정상적으로 마스킹되는지 콘솔 확인
- [x] HTML to Markdown 변환 로직 연동
  - Target: `tasks/task001/src/utils/html2md.js`
  - Action: `turndown` 라이브러리를 활용해 의미 없는 색상 태그 등은 날리고 깔끔한 MD로 변환하는 함수 작성
  - Validation: `node src/utils/html2md.js` 실행 시 샘플 HTML이 MD로 정상 변환되는지 콘솔 확인

## Phase 3: JSONL 추출 스크립트 통합
- [x] 스트리밍 기반 JSONL 전체 추출 스크립트 작성
  - Target: `tasks/task001/src/extract-jsonl.js`
  - Action: DB에서 500건씩 조회(Pagination) -> 변환/마스킹 적용 -> `output.jsonl` 파일에 Append
  - Validation: `node src/extract-jsonl.js` 실행 후 `output.jsonl` 파일 생성 및 데이터 10건(샘플) 확인
