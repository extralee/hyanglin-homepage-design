import json
import re
import os
import pandas as pd
import plotly.express as px
from collections import Counter, defaultdict
from konlpy.tag import Okt

input_file = '/home/hyuk/nvme_data/prj/hyanglin-legacy/tasks/task001/output.jsonl'
output_html = '/home/hyuk/nvme_data/prj/hyanglin-legacy/tasks/task001/output/gapminder_keywords.html'
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

# 2. 데이터 처리 및 토큰화
okt = Okt()
posts_by_year = defaultdict(list)
overall_counter = Counter()

with open(input_file, 'r', encoding='utf-8') as f:
    for line in f:
        try:
            data = json.loads(line)
            date_str = data.get('date', '')
            if len(date_str) >= 4:
                year = int(date_str[:4])
                if year < 2017: continue # 너무 오래되거나 파편화된 데이터 제외
                
                # 형태소 분석 및 단어 세트 구성
                content = clean_text(data.get('title', '') + " " + data.get('content', ''))
                nouns = okt.nouns(content)
                words = [w for w in nouns if len(w) >= 2 and w not in stop_words]
                
                data['extracted_words'] = words
                posts_by_year[year].append(data)
                overall_counter.update(words)
        except Exception:
            continue

# 가장 많이 등장한 상위 30개 키워드 선정 (버블 개수)
top_keywords = [w for w, c in overall_counter.most_common(30)]

years = sorted(posts_by_year.keys())
records = []

for year in years:
    for keyword in top_keywords:
        frequency = 0  # 해당 연도 총 등장 횟수
        impact = 0     # 해당 연도 이 단어가 포함된 글의 총 조회수
        reach = 0      # 해당 연도 이 단어가 포함된 글의 개수
        
        for post in posts_by_year[year]:
            count_in_post = post['extracted_words'].count(keyword)
            if count_in_post > 0:
                frequency += count_in_post
                reach += 1
                impact += int(post.get('readed_count', 0))
        
        records.append({
            'Year': year,
            'Keyword': keyword,
            'Frequency': frequency,
            'Impact (Total Views)': impact,
            'Reach (Post Count)': reach if reach > 0 else 0.1 # 크기가 0이면 안보이므로 최소값 설정
        })

df = pd.DataFrame(records)

# 빈도가 0인 경우 시각화에서 뭉치지 않도록 조정
df['Frequency'] = df['Frequency'].apply(lambda x: x if x > 0 else 0.1)

# 3. Gapminder 스타일 버블 차트 생성 (Plotly)
fig = px.scatter(
    df, 
    x="Frequency", 
    y="Impact (Total Views)", 
    animation_frame="Year", 
    animation_group="Keyword",
    size="Reach (Post Count)", 
    color="Keyword", 
    hover_name="Keyword",
    text="Keyword",
    log_x=True, 
    size_max=60,
    range_x=[0.5, df['Frequency'].max() * 1.5],
    range_y=[-df['Impact (Total Views)'].max()*0.05, df['Impact (Total Views)'].max() * 1.1],
    title="향린교회 핵심 키워드 다이내믹 트렌드 (Gapminder Style)",
    labels={"Frequency": "단어 등장 횟수 (Frequency)", "Impact (Total Views)": "단어 포함 게시글 총 조회수 (Impact)"}
)

fig.update_traces(textposition='top center')
fig.update_layout(showlegend=False) # 텍스트 라벨이 있으므로 레전드는 숨김

# 초기 속도 설정 (기본값: 7000ms)
if len(fig.layout.updatemenus) > 0:
    fig.layout.updatemenus[0].buttons[0].args = [
        None,
        {"frame": {"duration": 7000, "redraw": True},
         "fromcurrent": True,
         "transition": {"duration": 6500, "easing": "quadratic-in-out"}}
    ]

# HTML 추출 및 Custom UI 주입
html_content = fig.to_html(full_html=True, include_plotlyjs='cdn')

custom_js = """
<div style="position: absolute; top: 20px; left: 20px; z-index: 1000; background: rgba(255, 255, 255, 0.9); padding: 15px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: 1px solid #ddd; font-family: sans-serif;">
    <label for="speed-slider" style="font-weight: bold; font-size: 14px;">🐢 느리게 &nbsp; <input type="range" id="speed-slider" min="200" max="7000" step="100" value="200" style="width: 200px; vertical-align: middle;"> &nbsp; 🐇 빠르게</label>
    <div style="text-align: center; margin-top: 5px; font-size: 12px; color: #555;">현재 속도: <span id="speed-val">7.0</span>초 / 프레임</div>
</div>
<script>
    var slider = document.getElementById('speed-slider');
    
    function applySpeed(sliderValue) {
        var duration = 7200 - sliderValue; 
        document.getElementById('speed-val').innerText = (duration / 1000).toFixed(1);
        
        var graphDivs = document.getElementsByClassName('plotly-graph-div');
        if (graphDivs.length > 0) {
            var gd = graphDivs[0];
            if (gd.layout && gd.layout.updatemenus && gd.layout.updatemenus.length > 0) {
                var buttons = gd.layout.updatemenus[0].buttons;
                if (buttons && buttons.length > 0 && buttons[0].args && buttons[0].args.length > 1) {
                    buttons[0].args[1].frame.duration = duration;
                    buttons[0].args[1].transition.duration = duration * 0.7;
                    return true; // 성공적으로 적용됨
                }
            }
        }
        return false;
    }

    slider.addEventListener('input', function(e) {
        applySpeed(parseInt(e.target.value));
    });

    // 브라우저 로드 직후 Plotly가 렌더링될 때까지 기다렸다가 강제 초기화 적용
    var initInterval = setInterval(function() {
        if (applySpeed(parseInt(slider.value))) {
            clearInterval(initInterval);
        }
    }, 200);
</script>
"""

html_content = html_content.replace('</body>', custom_js + '</body>')

with open(output_html, 'w', encoding='utf-8') as f:
    f.write(html_content)

print(f"✅ 속도 조절 슬라이더가 추가된 버블 차트가 생성되었습니다: {output_html}")
