import json
import re
import os
from collections import Counter, defaultdict
import matplotlib.pyplot as plt
import matplotlib.font_manager as fm
from konlpy.tag import Okt

input_file = '/home/hyuk/nvme_data/prj/hyanglin-legacy/tasks/task001/output.jsonl'
output_image = '/home/hyuk/nvme_data/prj/hyanglin-legacy/tasks/task001/trend_chart.png'
stopwords_path = os.path.join(os.path.dirname(__file__), 'stopwords.txt')

# 1. 불용어 로드
stop_words = set()
if os.path.exists(stopwords_path):
    with open(stopwords_path, 'r', encoding='utf-8') as f:
        stop_words = set([line.strip() for line in f if line.strip()])

def clean_text(text):
    text = re.sub(r'http[s]?://\S+', '', text)
    text = re.sub(r'!\[.*?\]\(.*?\)', '', text)
    text = re.sub(r'[^가-힣a-zA-Z\s]', ' ', text)
    return text

# 2. 데이터 처리
posts_by_year = defaultdict(list)
with open(input_file, 'r', encoding='utf-8') as f:
    for line in f:
        try:
            data = json.loads(line)
            date_str = data.get('date', '')
            if len(date_str) >= 4:
                year = date_str[:4]
                # 2017년 등 데이터가 너무 적은 연도는 제외하거나 포함
                posts_by_year[year].append(data)
        except Exception:
            continue

years = sorted(posts_by_year.keys())
okt = Okt()

# 연도별 키워드 빈도 및 전체 키워드 빈도 계산
word_freq_by_year = {}
overall_counter = Counter()

for year in years:
    text = " ".join([p.get('title', '') + " " + p.get('content', '') for p in posts_by_year[year]])
    cleaned_text = clean_text(text)
    nouns = okt.nouns(cleaned_text)
    words = [w for w in nouns if len(w) >= 2 and w not in stop_words]
    
    year_counter = Counter(words)
    word_freq_by_year[year] = year_counter
    overall_counter.update(words)

# 가장 많이 등장한 상위 5개 키워드 선정
top_5_keywords = [w for w, c in overall_counter.most_common(5)]

# 3. 차트 그리기
font_path = '/usr/share/fonts/truetype/nanum/NanumGothic.ttf'
if os.path.exists(font_path):
    font_prop = fm.FontProperties(fname=font_path)
else:
    print("⚠️ 폰트를 찾을 수 없어 기본 폰트를 사용합니다.")
    font_prop = fm.FontProperties()

plt.figure(figsize=(12, 7))

# 각 키워드별로 연도별 트렌드 라인 그리기
for keyword in top_5_keywords:
    frequencies = [word_freq_by_year[year][keyword] for year in years]
    plt.plot(years, frequencies, marker='o', linewidth=2, label=keyword)

plt.title('시대별 핵심 키워드 트렌드 (Top 5)', fontproperties=font_prop, fontsize=18, pad=20)
plt.xlabel('연도', fontproperties=font_prop, fontsize=14)
plt.ylabel('등장 빈도', fontproperties=font_prop, fontsize=14)
plt.xticks(fontsize=12, fontproperties=font_prop)
plt.yticks(fontsize=12, fontproperties=font_prop)
plt.legend(prop=font_prop, loc='upper left')
plt.grid(True, linestyle='--', alpha=0.7)

# 여백 조정 및 저장
plt.tight_layout()
plt.savefig(output_image, dpi=300)
print(f"✅ 시계열 트렌드 차트가 생성되었습니다: {output_image}")
