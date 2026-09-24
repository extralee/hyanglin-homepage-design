# 🌐 가비아 프로덕션 서버 사이트 목록

> **서버**: 45.115.154.229 (가비아 VPS)
> **마지막 업데이트**: 2026-09-23

---

## 활성 사이트

정기적으로 사용되며 POST(글쓰기/로그인)가 허용된 사이트입니다.

| # | 사이트명 | 도메인 | 포트 | 컨테이너 | CMS | 설명 |
|---|---|---|---|---|---|---|
| 1 | **향린교회** | [hyanglin.org](https://www.hyanglin.org/) | 8080 | web | XE (PHP 5.6) | 메인 홈페이지. 성도 및 방문자용 |
| 2 | **안병무도서관** | [ahn-library.org](https://www.ahn-library.org/) | 8087 | ahn-library | 그누보드4 + OpenBiblio (PHP 5.6) | 도서 검색·대출, 커스텀 관리자(일정/업무일지/업무시간) |
| 3 | **이양노 갤러리** | [ongallery.co.kr](https://www.ongallery.co.kr/) | 8081 | ongallery | XE (PHP 5.6) | 이양노 10주기 회고전 |
| 4 | **향린 재정 시스템** | [finance.hyanglin.org](https://finance.hyanglin.org/) | 3000 | Host PM2 (Next.js) | Next.js | 지출결의서 및 재정 관리 (**비-Docker**) |
| 5 | **심원 아카이브** | [simwon.org](https://www.simwon.org/) | 8086 | simwon | XE (PHP 5.6) | 심원 안병무 아카이브 (XE 로그인 폼 존재) |
| 6 | **법무법인오늘** | [법무법인오늘.com](https://www.xn--wh1b76ni4aba943jj2b.com/home/) | 8091 | cnblaw | XE (PHP 5.6) | 법무법인 오늘 (한글 도메인, 퓨니코드: xn--wh1b76ni4aba943jj2b.com) |

---

## 아카이브 사이트 (읽기 전용)

현재 활발히 사용되지 않으며, 보안을 위해 **POST 요청이 nginx에서 차단**되어 있습니다.

> **[읽기 전용]이란?**
> 파일 시스템의 쓰기 권한 제한이 아니라, **HTTP 프로토콜 레벨의 보안 정책**입니다.
>
> | HTTP 메서드 | 허용 여부 | 설명 |
> |---|---|---|
> | **GET** | ✅ 허용 | 페이지 조회, 글 읽기, 이미지 로딩 등 |
> | **POST** | ❌ 차단 | 글쓰기, 로그인, 댓글, 파일 업로드 등 |
>
> 차단 주체는 **nginx 리버스 프록시**입니다. POST 요청이 백엔드 PHP/XE 앱에 도달하기 전에 nginx가 먼저 막으므로, 사이트 자체는 정상 서비스 중이지만 방문자는 **읽기(열람)만 가능**합니다.

| # | 사이트명 | 도메인 | 포트 | 컨테이너 | CMS | 설명 |
|---|---|---|---|---|---|---|
| 5 | **박형규 기념사업회** | [parkhyungkyu.org](https://www.parkhyungkyu.org/) | 8082 | parkhk | XE (PHP 5.6) | (사)박형규목사기념사업회 |
| 6 | **해랑** | [haerangart.com](https://www.haerangart.com/) | 8083 | haerangart | 그누보드4 (PHP 5.6) | 해랑 예술 사이트 |
| 7 | **교육비평** | [educrit.org](https://www.educrit.org/) | 8084 | educrit | XE (PHP 5.6) | 교육비평 저널 |
| 8 | **길목** | [gilmok.org](https://www.gilmok.org/) | 8085 | gilmok | XE (PHP 5.6) | 길목 / 심심프로그램 (simsimprogram.org 포함) |
| 9 | **심원 아카이브** | [simwon.org](https://www.simwon.org/) | 8086 | simwon | XE (PHP 5.6) | 심원 안병무 아카이브 |
| 10 | **로로브레인** | [rorobrain.com](https://www.rorobrain.com/) | 8088 | rorobrain | 그누보드4 (PHP 5.6) | 로로브레인 (RoRo 이규성 운영) |
| 11 | **비북** | [b-book.co.kr](https://www.b-book.co.kr/) | 8089 | bbook | 그누보드4 (PHP 5.6) | 비북 출판 |
| 12 | **시네마 버킷리스트** | [cinemabucketlist.com](https://www.cinemabucketlist.com/) | 8090 | moviediary | 그누보드4 (PHP 5.6) | 영화 버킷리스트 |

---

## 인프라 서비스

| 서비스 | 포트 | 컨테이너 | 설명 |
|---|---|---|---|
| **Nginx** (리버스 프록시) | 80, 443 | nginx | SSL 종단, 보안 헤더, WAF, 리버스 프록시 |
| **Certbot** | - | certbot | Let's Encrypt SSL 자동 갱신 (12시간 주기) |
| **Legacy MySQL** | 127.0.0.1:3307 | legacy-mysql | MySQL 5.7 (KT 서버 이관 DB, Docker) |
| **Host MySQL** | 127.0.0.1:3306 | Host 서비스 | MySQL 8.x (메인 DB, 비-Docker) |
| **PostgreSQL** | 5432 | pgsql | PostgreSQL (재정 시스템용, Docker) |

---

## 테스트 환경

| 사이트 | 도메인 | 포트 | 설명 |
|---|---|---|---|
| 재정 시스템 (테스트) | [test-finance.hyanglin.org](https://test-finance.hyanglin.org/) | 3001 | 재정 시스템 개발/테스트 인스턴스 |

---

## 포트 맵 요약

```
80   → Nginx (HTTP → HTTPS 리다이렉트)
443  → Nginx (SSL 종단)
3000 → 향린 재정 시스템 (Host PM2)
3001 → 재정 시스템 테스트 (Host PM2)
3306 → Host MySQL 8.x (127.0.0.1 바인딩)
3307 → Docker Legacy MySQL 5.7 (127.0.0.1 바인딩)
5432 → PostgreSQL (Docker)
8080 → 향린교회 (Docker PHP)
8081 → 이양노 갤러리 (Docker PHP)
8082 → 박형규 기념사업회 (Docker PHP)
8083 → 해랑 (Docker PHP)
8084 → 교육비평 (Docker PHP)
8085 → 길목 (Docker PHP)
8086 → 심원 아카이브 (Docker PHP)
8087 → 안병무도서관 (Docker PHP)
8088 → 로로브레인 (Docker PHP)
8089 → 비북 (Docker PHP)
8090 → 시네마 버킷리스트 (Docker PHP)
```

---

## DNS 관리

도메인별 네임서버와 DNS 관리 웹 콘솔 주소입니다.

### DNSEver (무료 DNS 호스팅)
- **관리 콘솔**: [https://www.dnsever.com](https://www.dnsever.com)

| 도메인 | 네임서버 |
|---|---|
| hyanglin.org | ns384.dnsever.com |
| ahn-library.org | ns347.dnsever.com |
| ongallery.co.kr | ns327.dnsever.com |
| haerangart.com | ns327.dnsever.com |
| educrit.org | ns347.dnsever.com |
| gilmok.org | ns231.dnsever.com |
| rorobrain.com | ns366.dnsever.com |
| b-book.co.kr | ns301.dnsever.com |

### 가비아 (Gabia)
- **관리 콘솔**: [https://dns.gabia.com](https://dns.gabia.com)
- **도메인 관리**: [https://domain.gabia.com](https://domain.gabia.com)

| 도메인 | 네임서버 |
|---|---|
| parkhyungkyu.org | ns.gabia.net |
| simwon.org | ns.gabia.net |
| cinemabucketlist.com | ns1.gabia.co.kr |

> 모든 도메인의 A 레코드는 가비아 VPS IP `45.115.154.229`를 가리켜야 합니다.

---

## 보안 현황 (2026-09-23 감사 기준)

| 항목 | 상태 |
|---|---|
| SSL (HTTPS) | ✅ 전 도메인 적용, 자동 갱신 |
| 보안 헤더 | ✅ X-Frame-Options, HSTS, XSS-Protection, nosniff |
| SQL Injection WAF | ✅ nginx에서 시그니처 패턴 차단 |
| 아카이브 POST 차단 | ✅ 8개 읽기전용 사이트 |
| DB 외부 접근 | ✅ MySQL 127.0.0.1 바인딩 |
| phpMyAdmin 차단 | ✅ nginx 403 |
| config.php 차단 | ✅ nginx 403 |
| fail2ban (SSH) | ✅ sshd jail 활성 |
