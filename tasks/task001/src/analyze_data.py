import json
import re
from collections import Counter, defaultdict
import os
try:
    from konlpy.tag import Okt
except ImportError:
    pass

input_file = '/home/hyuk/nvme_data/prj/hyanglin-legacy/tasks/task001/output.jsonl'
output_report = '/home/hyuk/nvme_data/prj/hyanglin-legacy/tasks/task001/output/analysis_report.md'

stop_words = set()
stopwords_path = os.path.join(os.path.dirname(__file__), 'stopwords.txt')
if os.path.exists(stopwords_path):
    with open(stopwords_path, 'r', encoding='utf-8') as f:
        stop_words = set([line.strip() for line in f if line.strip()])
else:
    stop_words = set(['향린', '교회', '향린교회', '때문'])

def clean_text(text):
    text = re.sub(r'http[s]?://\S+', '', text)
    text = re.sub(r'!\[.*?\]\(.*?\)', '', text)
    text = re.sub(r'[^가-힣a-zA-Z\s]', ' ', text)
    return text

def clean_snippet(text, length=300):
    text = re.sub(r'!\[.*?\]\(.*?\)', '', text)
    text = re.sub(r'\[.*?\]\(.*?\)', '', text)
    text = re.sub(r'http[s]?://\S+', '', text)
    text = re.sub(r'\s+', ' ', text).strip()
    return text[:length] + "..." if len(text) > length else text

def clean_title(title):
    title = re.sub(r'&nbsp;', ' ', title)
    title = re.sub(r'\s+', ' ', title).strip()
    if not title or title == '/' or title.startswith('향린교회('):
        return "(제목 없음 또는 마크다운 손상)"
    return title

posts_by_year = defaultdict(list)

# 1. 데이터 로드 및 연도별 그룹화
with open(input_file, 'r', encoding='utf-8') as f:
    for line in f:
        try:
            data = json.loads(line)
            date_str = data.get('date', '')
            if len(date_str) >= 4:
                year = date_str[:4]
                posts_by_year[year].append(data)
        except Exception as e:
            continue

with open(output_report, 'w', encoding='utf-8') as out:
    out.write("# 향린교회 레거시 데이터 다각도 분석 리포트 (전체 데이터)\n\n")
    out.write("> 이 리포트는 3가지 페르소나(본질 탐구자, 반대론자, 단순화자)의 관점을 코드로 구현하여 자동 생성된 결과물입니다.\n\n")
    
    out.write("### 📊 [분석 기준 안내]\n")
    out.write("- **시대의 키워드 (시계열 트렌드)**: 형태소 분석기(KoNLPy)를 통해 추출된 순수 명사 중, 불용어를 제외하고 해당 연도에 가장 많이 사용된 단어의 단순 '등장 횟수(Frequency)'를 집계했습니다.\n")
    out.write("- **가장 뜨거웠던 사건 (감성 분석)**: 이미지 태그를 제외한 순수 텍스트를 바탕으로 감성 점수(`느낌표(!)×5점 + 물음표(?)×3점 + 글자수×0.01점`)를 매겨, 호소력과 감정적 깊이가 있는 글을 선별했습니다.\n")
    out.write("- **10대 뉴스 (파급력 분석)**: 해당 연도의 글 중 `조회수 + (추천수×20) + (댓글수×10)`의 공식으로 '파급력 점수(Impact Score)'를 계산하여, 교우들의 실제 관심도가 가장 높았던 이슈를 도출했습니다.\n\n")
    out.write("---\n\n")
    
    # 연도별 정렬 (오래된 순)
    years = sorted(posts_by_year.keys())
    
    for year in years:
        posts = posts_by_year[year]
        out.write(f"## 📅 {year}년 (총 {len(posts)}건의 기록)\n\n")
        
        # --- 1. 시계열 키워드 (Ontologist) ---
        year_text = " ".join([p.get('title', '') + " " + p.get('content', '') for p in posts])
        cleaned_text = clean_text(year_text)
        
        try:
            okt = Okt()
            nouns = okt.nouns(cleaned_text)
            words = [w for w in nouns if len(w) >= 2 and w not in stop_words]
        except NameError:
            # konlpy가 설치되지 않은 경우 기존 로직 Fallback
            words = [w for w in cleaned_text.split() if len(w) >= 2 and w not in stop_words]
            
        top_keywords = Counter(words).most_common(20)
        
        out.write("### 🔍 1. 시대의 키워드 (시계열 트렌드)\n")
        if top_keywords:
            kw_str = ", ".join([f"**{w}**({c}회)" for w, c in top_keywords])
            out.write(f"- {kw_str}\n\n")
        else:
            out.write("- 키워드 추출 불가\n\n")
        
        # --- 2. 핫이슈 / 격정적 사건 (Contrarian) ---
        # 감정 점수: 마크다운 이미지(![])를 제외한 순수 느낌표(!), 물음표(?) 개수와 텍스트 길이를 가중합산
        def emotional_score(p):
            content = p.get('content', '')
            # 마크다운 이미지 문법 제거
            content_no_img = re.sub(r'!\[.*?\]\(.*?\)', '', content)
            return content_no_img.count('!') * 5 + content_no_img.count('?') * 3 + len(content_no_img) * 0.01

        hottest_posts = sorted(posts, key=emotional_score, reverse=True)[:5]
        
        out.write("### 🔥 2. 가장 뜨거웠던 사건 상위 5개 (감성/이슈 분석)\n")
        for idx, p in enumerate(hottest_posts, 1):
            title = clean_title(p.get('title', '제목 없음'))
            out.write(f"{idx}. **제목**: {title} (작성자: {p.get('author', '알수없음')})\n")
            snippet = clean_snippet(p.get('content', ''), 300)
            out.write(f"   - **상세 내용 요약**: \"{snippet}\"\n")
        out.write("\n")
        
        # --- 3. 타임라인 / 10대 뉴스 (Simplifier) ---
        # 조회수, 추천수, 댓글수를 조합한 '파급력 점수(Impact Score)'로 상위 10개 글 추출
        def news_impact_score(p):
            readed = int(p.get('readed_count', 0))
            voted = int(p.get('voted_count', 0))
            comment = int(p.get('comment_count', 0))
            return readed + (voted * 20) + (comment * 10)

        top_10_news = sorted(posts, key=news_impact_score, reverse=True)[:10]
        
        out.write("### 📰 3. 올 해의 10대 뉴스 (자동 추출 헤드라인)\n")
        for idx, news in enumerate(top_10_news, 1):
            title = clean_title(news.get('title', '제목 없음'))
            snippet = clean_snippet(news.get('content', ''), 100)
            out.write(f"{idx}. **{title}**\n   - {snippet}\n")
            
        out.write("\n---\n\n")

print(f"분석 완료! 리포트 생성됨: {output_report}")
