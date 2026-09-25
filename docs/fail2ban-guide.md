# fail2ban 초보자 가이드

> 이 문서는 서버 관리가 처음인 분도 이해할 수 있도록 **fail2ban이 뭔지, 왜 필요한지, 어떻게 설정하는지**를 쉽게 설명합니다.

---

## 1. fail2ban이 뭔가요?

### 한 줄 요약
> **나쁜 놈이 문을 너무 많이 두드리면 자동으로 출입 금지시키는 경비원**

### 비유로 이해하기

우리 집(서버)에는 현관문(웹사이트)이 있습니다.

- **정상 방문자**: 문을 열고 들어와서 구경하고 나갑니다.
- **나쁜 봇**: 1초에 수십 번씩 문을 두드리고, 모든 방을 뒤지고, 서버를 과부하시킵니다.

fail2ban은 이런 경비원입니다:

```
👀 "저 사람, 1분에 120번이나 문을 두드리네?"
🚫 "30분간 출입 금지!" (iptables에 IP 차단 등록)
⏰ (30분 후) "풀어줄게. 또 그러면 다시 차단이야."
```

### 왜 꼭 필요한가요?

| 없을 때 | 있을 때 |
|---------|---------|
| 봇이 폭주하면 서버 다운 | 자동으로 차단, 서버 정상 유지 |
| 관리자가 직접 IP 찾아서 차단해야 함 | 알아서 탐지하고 알아서 차단 |
| 새벽에 공격당하면 아침까지 다운 | 24시간 자동 방어 |
| 매번 다른 봇에 대응 못함 | 봇 종류 상관없이 과다 요청 자체를 차단 |

---

## 2. 어떻게 동작하나요?

```
[인터넷에서 오는 요청]
        │
        ▼
┌─────────────────┐
│  iptables 방화벽  │  ← fail2ban이 여기에 차단 규칙을 추가
│  (커널 레벨)      │     차단된 IP는 여기서 바로 DROP
└────────┬────────┘
         │ (통과한 요청만)
         ▼
┌─────────────────┐
│    Nginx         │  → 접속 로그 기록 (/data/log/nginx/access.log)
└────────┬────────┘
         │                    ↑
         ▼                    │ fail2ban이 이 로그를 실시간 감시
┌─────────────────┐           │ "이 IP가 1분에 120번 넘게 왔네!"
│  PHP / 웹 앱     │           │ → iptables에 차단 규칙 추가
└─────────────────┘
```

**핵심 포인트:**
- fail2ban은 **로그 파일을 읽어서** 나쁜 패턴을 찾습니다
- 찾으면 **iptables(방화벽)에 규칙을 추가**해서 해당 IP를 차단합니다
- 일정 시간 후 **자동으로 차단을 해제**합니다

---

## 3. 설치하기

### Ubuntu / Debian 계열

```bash
# 설치
sudo apt update
sudo apt install -y fail2ban

# 자동 시작 등록 + 지금 바로 시작
sudo systemctl enable --now fail2ban

# 잘 설치됐나 확인
sudo fail2ban-client status
```

정상이면 이렇게 나옵니다:
```
Status
|- Number of jail:    1
`- Jail list:    sshd
```

> 💡 **jail(감옥)**이란? fail2ban에서 "이런 종류의 공격을 감시하겠다"는 설정 묶음입니다.
> 기본으로 `sshd` jail이 활성화되어, SSH 비밀번호 무차별 대입 공격을 방어합니다.

---

## 4. 설정 파일 구조

```
/etc/fail2ban/
├── jail.conf          ← 기본 설정 (절대 수정하지 마세요!)
├── jail.local         ← ★ 우리가 수정할 파일 (이걸 새로 만듭니다)
├── jail.d/            ← 추가 설정 폴더
│   └── defaults-debian.conf
└── filter.d/          ← 로그 패턴 정의 폴더
    ├── sshd.conf      ← SSH 로그 패턴 (기본 제공)
    └── nginx-flood.conf  ← ★ 우리가 만들 Nginx 패턴
```

> ⚠️ **중요**: `jail.conf`를 직접 고치면 업데이트 시 덮어씌워집니다.
> 항상 `jail.local`에 우리 설정을 넣으세요. `jail.local`이 `jail.conf`보다 우선합니다.

---

## 5. 설정하기 (단계별)

### 5-1. jail.local 만들기

```bash
sudo nano /etc/fail2ban/jail.local
```

아래 내용을 붙여넣으세요:

```ini
# ──────────────────────────────────
# fail2ban 로컬 설정
# ──────────────────────────────────

[DEFAULT]
# 차단 시간: 1800초 = 30분
bantime = 1800

# 관찰 시간: 60초 = 1분
# "1분 동안 몇 번 왔는지" 세는 기간
findtime = 60

# 최대 허용 횟수: 1분에 120번 넘으면 차단
maxretry = 120

# 차단 방식: iptables 사용
banaction = iptables-multiport


# ── SSH 보호 ──────────────────────
[sshd]
enabled = true
port = 2222
# SSH는 더 엄격하게: 5번 틀리면 1시간 차단
maxretry = 5
bantime = 3600


# ── 웹서버(Nginx) 과다 요청 차단 ──
[nginx-flood]
enabled = true
filter = nginx-flood
action = iptables-multiport[name=nginx-flood, port="http,https"]
# ★ 이 경로를 여러분 서버의 Nginx 로그 경로로 바꾸세요
logpath = /data/log/nginx/access.log
findtime = 60
maxretry = 120
bantime = 1800
```

> 📖 **설정값 해석하기**
>
> `findtime = 60`, `maxretry = 120` → "1분(60초) 동안 120번 넘게 접속하면"
>
> `bantime = 1800` → "30분(1800초) 동안 차단"

### 5-2. Nginx 로그 필터 만들기

```bash
sudo nano /etc/fail2ban/filter.d/nginx-flood.conf
```

아래 내용을 붙여넣으세요:

```ini
[Definition]
# Nginx 로그에서 IP와 HTTP 요청을 찾는 패턴
failregex = ^<HOST> - .* \[.*\] "(GET|POST|HEAD|PUT|DELETE|OPTIONS) .* HTTP/
ignoreregex =

[Init]
# Nginx 로그의 날짜 형식 지정
datepattern = %%d/%%b/%%Y:%%H:%%M:%%S %%z
```

> 💡 `<HOST>`는 fail2ban이 자동으로 IP 주소로 치환하는 특수 변수입니다.

### 5-3. 필터가 잘 동작하는지 테스트

```bash
# Nginx 로그 최근 5줄로 테스트
tail -5 /data/log/nginx/access.log > /tmp/test-log.txt
sudo fail2ban-regex /tmp/test-log.txt /etc/fail2ban/filter.d/nginx-flood.conf
```

이렇게 나오면 성공:
```
Lines: 5 lines, 0 ignored, 5 matched, 0 missed
```

> ⚠️ `0 matched`가 나오면? → 여러분 서버의 Nginx 로그 형식이 다를 수 있습니다.
> `tail -1 /data/log/nginx/access.log`로 실제 로그를 확인하고 `failregex`를 맞춰야 합니다.

### 5-4. fail2ban 재시작

```bash
sudo systemctl restart fail2ban
```

### 5-5. 잘 동작하는지 확인

```bash
# 전체 상태 보기
sudo fail2ban-client status

# nginx-flood jail 상태 보기
sudo fail2ban-client status nginx-flood
```

---

## 6. 일상 관리 명령어 모음

이것만 외우면 됩니다:

```bash
# ── 상태 확인 ──
sudo fail2ban-client status              # 전체 jail 목록
sudo fail2ban-client status nginx-flood   # 특정 jail 상세 (차단된 IP 목록)
sudo fail2ban-client status sshd          # SSH jail 상세

# ── 수동 차단/해제 ──
sudo fail2ban-client set nginx-flood banip 1.2.3.4     # 특정 IP 수동 차단
sudo fail2ban-client set nginx-flood unbanip 1.2.3.4   # 특정 IP 차단 해제

# ── 서비스 관리 ──
sudo systemctl restart fail2ban    # 재시작 (설정 변경 후)
sudo systemctl status fail2ban     # 서비스 상태 확인

# ── 로그 보기 ──
sudo tail -50 /var/log/fail2ban.log   # fail2ban 자체 로그 (차단 이력)
```

---

## 7. "우리 서버 IP가 차단됐어요!" 긴급 대처

가끔 관리자 본인의 IP가 차단될 수 있습니다 (새로고침을 너무 많이 했거나).

```bash
# 1. 어떤 jail에서 차단됐는지 확인
sudo fail2ban-client status nginx-flood

# 2. 내 IP 차단 해제
sudo fail2ban-client set nginx-flood unbanip 내.IP.주소

# 3. 특정 IP를 영구적으로 차단 제외하려면:
#    jail.local의 [DEFAULT] 섹션에 추가
#    ignoreip = 127.0.0.1/8 내.IP.주소
```

---

## 8. Docker 환경에서의 주의사항

> ⚠️ fail2ban은 반드시 **호스트 OS**에 설치해야 합니다.
> Docker 컨테이너 안에 설치하면 iptables 권한이 없어서 동작하지 않습니다.

```
호스트 OS (여기에 fail2ban 설치)
├── Docker: Nginx 컨테이너
│   └── 로그 → /data/log/nginx/access.log (호스트와 볼륨 공유)
├── Docker: PHP 컨테이너
└── Docker: MySQL 컨테이너
```

fail2ban이 Docker Nginx의 로그를 읽으려면, docker-compose.yml에서
로그 디렉토리가 호스트와 공유되어 있어야 합니다:

```yaml
# docker-compose.yml 예시
services:
  nginx:
    volumes:
      - /data/log/nginx:/var/log/nginx   # ← 이 줄이 있어야 함
```

---

## 9. 우리 서버(향린교회)의 실제 설정

### 현재 활성 jail

| jail | 대상 | 기준 | 차단 시간 |
|------|------|------|-----------|
| `sshd` | SSH 접속 (포트 2222) | 5회 비밀번호 실패 | 1시간 |
| `nginx-flood` | 웹 요청 (HTTP/HTTPS) | 1분에 120회 초과 | 30분 |

### 로그 파일 위치

| 로그 | 경로 | 용도 |
|------|------|------|
| Nginx 접속 로그 | `/data/log/nginx/access.log` | fail2ban이 감시하는 대상 |
| fail2ban 자체 로그 | `/var/log/fail2ban.log` | 차단/해제 이력 확인 |

### 설정 파일 위치

| 파일 | 경로 |
|------|------|
| jail 설정 | `/etc/fail2ban/jail.local` |
| Nginx 필터 | `/etc/fail2ban/filter.d/nginx-flood.conf` |

---

## 10. 실전 사례: 이것 때문에 fail2ban을 설치했습니다

### 사례 1: 알리바바 봇넷 공격 (이슈 #79)
- 중국 알리바바 클라우드 IP에서 초당 수백 건 요청
- Apache 워커 150개 전부 잠식 → 홈페이지 504 다운
- **수동 대응**: Nginx에 IP 대역 차단 추가

### 사례 2: KeenableBot 크롤러 폭주 (이슈 #79 2차)
- AI 크롤러가 정상 브라우저 UA로 위장하여 초당 수십 건 크롤링
- 또다시 워커 153개 잠식 → 504 다운
- **수동 대응**: Nginx에 UA 차단 추가

### 교훈
- 매번 새로운 봇이 올 때마다 수동 대응은 한계가 있음
- **fail2ban이 있었다면** → IP당 요청 수를 감시하여 자동 차단됐을 것
- 봇의 이름(UA)을 몰라도, IP를 바꿔도 → **과다 요청 자체를 기준으로 차단**하므로 방어 가능

---

## 11. FAQ

### Q: Googlebot 같은 정상 크롤러도 차단되나요?
A: Googlebot은 보통 분당 10-20건 정도로 예의 바르게 크롤링합니다. `maxretry = 120`(분당 120건)이면 정상 봇은 절대 걸리지 않습니다.

### Q: maxretry를 몇으로 설정해야 하나요?
A: 사이트 규모에 따라 다르지만, 일반적으로:
- **소규모 사이트**: 60-120 (분당)
- **중규모 사이트**: 200-300 (분당)
- 너무 낮게 잡으면 정상 사용자가 차단될 수 있습니다

### Q: bantime을 영구적으로 하면 안 되나요?
A: `bantime = -1`로 설정하면 영구 차단됩니다. 하지만 정상 사용자가 실수로 차단됐을 때 자동 해제되지 않으므로 권장하지 않습니다. 30분-1시간이 적당합니다.

### Q: Rate Limiting(Nginx limit_req)과 뭐가 다른가요?
A: 
- **Rate Limiting**: Nginx가 429 응답을 보냄 → 이미지/CSS/JS도 같이 차단될 수 있음 → 화면 깨짐 위험 (이슈 #44에서 실제 발생!)
- **fail2ban**: iptables에서 패킷 자체를 DROP → 정상 사용자에게 영향 없음
