import json
import re
from collections import Counter
import os

input_file = '/home/hyuk/nvme_data/prj/hyanglin-legacy/tasks/task001/output.jsonl'
output_image = '/home/hyuk/nvme_data/prj/hyanglin-legacy/tasks/task001/output/wordcloud.png'

print("데이터 읽는 중...")
text_data = []
with open(input_file, 'r', encoding='utf-8') as f:
    for line in f:
        data = json.loads(line)
        text_data.append(data.get('content', '') + ' ' + data.get('title', ''))

full_text = " ".join(text_data)

print("텍스트 정제 중...")
# URL 제거, 이미지 마크다운 제거
full_text = re.sub(r'http[s]?://\S+', '', full_text)
full_text = re.sub(r'!\[.*?\]\(.*?\)', '', full_text)
# 한글과 영문, 공백만 남기기
full_text = re.sub(r'[^가-힣a-zA-Z\s]', ' ', full_text)

words = full_text.split()
# 의미없는 조사나 짧은 단어 제외 (강화된 불용어 사전)
stop_words = set()
stopwords_path = os.path.join(os.path.dirname(__file__), 'stopwords.txt')
if os.path.exists(stopwords_path):
    with open(stopwords_path, 'r', encoding='utf-8') as f:
        stop_words = set([line.strip().lower() for line in f if line.strip()])
else:
    stop_words = set(['향린', '교회', '향린교회', '때문'])
filtered_words = [w.lower() for w in words if len(w) >= 2 and w.lower() not in stop_words]
word_counts = Counter(filtered_words)

top_words = word_counts.most_common(200)

try:
    from wordcloud import WordCloud
    import matplotlib.pyplot as plt
    
    # 폰트 경로 (Ubuntu 나눔고딕)
    font_path = '/usr/share/fonts/truetype/nanum/NanumGothic.ttf'
    if not os.path.exists(font_path):
        font_path = '/usr/share/fonts/truetype/fonts-nanum/NanumGothic.ttf'
        if not os.path.exists(font_path):
            print("경고: 나눔고딕 폰트를 찾을 수 없어 기본 폰트를 사용합니다. 한글이 깨질 수 있습니다.")
            font_path = None
            
    import numpy as np
    
    # 1200x1200 크기의 둥근 원형 마스크 생성 (기존 대비 1.5배 확대)
    x, y = np.ogrid[:1200, :1200]
    mask = (x - 600) ** 2 + (y - 600) ** 2 > 570 ** 2
    mask = 255 * mask.astype(int)

    wc = WordCloud(
        font_path=font_path, 
        width=1200,
        height=1200,
        background_color='white', 
        colormap='Spectral',      # 더 다양하고 미적인 색상 스펙트럼
        mask=mask,                # 둥근 형태 강제 적용
        contour_width=3,          # 원형 테두리 선 굵기도 비례해서 증가
        contour_color='#f0f0f0',  # 테두리 색상 (연한 회색)
        prefer_horizontal=0.9,    # 가로 글씨 비율을 90%로 올려 가독성 극대화
        max_words=200,            # 200개 유지
        relative_scaling=0.5      # 글자 크기 스케일링 조절하여 단어 간 공간 확보
    )
    wc.generate_from_frequencies(dict(top_words))
    
    wc.to_file(output_image)
    print(f"✅ 워드클라우드 이미지 생성 성공: {output_image}")
except ImportError as e:
    print(f"ImportError: {e}. 라이브러리가 없습니다.")
    for word, count in top_words[:20]:
        print(f"{word}: {count}")
