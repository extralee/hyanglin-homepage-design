# 레거시 홈페이지 서버 현황 (Legacy Homepage Server Status)

본 문서는 향린교회 레거시 홈페이지가 구동 중인 원격 서버(`14.63.198.35`)의 환경 및 폴더 구조 분석 결과를 기록합니다. 현재 로컬 환경(`hyanglin-finance` 프로젝트)과는 물리적으로 분리되어 운영 중입니다.

## 1. 서버 기본 정보
- **IP 주소**: `14.63.198.35`
- **SSH 포트**: `2222`
- **운영체제**: CentOS release 5.8 (Final)
- **커널 버전**: Linux 2.6.18-348.12.1.el5xen x86_64

## 2. 보안 및 패치 상태 (⚠️ 경고)
- **EOL (End of Life)**: CentOS 5 버전은 2017년 3월부로 공식 지원이 완전히 종료되었습니다.
- **업데이트 불가**: 패키지 관리자(`yum`)의 미러(Mirror) 서버 연결이 중단되어 최신 보안 패치 및 패키지 다운로드가 원천적으로 불가능합니다.
- **보안 취약점**: 지난 수년간 발견된 OS 및 시스템 취약점(Dirty COW 등)에 그대로 노출되어 있으므로, 신규 프로젝트나 장기적 운영을 위해서는 신규 서버 인프라로의 이전(Migration)이 강력히 권장됩니다.

## 3. 가상 호스팅(Virtual Host) 폴더 계층 및 용도
해당 원격 서버는 시스템 기본 웹 디렉토리(`/var/www/html`)를 사용하지 않고, 호스팅 서버처럼 **`/home/[실제_사용자_계정명]/www/`** 형태로 여러 사이트를 분리하여 운영 중입니다. 여기서 `[실제_사용자_계정명]`은 단순히 '계정명'이라는 단어가 아니라, 서버에 등록된 실제 아이디(예: `hr`, `cnblaw` 등)를 의미합니다.

> ⚠️ **주의 — 향린교회 사이트의 실제 위치**
> 직관과 달리 `hyanglin.org`의 DocumentRoot는 `/home/hyanglin/www`가 **아니라** `/home/hr/www`입니다.
> Apache VirtualHost 설정(`/etc/httpd/conf/httpd.conf`)에 다음과 같이 명시되어 있습니다.
> ```
> <VirtualHost *:80>
>     DocumentRoot /home/hr/www
>     ServerName hyanglin.org
>     ServerAlias www.hyanglin.org new.hyanglin.org hyanglin.org
> ```
> `/home/hyanglin/www/`에는 placeholder `index.html`만 남아 있고 실제 코드는 없습니다.

### 📁 주요 폴더 구조 및 분석 (전체 목록)
- **`/var/www/html`**
  - **용도**: 리눅스 기본 웹 서버 경로
  - **상태**: 2016년 이후 파일 변동이 없으며 사실상 사용되지 않는 빈 껍데기입니다.

- **`/home/`** (서버 폐기 전 확인해야 할 전체 디렉토리 목록 및 용량 - 2026-04-30 전수 조사 완료)
  - **`hr/` [68G]** ⭐: **향린교회 본 사이트(`hyanglin.org`)의 실제 DocumentRoot**. 
    - 하위 `www/` 폴더 내 주요 용량: `home` (41G), `bbs_old` (28G), `contents` (118M), `img` (50M), `library` (17M), `phpMyAdmin` (6.9M), `images` (2.1M) 등.
    - *💡 상세 분석*: `home`과 `bbs_old` 모두 XpressEngine(XE) 기반 게시판으로, 전체 69G 중 대부분(41G + 28G = 69G)은 사용자가 업로드한 첨부파일과 미디어가 저장되는 내부의 `files/` 디렉토리가 차지하고 있습니다. 순수 소스 코드 용량은 수십 MB에 불과합니다.
  - **`hyanglin/` [32K]**: 명목상 향린교회 계정으로 보이지만 실제 사이트 운영에는 사용되지 않는 빈 껍데기. `www/` 안에는 placeholder `index.html`(2018-05-12)과 `robots.txt`(2025-06-01)만 존재.
  - **`mysql/` [8.2G]**: 동일 서버 내에 구축된 데이터베이스(MySQL)의 원본 데이터 저장소 (소유자: root). 실제 데이터는 `/home/mysql/data/`에 있습니다.
  - **`logs/` [1.1G]**: 시스템 및 웹 서버(Apache 등) 로그 파일 (소유자: root). (최근 0.1G 증가)
  - **`mail/` [5.6M]**: 사용자별 시스템 메일 데이터 보관용 (소유자: root)
  - **`lost+found/` [16K]**: 파일 시스템 디스크 오류 복구용 (소유자: root)
  - **`www/` [20K]**: `/home/www/` 레벨에 별도로 존재하는 root 소유 폴더. **이름과 달리 `httpd.conf`의 전역 기본 `DocumentRoot "/home/www"`로 등록**되어 있어 매칭되는 VirtualHost가 없는 모든 요청을 받습니다(`index.html`, `index.php`, `info.php`, `robots.txt`).
  - **기타 호스팅 사이트 및 개인 계정 폴더들**: 향린교회 외에 동일 서버에 얹혀서 구동 중이던 소규모 사이트나 개인 데이터들입니다. **서버 삭제 전, 이 계정들의 데이터(홈페이지, DB 등) 역시 모두 영구 삭제되므로 유관 부서/인원의 확인이 반드시 필요합니다.**
    - `ahn-library/` [110M] (소유자: ongallery)
    - `bbook/` [287M] (소유자: bbook)
    - `cnblaw/` [192M] (소유자: cnblaw)
    - `educrit/` [2.3G] (소유자: educrit)
    - `gilmok/` [8.8G] (소유자: gilmok) - '길목' 관련 사이트
    - `haerangart/` [732M] (소유자: haerangart) - 해랑아트
    - `jaemisama/` [172M] (소유자: jaemisama)
    - `kscf/` [3.6G] (소유자: root) - KSCF (한국기독학생회총연맹) 관련 파일/폴더
    - `moviediary/` [160M] (소유자: moviediary) - 무비다이어리
    - `ongallery/` [307M] (소유자: ahn-library) - 온갤러리
    - `parkhk/` [597M] (소유자: parkhk)
    - `rorobrain/` [3.2G] (소유자: rorobrain)
    - `simwon/` [23G] (소유자: simone) - 심원 관련 사이트 (마찬가지로 23G 전부가 XE 게시판의 `files/` 폴더인 첨부파일 용량입니다.)
  - **시스템 계정만 등록되고 `/home/` 디렉토리는 없는 추가 계정들** (서버 폐기 전 별도 확인 권장 — `getent passwd`로 전수 조회): `baegak`, `footact`, `forest`, `forest6`, `foundationtheforest`, `hanjifoundation`, `isangyun`, `kice`, `kpggoldenbell`, `masambooks`, `mypicturebook`, `nowonbook`, `samilprok`, `wscfap-archive`, `yunfoundation`

## 4. 결론 및 소스 확인 방법
과거의 소스 코드나 첨부파일, 데이터베이스 연결 정보(`dbconfig.php` 등)를 확인하시려면 터미널에서 `sshh` 명령어로 접속하신 뒤, 즉시 **`cd /home/hr/www/`** 로 이동하여 소스 코드를 탐색하시면 됩니다.

> 과거 문서에는 `/home/hyanglin/www/`를 소스 위치로 안내한 적이 있으나, Apache VirtualHost 설정상 `hyanglin.org`의 실제 DocumentRoot는 `/home/hr/www`임을 교차 검증으로 확인했습니다. (`/home/hyanglin/www/`는 사용되지 않는 빈 껍데기)

## 5. `/home` 폴더 외의 주요 백업 권장 경로 (시스템 설정)
원격 서버 내부를 추가로 스캔해 본 결과, 실제 데이터(DB, 웹 소스)는 확실하게 모두 `/home`에 집중되어 있습니다. (기본 DB 경로 `/var/lib/mysql/`에는 런타임 소켓 파일(`mysql.sock`, `mysql.sock.lock`)만 존재하고 실제 데이터 파일은 `/home/mysql/data/`에 있는 것이 교차 검증됨)

다만 서버를 영구 삭제하시기 전에, 향후 **기존 환경 설정값을 참고해야 할 수도 있으므로** 아래의 '설정 파일'들은 텍스트 형태로나마 복사/백업해 두시는 것을 권장합니다.

1. **웹 서버(Apache) 가상호스트 설정 파일**
   - 경로: `/etc/httpd/conf/httpd.conf`(필수) 및 `/etc/httpd/conf.d/ssl.conf`(HTTPS 적용 분량)
   - 이유: 어떤 도메인이 어느 계정의 `/home/[계정명]/www` 폴더로 연결되었는지 규칙이 적혀있습니다. **모든 `<VirtualHost>` 블록은 단일 `httpd.conf`에 집약**되어 있고, `conf.d/` 안에서는 `ssl.conf`만 VirtualHost를 포함합니다(#67 HTTPS 적용 결과). 나머지 `conf.d/*.conf`(`php.conf`, `proxy_ajp.conf`, `welcome.conf`, `README`)는 패키지 기본값입니다.
2. **데이터베이스(MySQL) 환경 설정 파일**
   - 경로: `/etc/my.cnf`
   - 이유: 기존 데이터베이스의 문자열 인코딩(euckr, utf8 등)이나 메모리 할당 등의 설정값이 기록되어 있어, 추후 DB 이전 시 중요한 단서가 됩니다.
3. **루트 계정 히스토리 (선택 사항)**
   - 경로: `/root/.bash_history` 및 `/root/.mysql_history`
   - 이유: 과거 서버 관리자가 어떤 명령어를 쳤었는지, 어떤 DB 작업을 했었는지 확인할 수 있는 귀중한 기록입니다.
※ 시스템 예약 작업(Cron) 폴더(`/var/spool/cron`)는 확인 결과 등록된 스케줄이 없어 백업하지 않으셔도 됩니다.

## 6. 서버 이전 시 트래픽 비용 분석 (Traffic Cost Analysis)
서버 폐기 및 가비아(Gabia) 이전 과정에서 발생하는 네트워크 트래픽 비용을 분석한 결과, 현재 데이터 규모로는 **추가 비용이 발생하지 않을 것**으로 예상됩니다.

- **보내는 쪽 (엔클라우드24 / KT Cloud)**:
  - 아웃바운드 트래픽 월 1TB(1,000GB)까지 무료 제공.
  - 현재 전체 데이터(`hr`, `simwon`, `mysql` 등) 합계 약 110~150GB로 무료 범위 내에 충분히 포함됨.
- **받는 쪽 (가비아 클라우드)**:
  - 인바운드 트래픽 무료 또는 국내 트래픽 월 4TB까지 무료 제공.
  - 150GB 수준의 데이터 유입은 추가 과금 대상이 아님.
- **💡 팁**: `rsync` 수행 시 `-z` 옵션을 사용하여 데이터를 압축 전송하면 실제 소모 트래픽을 더욱 절감할 수 있습니다. (예: `rsync -avz ...`)

## 7. 웹 소스 및 데이터베이스 이관 계획 (Migration Plan)
구형 서버의 모든 서비스(향린 본 사이트, 구 게시판, 기타 호스팅 사이트 등)를 가비아 서버로 안전하게 이관하기 위한 단계별 계획입니다.

### 7.1. 데이터베이스(MySQL) 이관 계획
원격 서버 내의 데이터베이스 전수 조사 결과, 총 20개 이상의 DB가 존재함을 확인했습니다.

| 구분 | 데이터베이스명 (DB Name) | 매칭 웹 경로 (DocumentRoot) | 비고 |
| :--- | :--- | :--- | :--- |
| **현재 홈페이지** | `hr2` | `/home/hr/www/home/` | XE 기반 메인 사이트 |
| **구 홈페이지** | `hr` | `/home/hr/www/bbs_old/` | 과거 게시판 데이터 |
| **심원** | `simone` | `/home/simwon/` | 심원 안병무 기념사업회 |
| **길목** | `gilmok`, `gilmokorg` | `/home/gilmok/` | 길목 웹사이트 |
| **KSCF** | `kscf` | `/home/kscf/` (경로 추정) | 한국기독학생회총연맹 |
| **기타** | `bbook`, `cnblaw`, `educrit` 등 | `/home/[계정명]/` | 기타 호스팅 사이트들 |

**이관 절차:**
1.  **백업**: `mysqldump` 명령어를 사용하여 각 DB별로 SQL 덤프 파일을 생성합니다.
    - 예: `mysqldump -u root -p [DB_NAME] > [DB_NAME].sql`
2.  **전송**: 생성된 SQL 파일을 `rsync` 또는 `scp`를 통해 가비아 서버로 전송합니다.
3.  **복원**: 가비아 서버의 MySQL에서 DB를 생성하고 덤프 파일을 임포트합니다.
    - 예: `mysql -u root -p [DB_NAME] < [DB_NAME].sql`
4.  **권한 설정**: 각 사이트에서 사용할 DB 계정을 생성하고 권한을 부여합니다.

### 7.2. 웹 소스 코드 및 미디어 파일 이관 계획
전체 약 150GB 규모의 파일 데이터를 전송합니다.

**이관 절차:**
1.  **서버 간 직접 복사 (rsync)**: 로컬 PC를 거치지 않고 구 서버에서 가비아 서버로 직접 전송합니다.
    - 명령어 예시: `rsync -avz -e "ssh -p 2222" /home/ root@[가비아_IP]:/home/`
    - `-a`: 권한 및 타임스탬프 보존
    - `-v`: 상세 로그 출력
    - `-z`: 전송 시 압축 (트래픽 절감)
    - **💡 점진적 동기화(Incremental Sync)**: `rsync` 명령어는 변경된 부분(Delta)만 전송하는 기능이 기본 탑재되어 있습니다. 서비스 중단 시간을 최소화하기 위해, **1차로 전체 복사**를 진행해 두고, 서비스 전환(DNS 변경) 직전에 **동일한 명령어를 다시 실행(2차 복사)**하면 그동안 추가되거나 변경된 파일만 빠르게 전송할 수 있습니다. (필요 시 `--delete` 옵션을 추가하면 원본에서 삭제된 파일도 타겟에서 함께 삭제하여 양쪽 상태를 똑같이 맞출 수 있습니다.)
2.  **심볼릭 링크 확인**: 서버 내에 절대 경로로 설정된 심볼릭 링크가 있다면 이전 후 재설정이 필요합니다.

### 7.3. 환경 설정 업데이트 및 검증
1.  **DB 접속 정보 수정**: 각 사이트의 설정 파일(`db.config.php`, `dbconfig.php` 등)에서 DB 호스트, 아이디, 패스워드를 가비아 서버 기준으로 수정합니다.
2.  **PHP 버전 호환성 체크**: 구 서버는 **PHP 5.6**을 사용 중입니다. 가비아 서버에서도 해당 버전을 지원하거나, 코드 수정이 필요할 수 있습니다.
3.  **도메인 연결 (DNS)**: 가비아 서버에서 사이트 구동을 확인한 후, DNSEver 등에서 도메인의 A 레코드를 가비아 IP로 변경합니다.

### 7.4. `hr2` (현재 홈페이지) 전용 무중단 이전 시나리오
XE(XpressEngine) 기반의 메인 사이트(`hr2` DB + `/home/hr/www/home/` 소스)를 빈틈없이 이전하기 위한 구체적인 4단계 시나리오입니다.

#### [1단계] 사전 준비 및 1차 동기화 (서비스 중단 없음)
1. **DB 1차 덤프 및 전송**:
   - 구 서버: `mysqldump -u root -p hr2 > hr2_1st.sql`
   - 구 서버 -> 가비아: 생성된 SQL을 전송 후 가비아 DB(예: `hr2_gabia`)에 임포트합니다.
2. **소스 1차 전송 (캐시 제외)**:
   - 구 서버에서 실행: `rsync -avz --exclude 'files/cache' -e "ssh -p 2222" /home/hr/www/home/ root@[가비아_IP]:/가비아_경로/`
   - *(이 작업은 수십 GB이므로 오래 걸리지만 서비스엔 영향이 없습니다.)*

#### [2단계] 가비아 서버 세팅 및 사전 테스트
1. **DB 설정 변경**: 가비아 서버의 `가비아_경로/files/config/db.config.php` 파일을 열어 가비아 DB 접속 정보로 수정합니다.
2. **권한 부여 (필수)**: 가비아 서버에서 `chmod -R 777 가비아_경로/files` 명령어를 실행하여 첨부파일 업로드 및 세션 쓰기 권한을 부여합니다.
3. **PC hosts 파일 변조 테스트 (중요!)**: 
   - 작업자의 로컬 PC `hosts` 파일에 `[가비아_IP] hyanglin.org`를 임시로 추가합니다.
   - 브라우저로 `hyanglin.org` 접속 시 구 서버가 아닌 가비아 서버로 접속됨을 확인합니다.
   - 로그인, 글쓰기, 이미지 로드가 모두 정상 작동하는지 완벽히 테스트한 후, hosts 파일을 원복합니다.

#### [3단계] 최종 동기화 (DNS 전환 직전 - 야간 추천)
1. **구 서버 글쓰기 차단**: 가급적 관리자 페이지에서 잠시 사이트 접속을 막거나, 사용자 접속이 적은 새벽 시간에 진행합니다.
2. **최종 DB 동기화**: 1단계 이후 새로 작성된 글을 반영하기 위해 다시 `mysqldump` 후 가비아 서버 DB를 덮어씁니다.
3. **최종 소스 동기화 (변경분만)**:
   - 구 서버에서 실행: `rsync -avz --delete --exclude 'files/cache' -e "ssh -p 2222" /home/hr/www/home/ root@[가비아_IP]:/가비아_경로/`
   - *(수 분 내로 빠르게 완료됩니다.)*
4. **가비아 서버 캐시 비우기**: 가비아 서버의 `files/cache/` 디렉토리 안의 내용을 수동으로 전부 삭제합니다. (접속 시 자동 재생성됨)

#### [4단계] DNS 실변경
1. 가비아(또는 DNSEver 등) 설정 패널에서 `hyanglin.org`의 A 레코드 IP를 가비아 IP로 변경합니다.
2. 이전 완료.

## 8. 트러블슈팅 (Troubleshooting)

### PHP 5.6과 MySQL 8.0 호환성 문제 (Charset 255 Unknown)
* **증상**: 가비아 서버(MySQL 8.0)에 DB를 임포트하고 PHP 5.6 기반의 구형 XE 엔진에서 접속을 시도할 때 `Server sent charset (255) unknown to the client` 에러 발생.
* **원인**: MySQL 8.0의 기본 문자셋이 `utf8mb4_0900_ai_ci` (Charset 255)로 변경되었으나, 매우 오래된 구형 PHP 5.6 클라이언트 라이브러리(`mysql_connect`)는 이를 전혀 인식하지 못해 연결 자체를 거부함.
* **해결**: `/etc/mysql/mysql.conf.d/mysqld.cnf`에 아래 설정을 추가하여 MySQL 서버의 기본 동작을 구형 시스템과 호환되는 `utf8`로 강제 지정하고 MySQL 재시작.
  ```ini
  [mysqld]
  character-set-server = utf8
  collation-server = utf8_general_ci
  default-authentication-plugin = mysql_native_password
  ```
