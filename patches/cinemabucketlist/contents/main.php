<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <title>CinemaBucketlist</title>
  <link rel="stylesheet" href="style.css" />
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'NanumBarunGothic', 'Malgun Gothic', sans-serif;
      background-color: #fafbfc;
      color: #333;
    }
    .hero-container {
      width: 100%;
      max-width: 1200px;
      margin: 40px auto 20px auto;
      text-align: center;
      padding: 40px 20px 20px 20px;
      box-sizing: border-box;
    }
    .hero-title {
      font-size: 32px;
      font-weight: 700;
      color: #2b3a4a;
      margin-bottom: 12px;
      letter-spacing: -0.5px;
    }
    .hero-desc {
      font-size: 16px;
      color: #6c7a89;
      line-height: 1.6;
      max-width: 600px;
      margin: 0 auto 40px auto;
    }
    .card-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 24px;
      max-width: 1000px;
      margin: 0 auto;
      padding: 0 10px;
    }
    .feature-card {
      background: #ffffff;
      border: 1px solid #e1e8ed;
      border-radius: 12px;
      padding: 32px 28px;
      text-align: left;
      cursor: pointer;
      transition: all 0.25s ease-in-out;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .feature-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(53, 200, 254, 0.15);
      border-color: #35c8fe;
    }
    .card-icon {
      font-size: 28px;
      margin-bottom: 14px;
      color: #35c8fe;
      font-weight: 800;
    }
    .card-title {
      font-size: 20px;
      font-weight: 700;
      color: #1a252f;
      margin-bottom: 8px;
    }
    .card-text {
      font-size: 14px;
      color: #7f8c8d;
      line-height: 1.6;
      margin-bottom: 20px;
    }
    .card-btn {
      display: inline-block;
      align-self: flex-start;
      font-size: 13px;
      font-weight: 600;
      color: #35c8fe;
      text-decoration: none;
      border-bottom: 2px solid #35c8fe;
      padding-bottom: 2px;
    }
    .card-btn:hover {
      color: #1b9cdb;
      border-color: #1b9cdb;
    }
    @media (max-width: 768px) {
      .card-grid {
        grid-template-columns: 1fr;
      }
      .hero-title {
        font-size: 24px;
      }
    }
  </style>
</head>
<body>
  <div class="hero-container">
    <div class="hero-title">영화와 함께하는 특별한 기록, CinemaBucketlist</div>
    <div class="hero-desc">
      23,000여 편의 영화 데이터베이스를 검색하고, 나만의 관람 일정과 감상 다이어리를 체계적으로 기록하세요.
    </div>

    <div class="card-grid">
      <div class="feature-card" onclick="top.location.href='/home/page_ChjU96'">
        <div>
          <div class="card-icon">🎬</div>
          <div class="card-title">Movies (영화 탐색)</div>
          <div class="card-text">
            제작연도, 평점, 장르, 국가별 상세 필터와 검색으로 원하는 영화를 찾고 평점과 메타크리틱 점수를 확인하세요.
          </div>
        </div>
        <span class="card-btn">영화 둘러보기 &rarr;</span>
      </div>

      <div class="feature-card" onclick="top.location.href='/home/page_KAjZ32'">
        <div>
          <div class="card-icon">📅</div>
          <div class="card-title">myDiary (관람 다이어리)</div>
          <div class="card-text">
            캘린더 스케줄러를 통해 관람 예정 및 감상 완료한 영화를 날짜별로 등록하고 나만의 아카이브를 만드세요.
          </div>
        </div>
        <span class="card-btn">다이어리 열기 &rarr;</span>
      </div>

      <div class="feature-card" onclick="top.location.href='/home/page_QATA26'">
        <div>
          <div class="card-icon">💡</div>
          <div class="card-title">What's? (서비스 소개)</div>
          <div class="card-text">
            CinemaBucketlist의 기획 의도와 버킷리스트 시스템의 철학, 아카이빙 방향을 소개합니다.
          </div>
        </div>
        <span class="card-btn">소개 보기 &rarr;</span>
      </div>

      <div class="feature-card" onclick="top.location.href='/home/page_zjlf30'">
        <div>
          <div class="card-icon">📖</div>
          <div class="card-title">HowTo? (이용 안내)</div>
          <div class="card-text">
            영화 검색, 다이어리 기록, 등급별 필터링 등 CinemaBucketlist의 주요 기능을 쉽게 사용하는 가이드입니다.
          </div>
        </div>
        <span class="card-btn">가이드 보기 &rarr;</span>
      </div>
    </div>
  </div>
</body>
</html>
