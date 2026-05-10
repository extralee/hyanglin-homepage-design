import json
import re
import os
try:
    from konlpy.tag import Okt
except ImportError:
    pass

input_file = '/home/hyuk/nvme_data/prj/hyanglin-legacy/tasks/task001/output.jsonl'
output_processed = '/home/hyuk/nvme_data/prj/hyanglin-legacy/tasks/task001/output_processed.jsonl'
stopwords_path = os.path.join(os.path.dirname(__file__), 'stopwords.txt')
synonyms_path = os.path.join(os.path.dirname(__file__), 'synonyms.txt')

# 불용어 로드
stop_words = set()
if os.path.exists(stopwords_path):
    with open(stopwords_path, 'r', encoding='utf-8') as f:
        stop_words = set([line.strip().lower() for line in f if line.strip() and not line.startswith('#')])

# 동의어 사전 로드
synonyms = {}
if os.path.exists(synonyms_path):
    with open(synonyms_path, 'r', encoding='utf-8') as f:
        for line in f:
            if line.strip() and not line.startswith('#'):
                parts = [p.strip() for p in line.split(',')]
                if len(parts) >= 2:
                    target = parts[0]
                    for source in parts[1:]:
                        synonyms[source] = target

def clean_text(text):
    text = re.sub(r'http[s]?://\S+', '', text)
    text = re.sub(r'!\[.*?\]\(.*?\)', '', text)
    text = re.sub(r'[^가-힣a-zA-Z\s]', ' ', text)
    return text

okt = Okt()

print("텍스트 전처리를 시작합니다. 전체 데이터를 한 번만 분석합니다...")

count = 0
with open(input_file, 'r', encoding='utf-8') as f_in, open(output_processed, 'w', encoding='utf-8') as f_out:
    for line in f_in:
        try:
            data = json.loads(line)
            content = clean_text(data.get('title', '') + " " + data.get('content', ''))
            nouns = okt.nouns(content)
            
            words = []
            for w in nouns:
                w = w.lower()
                if len(w) >= 2 and w not in stop_words:
                    w = synonyms.get(w, w)
                    words.append(w)
                    
            data['extracted_words'] = words
            f_out.write(json.dumps(data, ensure_ascii=False) + '\n')
            
            count += 1
            if count % 1000 == 0:
                print(f"{count}개 게시글 처리 완료...")
        except Exception:
            pass

print(f"전처리 완벽 종료! 총 {count}개의 데이터가 {output_processed} 에 저장되었습니다.")
