# 레거시 서버 게시판(문서) 데이터 현황 분석

이 문서는 레거시 향린 서버 (14.63.198.35)의 MySQL 데이터베이스(`hr2` 및 `hr`)에서 직접 추출한 게시판(문서 카테고리) 및 문서 수 현황입니다. 이 목록을 바탕으로 신규 시스템으로 마이그레이션할 핵심 대상과 아카이빙 처리할 대상을 분류할 수 있습니다.

## 1. 현재 홈페이지 게시판 (`hr2` DB)

최근까지 사용된 현재 홈페이지의 데이터베이스입니다. 

| ID (`mid`) | 게시판명 (`browser_title`) | 타입 (`module`) | 문서 수 |
| :--- | :--- | :--- | :--- |
| board_mjTi48 | 2번 이미지 관리 | board | 2441 |
| board_SbYk83 | 주보 \| 예배와 친교 | board | 1221 |
| b_church | 교회운영과 조직 | board | 910 |
| board_hhXD46 | 자유게시판 | board | 838 |
| board_OrAG11 | 뜻나눔 | board | 552 |
| board_Zujt14 | 사진자료실 | board | 525 |
| b_movie | 동영상자료실 | board | 513 |
| board_cdIM31 | 하늘뜻펴기 | board | 451 |
| board_hVTK46 | 이번주 성서읽기 | board | 445 |
| board_main1 | 1번 이미지 관리 | board | 416 |
| board_eCir59 | 오디오자료실 | board | 392 |
| board_EkxT26 | 성서묵상 | board | 376 |
| board_HbQf45 | 기도문 | board | 313 |
| b_notice | 공지게시판 | board | 275 |
| b_group | 모임과 활동 | board | 223 |
| board_member | 교인전용게시판 | board | 202 |
| board_rwuM37 | 3번 이미지 관리 | board | 177 |
| admin_board | 관리용게시판 | board | 53 |
| board_LJBs67 | 언론에 비친 향린 | board | 40 |
| board_BOSa70 | 자료실 | board | 27 |
| board_KxII61 | 관리자게시판 | board | 18 |
| b_paper | 향린의 신앙고백과 문서 | board | 15 |
| board_XHld94 | 목회자 / 직원 소개 | board | 10 |
| board_GsAA13 | 향린 거리좁히기 아이디어 공모 | board | 8 |
| board_OamQ28 | 향린교우 사진 코너 | board | 6 |
| board_nQZz30 | 신축건물 하자 보수 센터 | board | 3 |
| board_EwgE10 | 향린 부서충원 아이디어 공모 | board | 3 |
| page_EJfw78 | 향린생방송 | page | 2 |
| index | 향린교회 | page | 2 |
| bible_more | 성서읽기 보관용 | board | 1 |
| page_AaZN50 | 교인돌봄시스템 | page | 1 |
| page_zjlf30 | 찾아오는 길 | page | 1 |
| page_gizt51 | 향린아카이브 | page | 1 |
| page_QATA26 | 향린교회 소개 | page | 1 |
| page_vEqr50 | 교회생활 안내 | page | 1 |
| page_VxYW08 | 주일출석명부 | page | 1 |
| page_NoiR54 | 교회 연혁 | page | 1 |
| page_iEOW91 | 스케줄 | page | 1 |

---

## 2. 과거 홈페이지 게시판 (`hr` DB)

과거(구형) 홈페이지에서 사용되던 방대한 자료 모음입니다. 특히 열린게시판(`free01`)에 가장 많은 문서를 포함하고 있습니다. (게시글 100개 이상 주요 모듈 위주)

| ID (`mid`) | 게시판명 (`browser_title`) | 타입 (`module`) | 문서 수 |
| :--- | :--- | :--- | :--- |
| free01 | 열린게시판 | board | 17766 |
| mok03 | 예배동영상 | board | 2934 |
| notice1 | 공지사항 | board | 1881 |
| mem02 | 청년신도회 | board | 1388 |
| mem03 | 희년청년회 | board | 1295 |
| school02 | 유치부 | board | 1124 |
| album01 | 교회행사앨범 | board | 1108 |
| mok07 | 향린동영상 | board | 1106 |
| news1 | 향린뉴스 | board | 1105 |
| part04 | 사회부 | board | 1075 |
| schedule | 일정표 | schedule | 978 |
| mok08 | 향린오디오 | board | 931 |
| mem04 | 청년남신도회 | board | 877 |
| album02 | 부서/신도회앨범 | board | 844 |
| part05_01 | 성가대 | board | 712 |
| free03 | 홍보물게시판 | board | 663 |
| album05 | 목회자전용앨범 | board | 654 |
| school01 | 유아부 | board | 588 |
| school04 | 청소년부 | board | 576 |
| group09 | 향린 철공소 | board | 561 |
| album03 | 누구나앨범 | board | 557 |
| mem05 | 청년여신도회 | board | 544 |
| free02 | 나눔(펌)게시판 | board | 536 |
| mok05 | 주보 | board | 529 |
| school03 | 어린이부 | board | 520 |
| church03 | 목회운영위원회 | board | 402 |
| webzine09 | 통일한마당 | board | 364 |
| part03_04 | 통일선교위원회 | board | 325 |
| webzine08 | 홍근수목사 칼럼 | board | 310 |
| mem01 | 새날청년회 | board | 298 |
| mok11 | 하늘뜻펴기 나눔 | board | 282 |
| group08 | 강정구 교우 대책위 | board | 279 |
| mem06 | 장년남신도회 | board | 248 |
| talk02 | 교회개혁 | board | 238 |
| work01 | 60년사편찬 워킹자료 | board | 230 |
| church02 | 당회 | board | 230 |
| talk01 | 시사토론방 | board | 219 |
| mem09 | 희년남신도회 | board | 195 |
| mok04 | 목회기도 | board | 187 |
| bible01 | 성서배움마당 | board | 145 |
| mok04_1 | 감사기도 | board | 132 |
| part07_01 | 향린의 살림살이 | board | 118 |
| webzine12 | 담임목사 안식년 소식 | board | 100 |

*(참고: 100개 미만의 소규모 게시판들은 아카이빙을 고려하여 생략 또는 별도 관리 예정)*

## 3. 향후 논의 사항 (Step 3)
* **마이그레이션 핵심 대상 선정**: 하늘뜻펴기, 주보, 공지사항, 칼럼, 기도문 등 신규 시스템에서도 계속 사용할 핵심 데이터
* **아카이빙 대상**: 이미지/동영상 관리용 게시판, 활동이 중단된 과거 부서/모임 게시판 등은 새 시스템에는 노출하지 않고 보관만 할 것인지 결정
* **병합 대상**: 구 홈페이지(`hr`)의 열린게시판, 예배동영상 등과 현 홈페이지(`hr2`)의 자유게시판, 동영상자료실 등을 어떻게 합칠지 논의
