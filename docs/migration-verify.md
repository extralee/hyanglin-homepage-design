# 레거시 데이터 이관 정합성 검증 체크리스트

> **목적**: XE MySQL → PostgreSQL 데이터 이관 후 유실·깨짐·엑박 없음을 수동으로 확인
>
> **시스템 정보**
> - 원본 DB: 구 서버(`14.63.198.35`) MySQL — DB명 `hr2`, XE 기반
> - 대상 DB: 로컬 / 가비아 PostgreSQL — `church_seoul` DB (Prisma 관리)
> - Next.js 앱 경로: `hyanglin-finance/web/`
> - 이미지 물리 경로: `hyanglin-finance/web/public/uploads/legacy/`
>
> **레거시 기준값** (xe DB 실측치)
>
> | 항목 | 기준값 |
> |------|--------|
> | 회원 (`xe_member`) | 601명 |
> | 게시글 (`xe_documents`) | 7,434건 |
> | 댓글 (`xe_comments`) | 7건 |
> | 파일 메타 (`xe_files`) | 2,191건 |
> | 물리 이미지 파일 | 2,231개 (JPG/PNG/GIF) |

---

## 사전 준비: DB 연결 확인

```bash
# 로컬 PostgreSQL 접속 (psql)
psql "postgresql://postgres:postgres@localhost:5432/church_seoul"

# 접속 확인 — 버전 및 현재 DB 출력
SELECT version(), current_database();
```

---

## 1. DB 레코드 수 대조

### 1-A. 회원 수

```sql
-- 전체 회원 (개발 계정 제외)
SELECT COUNT(*) AS total_users FROM users WHERE email NOT LIKE '%@dev.local';

-- 역할별 분포
SELECT role, COUNT(*) FROM users GROUP BY role ORDER BY role;

-- 비활성 계정 (xe_member.denied = 'Y' 에 해당)
SELECT COUNT(*) FROM users WHERE "isActive" = false;
```

| 체크 | 항목 | 기준값 | 실측값 |
|------|------|--------|--------|
| `[ ]` | 전체 회원 수 (dev.local 제외) | ≥ 600명 | |
| `[ ]` | ADMIN 역할 수 | ≥ 2명 | |
| `[ ]` | 비활성 계정(`isActive=false`) | xe `denied='Y'` 수와 일치 | |

---

### 1-B. 게시판 수 및 슬러그

```sql
SELECT b.slug, b.name, COUNT(p.id) AS post_count
FROM boards b
LEFT JOIN posts p ON b.id = p."boardId" AND p."isDeleted" = false
GROUP BY b.id, b.slug, b.name
ORDER BY b."sortOrder";
```

| 체크 | slug | 예상 게시글 수 | 실측값 |
|------|------|----------------|--------|
| `[ ]` | `notice` (공지사항) | ≥ 1,199건 | |
| `[ ]` | `news` (노회소식) | ≥ 590건 | |
| `[ ]` | `archive` (자료실) | ≥ 454건 | |
| `[ ]` | `schedule` (노회일정) | ≥ 253건 | |
| `[ ]` | `circuit` (시찰회) | ≥ 252건 | |
| `[ ]` | `department` (상비부) | ≥ 150건 | |
| `[ ]` | `general` (기타 module_srl 폴백 포함) | ≥ 4,400건 | |
| `[ ]` | 전체 게시판 레코드 수 | 7개 이상 | |

---

### 1-C. 게시글 총 행 수

```sql
-- 활성 게시글
SELECT COUNT(*) AS total_posts FROM posts WHERE "isDeleted" = false;

-- 고정글
SELECT COUNT(*) AS pinned_posts FROM posts WHERE "isPinned" = true AND "isDeleted" = false;

-- 소프트삭제된 게시글
SELECT COUNT(*) AS deleted_posts FROM posts WHERE "isDeleted" = true;

-- 제목 없는 글 (이관 오류 지표)
SELECT COUNT(*) FROM posts WHERE title = '(제목 없음)';
```

| 체크 | 항목 | 기준값 | 실측값 |
|------|------|--------|--------|
| `[ ]` | 전체 게시글 (삭제 제외) | ≥ 7,000건 | |
| `[ ]` | 공지 고정글 (`isPinned`) | ≥ 1건 | |
| `[ ]` | 제목 없는 글 (`'(제목 없음)'`) | 0건이 이상적 | |

---

### 1-D. 댓글 수

```sql
-- 전체 댓글
SELECT COUNT(*) AS total_comments FROM comments WHERE "isDeleted" = false;

-- 대댓글 (parentId가 있는 경우)
SELECT COUNT(*) AS threaded FROM comments WHERE "parentId" IS NOT NULL;

-- 고아 대댓글 (부모가 존재하지 않는 경우)
SELECT COUNT(*) FROM comments c
LEFT JOIN comments parent ON c."parentId" = parent.id
WHERE c."parentId" IS NOT NULL AND parent.id IS NULL;
```

| 체크 | 항목 | 기준값 | 실측값 |
|------|------|--------|--------|
| `[ ]` | 전체 댓글 수 | ≥ 7건 | |
| `[ ]` | 고아 대댓글 (부모 없는 대댓글) | 0건 | |

---

### 1-E. 파일 메타데이터 수

```sql
-- 전체 파일 메타 레코드
SELECT COUNT(*) AS total_files FROM files;

-- 게시글 미연결 파일 (고아 파일 후보)
SELECT COUNT(*) AS orphan_files FROM files WHERE "postId" IS NULL;

-- MIME 타입별 분포 (상위 15개)
SELECT "mimeType", COUNT(*) FROM files GROUP BY "mimeType" ORDER BY COUNT(*) DESC LIMIT 15;
```

| 체크 | 항목 | 기준값 | 실측값 |
|------|------|--------|--------|
| `[ ]` | 전체 파일 메타 레코드 | ≥ 2,000건 | |
| `[ ]` | 게시글 미연결 파일 (`postId IS NULL`) | 확인 후 기록 | |
| `[ ]` | `image/jpeg`, `image/png` 비율 확인 | 다수여야 정상 | |

---

### 1-F. 고아 데이터 (외래키 무결성)

```sql
-- 작성자가 없는 게시글
SELECT COUNT(*) AS orphan_posts FROM posts p
LEFT JOIN users u ON p."authorId" = u.id WHERE u.id IS NULL;

-- 게시판이 없는 게시글
SELECT COUNT(*) AS no_board_posts FROM posts p
LEFT JOIN boards b ON p."boardId" = b.id WHERE b.id IS NULL;

-- 게시글이 없는 첨부파일 (postId가 있지만 post가 없는 경우)
SELECT COUNT(*) FROM files f
WHERE f."postId" IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM posts p WHERE p.id = f."postId");
```

| 체크 | 항목 | 기준값 | 실측값 |
|------|------|--------|--------|
| `[ ]` | 고아 게시글 (작성자 없음) | 0건 | |
| `[ ]` | 고아 게시글 (게시판 없음) | 0건 | |
| `[ ]` | 고아 첨부파일 (게시글 없음) | 0건 | |

---

## 2. 한글 인코딩 검증 (깨진 텍스트)

> 인코딩 깨짐 패턴: MySQL `euckr` → PostgreSQL `UTF-8` 변환 과정에서 발생하는 `ì `, `ë`, `ê` 등 latin1 오표현

```sql
-- 게시글 제목 한글 깨짐 탐지
SELECT COUNT(*) AS broken_titles FROM posts
WHERE title ~ '[\xEC-\xEF][\x80-\xBF][\x80-\xBF]';

-- 사용자 이름 한글 깨짐 탐지
SELECT COUNT(*) AS broken_names FROM users
WHERE name ~ '[\xEC-\xEF][\x80-\xBF][\x80-\xBF]';

-- 깨진 제목 샘플 10건 확인
SELECT id, title FROM posts
WHERE title ~ '[\xEC-\xEF][\x80-\xBF][\x80-\xBF]'
LIMIT 10;
```

| 체크 | 항목 | 기준값 | 실측값 |
|------|------|--------|--------|
| `[ ]` | 게시글 제목 깨짐 건수 | 0건 | |
| `[ ]` | 게시글 본문 깨짐 건수 | 0건 | |
| `[ ]` | 사용자 이름 깨짐 건수 | 0건 | |
| `[ ]` | 댓글 내용 깨짐 건수 | 0건 | |
| `[ ]` | 파일명 깨짐 건수 | 0건 | |

---

## 3. 물리 이미지 파일 존재 여부 (파일시스템 점검)

> 물리 파일 기준 경로: `hyanglin-finance/web/public/uploads/legacy/`

```bash
# 프로젝트 루트로 이동
cd /home/hyuk/prj/hyanglin-finance

# 전체 물리 파일 수
find web/public/uploads/legacy -type f | wc -l

# 확장자별 분포
find web/public/uploads/legacy -type f \
  | awk -F. '{print tolower($NF)}' | sort | uniq -c | sort -rn

# 이미지 파일만 (JPG/PNG/GIF)
find web/public/uploads/legacy -type f \
  \( -iname "*.jpg" -o -iname "*.jpeg" -o -iname "*.png" -o -iname "*.gif" \) | wc -l

# 빈 파일(0 bytes) 확인
find web/public/uploads/legacy -type f -empty | wc -l
```

| 체크 | 항목 | 기준값 | 실측값 |
|------|------|--------|--------|
| `[ ]` | 전체 물리 파일 수 | ≥ 2,200개 | |
| `[ ]` | 이미지 파일 수 (JPG+PNG+GIF) | ≥ 1,700개 | |
| `[ ]` | 빈 파일 (0 bytes) | 0건이 이상적 | |

---

## 4. DB 파일 경로 ↔ 물리 파일 매칭 (샘플 검증)

> DB `files.path` 필드는 `/legacy/...` 형태로 저장됨
> 실제 물리 경로: `web/public/uploads/legacy/...`

```sql
-- DB에 저장된 파일 경로 샘플 50건
SELECT id, name, path FROM files
WHERE path LIKE '/legacy%'
ORDER BY "createdAt" DESC
LIMIT 50;

-- path가 NULL이거나 비어있는 레코드
SELECT COUNT(*) FROM files WHERE path IS NULL OR path = '';

-- /legacy/ 가 아닌 다른 경로 패턴 확인
SELECT DISTINCT LEFT(path, 20) AS path_prefix, COUNT(*) FROM files
GROUP BY LEFT(path, 20)
ORDER BY COUNT(*) DESC;
```

| 체크 | 항목 | 확인 방법 |
|------|------|-----------|
| `[ ]` | DB path 형식 `/legacy/...` 로 통일 | SQL 조회 결과 확인 |
| `[ ]` | 샘플 10개 파일 — DB path → 실제 파일 존재 | `ls web/public/uploads/legacy/...` |
| `[ ]` | path가 NULL 또는 빈 레코드 | SQL 조회 |

**샘플 매칭 확인 방법 (bash)**:
```bash
cd /home/hyuk/prj/hyanglin-finance
# DB에서 얻은 path 값 예시: /legacy/xe-files/abcd.jpg
# 아래처럼 실제 파일이 있는지 확인
ls web/public/uploads/legacy/xe-files/abcd.jpg
```

---

## 5. 브라우저 시각적 검증 (이미지 엑박 확인)

> 개발 서버 실행 후 브라우저에서 직접 확인

```bash
cd /home/hyuk/prj/hyanglin-finance/web
pnpm dev
# 또는
../.bin/start-svr.sh
```

### 5-A. 게시글 목록 한글 정상 출력

| 체크 | 페이지 URL | 확인 항목 |
|------|-----------|-----------|
| `[ ]` | `/seoul/boards/notice` | 제목/작성자 한글 정상 출력 |
| `[ ]` | `/seoul/boards/news` | 제목/작성자 한글 정상 출력 |
| `[ ]` | `/seoul/boards/general` | 제목/작성자 한글 정상 출력 |
| `[ ]` | `/seoul/boards/circuit` | 제목/작성자 한글 정상 출력 |
| `[ ]` | `/seoul/boards/schedule` | 제목/작성자 한글 정상 출력 |

### 5-B. 게시글 상세 — 본문 및 이미지

> XE 게시글 본문에는 `<img>` 태그가 포함된 경우가 있음 (`html-render-expert` 스킬 참고)

| 체크 | 확인 항목 | 방법 |
|------|-----------|------|
| `[ ]` | 이미지 포함 게시글 상세 — 이미지 정상 출력 (엑박 아님) | 브라우저 직접 확인 |
| `[ ]` | DevTools → Network 탭 이미지 요청 상태코드 200 | F12 → Network |
| `[ ]` | 이미지 URL 형식 `/uploads/legacy/...` 또는 `/legacy/...` | DevTools 확인 |
| `[ ]` | 본문 내 구 도메인 외부 이미지 (`hyanglin.org` 구 서버) 엑박 건수 기록 | 브라우저 확인 |

### 5-C. 첨부파일 다운로드

| 체크 | 확인 항목 |
|------|-----------|
| `[ ]` | 첨부파일 목록 있는 게시글 → 다운로드 클릭 → 정상 다운로드 |
| `[ ]` | 파일명에 한글 포함된 경우도 정상 다운로드 (Content-Disposition 헤더 확인) |
| `[ ]` | 존재하지 않는 파일 다운로드 요청 → 404 응답 (서버 에러 아님) |

---

## 6. 게시판-모듈 매핑 검증

> XE `module_srl` → Board `slug` 매핑이 올바른지 특정 게시글로 확인

```sql
-- 각 게시판별 최신 게시글 5건 확인
SELECT b.slug, b.name, p.title, p."createdAt"
FROM posts p
JOIN boards b ON p."boardId" = b.id
WHERE p."isDeleted" = false
ORDER BY b.slug, p."createdAt" DESC;

-- XE document_srl 기반 ID로 조회 (이관 시 id = 'xe-{document_srl}' 규칙인 경우)
-- 예: document_srl = 12345 이면 id = 'xe-12345'
SELECT id, title, "boardId", "createdAt" FROM posts
WHERE id = 'xe-12345';
```

| 체크 | 확인 항목 |
|------|-----------|
| `[ ]` | `notice` 게시판에 공지사항 내용의 글이 있음 |
| `[ ]` | `news` 게시판에 노회 소식 관련 글이 있음 |
| `[ ]` | `circuit` 게시판에 시찰회 관련 글이 있음 |
| `[ ]` | `general` 게시판에 매핑되지 않은 `module_srl` 폴백 데이터 포함 (정상) |
| `[ ]` | 특정 XE `document_srl`로 게시글 조회 가능 |

---

## 7. Prisma 스키마 무결성 확인

> Prisma 마이그레이션 이력과 실제 DB 스키마가 일치하는지 확인

```bash
cd /home/hyuk/prj/hyanglin-finance/web

# 적용된 마이그레이션 이력 확인
npx prisma migrate status

# DB와 스키마 동기화 상태 확인 (드리프트 탐지)
npx prisma db pull --print 2>&1 | head -50
```

| 체크 | 항목 | 기준값 |
|------|------|--------|
| `[ ]` | 모든 마이그레이션 `Applied` 상태 | drift 없음 |
| `[ ]` | `boards`, `posts`, `comments`, `files`, `users` 테이블 존재 | ✓ |

---

## 8. 이관 결과 요약표 (검증 완료 후 기입)

| 항목 | 레거시 기준값 | 이관 후 실측값 | 상태 |
|------|-------------|---------------|------|
| 회원 수 | 601명 | | `[ ]` |
| 게시판 수 | 7개 | | `[ ]` |
| 게시글 수 | 7,434건 | | `[ ]` |
| 댓글 수 | 7건 | | `[ ]` |
| 파일 메타 수 | 2,191건 | | `[ ]` |
| 물리 이미지 파일 수 | 2,231개 | | `[ ]` |
| 한글 깨짐 건수 | 0건 | | `[ ]` |
| 고아 데이터 | 0건 | | `[ ]` |
| 이미지 엑박 여부 | 없음 | | `[ ]` |
| Prisma 마이그레이션 상태 | drift 없음 | | `[ ]` |

---

> **검증 완료 일자**: ___________
> **검증자**: ___________
> **특이사항**: ___________
