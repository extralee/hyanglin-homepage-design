import json
import pandas as pd
import plotly.express as px
from collections import defaultdict

input_file = '/home/hyuk/nvme_data/prj/hyanglin-legacy/tasks/task001/output.jsonl'
output_html = '/home/hyuk/nvme_data/prj/hyanglin-legacy/tasks/task001/individual_traffic_charts.html'

stats_by_year = {}

with open(input_file, 'r', encoding='utf-8') as f:
    for line in f:
        try:
            data = json.loads(line)
            date_str = data.get('date', '')
            if len(date_str) >= 4:
                year = int(date_str[:4])
                if year < 2017: continue 
                
                if year not in stats_by_year:
                    stats_by_year[year] = {
                        'total_views': 0,
                        'total_posts': 0,
                        'total_comments': 0,
                        'unique_authors': set()
                    }
                
                stats = stats_by_year[year]
                stats['total_views'] += int(data.get('readed_count', 0))
                stats['total_posts'] += 1
                stats['total_comments'] += int(data.get('comment_count', 0))
                
                author = data.get('author', '').strip()
                if author:
                    stats['unique_authors'].add(author)
        except Exception:
            continue

records = []
for year, stats in sorted(stats_by_year.items()):
    records.append({
        'Year': str(year),
        'Views': stats['total_views'],
        'Posts': stats['total_posts'],
        'Comments': stats['total_comments'],
        'Users': len(stats['unique_authors'])
    })

df = pd.DataFrame(records)

# 4개의 개별 버블 차트 생성
charts = []

# 1. 누적 조회수
fig1 = px.scatter(df, x="Year", y="Views", size="Views", color="Views", size_max=50, title="1. 연도별 누적 조회수 (Total Views)", template="plotly_white")
charts.append(fig1.to_html(full_html=False, include_plotlyjs='cdn'))

# 2. 활동 유저 수
fig2 = px.scatter(df, x="Year", y="Users", size="Users", color="Users", size_max=50, color_continuous_scale="Plasma", title="2. 연도별 활동 유저 수 (Active Users)", template="plotly_white")
charts.append(fig2.to_html(full_html=False, include_plotlyjs=False))

# 3. 작성된 게시글 수
fig3 = px.scatter(df, x="Year", y="Posts", size="Posts", color="Posts", size_max=50, color_continuous_scale="Viridis", title="3. 연도별 작성된 게시글 수 (Total Posts)", template="plotly_white")
charts.append(fig3.to_html(full_html=False, include_plotlyjs=False))

# 4. 총 댓글 수
fig4 = px.scatter(df, x="Year", y="Comments", size="Comments", color="Comments", size_max=50, color_continuous_scale="Inferno", title="4. 연도별 총 댓글 수 (Total Comments)", template="plotly_white")
charts.append(fig4.to_html(full_html=False, include_plotlyjs=False))

# HTML 파일 하나로 결합
html_template = f"""
<!DOCTYPE html>
<html>
<head>
    <title>개별 지표 버블 차트</title>
    <style>
        body {{ font-family: sans-serif; padding: 20px; background-color: #f8f9fa; }}
        h1 {{ text-align: center; color: #333; }}
        .chart-container {{ background: white; margin-bottom: 30px; padding: 10px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }}
    </style>
</head>
<body>
    <h1>📊 향린교회 항목별 트래픽 버블 차트</h1>
    <div class="chart-container">{charts[0]}</div>
    <div class="chart-container">{charts[1]}</div>
    <div class="chart-container">{charts[2]}</div>
    <div class="chart-container">{charts[3]}</div>
</body>
</html>
"""

with open(output_html, 'w', encoding='utf-8') as f:
    f.write(html_template)

print(f"✅ 항목별 개별 버블 차트가 생성되었습니다: {output_html}")
