import json
import pandas as pd
import plotly.express as px
from collections import defaultdict

input_file = '/home/hyuk/nvme_data/prj/hyanglin-legacy/tasks/task001/output.jsonl'
output_html = '/home/hyuk/nvme_data/prj/hyanglin-legacy/tasks/task001/output/traffic_bubble_chart.html'

# 연도별 데이터 집계
stats_by_year = {}

with open(input_file, 'r', encoding='utf-8') as f:
    for line in f:
        try:
            data = json.loads(line)
            date_str = data.get('date', '')
            if len(date_str) >= 4:
                year = int(date_str[:4])
                if year < 2017: continue # 2017년 이전 데이터는 너무 적어 제외
                
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

# DataFrame으로 변환
records = []
for year, stats in sorted(stats_by_year.items()):
    records.append({
        '연도 (Year)': str(year),
        '누적 조회수 (Total Views)': stats['total_views'],
        '작성된 게시글 수 (Total Posts)': stats['total_posts'],
        '총 댓글 수 (Total Comments)': stats['total_comments'],
        '활동 유저 수 (Active Users)': len(stats['unique_authors'])
    })

df = pd.DataFrame(records)

# 버블 차트 생성 (정적 버블 차트 - 연도별로 하나의 버블 표시)
fig = px.scatter(
    df, 
    x="연도 (Year)", 
    y="누적 조회수 (Total Views)", 
    size="활동 유저 수 (Active Users)", 
    color="작성된 게시글 수 (Total Posts)",
    hover_name="연도 (Year)",
    hover_data=["총 댓글 수 (Total Comments)", "활동 유저 수 (Active Users)", "작성된 게시글 수 (Total Posts)"],
    size_max=50,
    color_continuous_scale=px.colors.sequential.Viridis,
    title="향린교회 연도별 트래픽 및 커뮤니티 건강도 (Bubble Chart)"
)

fig.update_layout(
    xaxis_title="연도 (Year)",
    yaxis_title="게시판 누적 조회수 (Total Views)",
    coloraxis_colorbar=dict(title="게시글 수"),
    font=dict(family="sans-serif", size=14)
)

fig.write_html(output_html)
print(f"✅ 연도별 트래픽 버블 차트가 생성되었습니다: {output_html}")
