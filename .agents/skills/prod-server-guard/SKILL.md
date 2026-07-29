---
name: prod-server-guard
description: 향린 가비아 프로덕션 서버(45.115.154.229, sshy) 접속, SSH/SCP, Docker, 파일 편집, 배포 및 DB 작업 전 경고 배너를 표시하고 사용자의 명시적 확인을 요구하는 프로젝트 전용 가드 스킬.
---

# 🚨 향린 프로덕션 서버 접속 경고 스킬 (Project Local)

## 📌 프로덕션 서버 접속 가이드 (SSH Access Guide)

AI 에이전트는 프로덕션 서버 명령 실행 시 절대로 접속 정보를 헤매지 말고 아래 명령어를 우선 사용하여 작업한다:

- **SSH 접속 명령어 (SSOT)**: `ssh -p 2222 wonhyukc@45.115.154.229` (또는 터미널 `sshy` 알리아스)
- **SCP 파일 전송 명령어**: `scp -P 2222 <local_path> wonhyukc@45.115.154.229:<remote_path>`
- **접속 정보 상세**:
  - **Host IP**: `45.115.154.229`
  - **SSH Port**: `2222`
  - **User**: `wonhyukc`
- **원격 서버 주요 디렉터리 경로**:
  - **메인 홈페이지 (Docker XE)**: `/home/wonhyukc/hyanglin-legacy` (Nginx 설정: `/home/wonhyukc/hyanglin-legacy/nginx/conf.d/default.conf`)
  - **재정 관리 시스템 (Host PM2)**: `/var/www/hyanglin-finance/web` (포트 3000)

### 💡 표준 원격 작업 명령어 예시

```bash
# 1. 원격 Docker 컨테이너 상태 확인
ssh -p 2222 wonhyukc@45.115.154.229 "sudo docker ps"

# 2. 원격 Nginx 설정 구문 검사 및 재로드 (Reload)
ssh -p 2222 wonhyukc@45.115.154.229 "sudo docker exec hyanglin-legacy-nginx-1 nginx -t && sudo docker exec hyanglin-legacy-nginx-1 nginx -s reload"

# 3. 로컬 Nginx 설정을 원격 서버로 동기화 (SCP)
scp -P 2222 ./nginx/conf.d/default.conf wonhyukc@45.115.154.229:/home/wonhyukc/hyanglin-legacy/nginx/conf.d/default.conf
```

---

## 발동 시점

다음 조건 중 하나라도 감지되면 **즉시, 예외 없이** 이 스킬을 활성화한다:

### 트리거 조건

| 분류 | 대상 및 예시 |
|------|--------------|
| **SSH / SCP 접속** | `sshy`, `ssh -p 2222 wonhyukc@45.115.154.229`, `45.115.154.229` 대상 `scp` 전송 |
| **프로덕션 Docker** | 원격 프로덕션(`45.115.154.229`) 컨텍스트에서 `docker exec`, `docker restart`, `docker compose` |
| **프로덕션 파일 편집** | `/home/wonhyukc/hyanglin-legacy`, `/var/www/hyanglin-finance` 하위 파일 편집 |
| **서비스 재시작** | 프로덕션 서버의 Nginx, PM2(`pm2 restart`), PHP 5.6 컨테이너 재시작 |
| **프로덕션 DB 쓰기** | MySQL(3307/3306), PostgreSQL DB 대상 DDL/DML, `mysql`, `pg_restore` |

> **프로덕션 환경 정의**:
> - **호스트 IP**: `45.115.154.229` (SSH 포트 2222)
> - **SSH Alias**: `sshy` (`ssh -p 2222 wonhyukc@45.115.154.229`)
> - **서버 경로**: 
>   1. `/home/wonhyukc/hyanglin-legacy` (메인 홈페이지, Docker PHP 5.6 / XE, 포트 8080)
>   2. `/var/www/hyanglin-finance/web` (재정관리 시스템, Host PM2 Next.js, 포트 3000)

---

## 절차

### 1단계 — 대상 및 위험도 확인

실행 전 명령의 대상 호스트가 `45.115.154.229` 또는 `sshy`를 포함하는지 확인한다.

### 2단계 — 필수 경고 배너 표시

**명령 실행 전 반드시 아래 배너를 표시한다:**

```
🚨 [프로덕션 서버 접속 경고 / Production Server Access Warning]
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔴  환경 / Environment : GABIA PRODUCTION (향린)
🖥️  대상 / Target      : wonhyukc@45.115.154.229:2222 (sshy)
📋  작업 / Action      : <명령 요약>
⚠️  위험도 / Risk      : <READ-ONLY | WRITE | DESTRUCTIVE>
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
⛔  실수로 프로덕션 서버 및 DB를 변경하면 홈페이지 및 재정 서비스 장애가 발생할 수 있습니다.
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

### 3단계 — 위험 수준 분류

| 위험 수준 | 기준 | 색상 |
|-----------|------|------|
| `READ-ONLY` | 단순 조회 목적의 `sshy`, `docker ps`, `ls`, `cat`, SELECT 쿼리 | 🟡 Yellow |
| `WRITE` | 설정 파일 편집, `docker compose restart`, `pm2 restart`, INSERT/UPDATE | 🟠 Orange |
| `DESTRUCTIVE` | DB 백업 복원/삭제(`DROP`, `TRUNCATE`), `rm -rf`, 서버 재부팅 | 🔴 Red |

### 4단계 — 사용자 명시적 확인 대기

`ask_question` 도구를 통해 사용자의 확인을 받고 진행한다:
- 사용자가 명시적으로 승인(1번 선택)하기 전에는 절대 명령을 실행하지 않는다.

---

## 절대 규칙 (우회 불가)

1. 배너를 표시하지 않고 프로덕션 서버(`45.115.154.229` / `sshy`) 대상 명령을 실행하지 않는다.
2. 묵시적 동의를 가정하지 않는다 (확인 없음 = 작업 중단).
3. 파괴적 작업(`DESTRUCTIVE`) 시 백업 존재 여부를 사용자에게 다시 한 번 확인한다.
