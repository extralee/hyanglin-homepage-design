# Cloudflare DNS 연동 및 설정 가이드

Cloudflare DNS를 통해 도메인의 DNS 관리, CDN, DDoS 방어 및 무료 SSL/TLS 기능을 적용하는 상세 가이드 문서입니다.

---

## 1. 주요 개념 및 메뉴 선택 주의사항

### ⚠️ 'Connect a domain' vs 'Transfer a domain' vs 'Buy a domain'
Cloudflare 대시보드에서 `Add a site`를 누르면 3가지 메뉴 카드가 표시됩니다:

1. **Connect a domain [선택 대상]**:
   - 가비아 등 기존 도메인 등록업체에서 도메인을 유지하면서, 네임서버만 Cloudflare로 변경하여 **무료 DNS/보안/SSL 서비스**를 사용하는 방식입니다.
2. **Transfer a domain**:
   - 도메인 등록기관 자체를 Cloudflare 레지스트라로 이전하는 유료 기관이전 기능입니다. 단순 DNS 연동 시 선택하지 마십시오.
3. **Buy a domain**:
   - Cloudflare에서 신규 도메인을 직접 구매하는 기능입니다.

---

## 2. 연동 단계별 상세 절차

### 1단계: Cloudflare 대시보드 진입 및 메뉴 선택
1. [Cloudflare 대시보드](https://dash.cloudflare.com/)에 로그인합니다.
2. 대시보드 우측 상단의 **`Add a site`** 버튼을 클릭합니다.
3. 화면에 표시된 3가지 카드 옵션 중 첫 번째인 **`Connect a domain`**을 클릭합니다.

### 2단계: 도메인 입력 및 요금제 선택
1. 입력 창에 보유한 도메인 이름(예: `hyanglin.org`)을 입력하고 **Continue**를 클릭합니다.
2. 요금제 선택 화면에서 하단의 **Free(무료)** 요금제를 선택한 후 계속 진행합니다.

### 3단계: DNS 레코드 스캔 및 프록시 상태 설정
Cloudflare가 기존 도메인의 DNS 레코드를 자동 스캔합니다.
- **A 레코드**: 서버의 실제 IP 주소가 올바르게 지정되어 있는지 확인합니다.
- **MX 레코드**: 이메일 서버 레코드가 유실되지 않았는지 점검합니다.
- **프록시 상태 (Proxy Status) 설정**:
  - 🟧 **주황색 구름 (Proxied)**: Cloudflare CDN, DDoS 방어, SSL 트래픽 중계 적용 (웹 포트: 80, 443 등)
  - ⬜ **회색 구름 (DNS Only)**: 순수 DNS 조회 기능만 수행 (SSH 22번 포트, FTP 등 직접 접속용)

### 4단계: 도메인 등록업체(가비아 등) 네임서버 변경
1. Cloudflare에서 새로 부여받은 **2개의 지정 네임서버 주소**를 확인합니다. (예: `ada.ns.cloudflare.com`, `bob.ns.cloudflare.com`)
2. 도메인 등록업체(가비아, 카페24 등)의 도메인 관리 페이지에 접속합니다.
3. 기존 1차, 2차 네임서버를 Cloudflare 네임서버 주소로 변경하여 저장합니다.
4. 네임서버 변경 반영에는 **몇 분에서 최대 24시간 이내**가 소요될 수 있습니다.

### 5단계: SSL/TLS 암호화 모드 설정
Cloudflare 대시보드의 **`SSL/TLS` -> `Overview`** 메뉴에서 서버 환경에 맞는 암호화 모드를 선택합니다:
- **Flexible**: 웹서버에 SSL 인증서가 없고 HTTP만 지원할 때 선택 (방문자-Cloudflare 구간만 HTTPS)
- **Full / Full (Strict)**: 웹서버에 이미 Let's Encrypt 등의 SSL이 적용된 경우 선택 (전 구간 HTTPS 암호화, 권장)

---

## 3. 운영 및 트러블슈팅 팁

### SSH 및 외부 포트 직접 접속 차단 해결
- Cloudflare **주황색 구름 (Proxied)** 적용 시 SSH(22번 포트) 등 비웹 트래픽이 차단됩니다.
- SSH 접속용 전용 서브도메인(예: `sshy.example.org`)을 별도 추가하고, 해당 레코드의 프록시 상태를 **회색 구름 (DNS Only)**으로 설정하여 사용하세요.
