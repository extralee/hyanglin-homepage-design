import json
import os
import pandas as pd
import plotly.express as px
from collections import defaultdict

input_file = '/home/hyuk/nvme_data/prj/hyanglin-legacy/tasks/task001/output.jsonl'
output_html = '/home/hyuk/nvme_data/prj/hyanglin-legacy/tasks/task001/category_motion_chart.html'

# 실제 게시판 이름 매핑 로드
modules_path = os.path.join(os.path.dirname(__file__), '../modules.json')
module_map = {}
if os.path.exists(modules_path):
    with open(modules_path, 'r', encoding='utf-8') as f:
        module_map = json.load(f)

# 연도별, 카테고리별 데이터 집계
# 구조: stats_by_year_category[year][category_id] = { ... }
stats_by_year_category = defaultdict(lambda: defaultdict(lambda: {
    'total_views': 0,
    'total_posts': 0,
    'total_comments': 0,
    'unique_authors': set()
}))

with open(input_file, 'r', encoding='utf-8') as f:
    for line in f:
        try:
            data = json.loads(line)
            date_str = data.get('date', '')
            if len(date_str) >= 4:
                year = int(date_str[:4])
                if year < 2017: continue 
                
                cat_id = str(data.get('category_id', 'Unknown'))
                # 보기 좋게 보드 이름 포맷팅 (실제 이름 맵핑 적용)
                board_name = module_map.get(cat_id, f"게시판 {cat_id}")
                
                stats = stats_by_year_category[year][board_name]
                stats['total_views'] += int(data.get('readed_count', 0))
                stats['total_posts'] += 1
                stats['total_comments'] += int(data.get('comment_count', 0))
                
                author = data.get('author', '').strip()
                if author:
                    stats['unique_authors'].add(author)
        except Exception:
            continue

# 모든 카테고리가 모든 연도에 존재하도록(애니메이션 버그 방지) 빈 레코드 채우기
all_years = sorted(stats_by_year_category.keys())
all_categories = set()
for y in all_years:
    for c in stats_by_year_category[y].keys():
        all_categories.add(c)

records = []
for year in all_years:
    for cat in all_categories:
        if cat in stats_by_year_category[year]:
            stats = stats_by_year_category[year][cat]
            records.append({
                'Year': year,
                'Category': cat,
                'Total Views': stats['total_views'] if stats['total_views'] > 0 else 0.1, # log scale 대비
                'Total Posts': stats['total_posts'] if stats['total_posts'] > 0 else 0.1,
                'Active Users': len(stats['unique_authors']) if len(stats['unique_authors']) > 0 else 0.1
            })
        else:
            records.append({
                'Year': year,
                'Category': cat,
                'Total Views': 0.1,
                'Total Posts': 0.1,
                'Active Users': 0.1
            })

df = pd.DataFrame(records)

# 모션 버블 차트 생성
fig = px.scatter(
    df, 
    x="Total Posts", 
    y="Total Views", 
    animation_frame="Year", 
    animation_group="Category",
    size="Active Users", 
    color="Category", 
    hover_name="Category",
    text="Category",
    log_x=True, 
    log_y=True,
    size_max=80,
    range_x=[0.5, df['Total Posts'].max() * 2],
    range_y=[0.5, df['Total Views'].max() * 2],
    title="향린교회 게시판 카테고리별 다이내믹 트래픽 맵 (Gapminder Style)",
    labels={
        "Total Posts": "게시글 수 (Total Posts)", 
        "Total Views": "게시판 누적 조회수 (Total Views)",
        "Active Users": "활동 유저 수 (Active Users)"
    }
)

fig.update_traces(textposition='top center')
fig.update_layout(showlegend=False)

# 속도 조절 슬라이더 및 애니메이션 초기화 (7초 강제)
if len(fig.layout.updatemenus) > 0:
    fig.layout.updatemenus[0].buttons[0].args = [
        None,
        {"frame": {"duration": 7000, "redraw": True},
         "fromcurrent": True,
         "transition": {"duration": 6500, "easing": "quadratic-in-out"}}
    ]

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

print(f"✅ 카테고리별 모션 버블 차트 HTML이 생성되었습니다: {output_html}")
