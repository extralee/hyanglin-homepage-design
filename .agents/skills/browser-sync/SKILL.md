---
name: browser-sync
description: >-
  사용자가 특정 HTML 파일(예: `prototype/index.html` 등)을 언급하며 "열어 줘", "브라우저로 열어 줘", "미리보기 띄워줘", "브라우저 싱크 실행해줘"라고 요청할 때 항상 활성화하여 Browser-sync 라이브 리로드 개발 서버를 구동합니다.
---

# Browser-sync HTML 실시간 미리보기 스킬 (`browser-sync`)

이 스킬은 사용자가 특정 `.html` 파일을 언급하며 **"열어 줘"**, **"브라우저로 열어 줘"**, **"실행해 줘"**, **"미리보기"** 등을 요청할 때, 해당 HTML 파일이 속한 디렉토리를 루트로 설정하여 Browser-sync 라이브 리로드(Live Reload) 개발 서버를 백그라운드로 실행하고 접속 경로를 안내합니다.

---

## 🎯 트리거 조건 (When to Use)

- 사용자가 `prototype/index.html` 등 특정 HTML 파일을 언급하며 **"열어 줘"**, **"실행해 줘"**, **"띄워 줘"**라고 요청할 때.
- 로컬 웹 페이지의 변경 사항을 실시간으로 반영하는 개발 서버(Browser-sync)가 필요할 때.
- 실행 중인 Browser-sync 서버를 종료하거나 재시작해야 할 때.

---

## 🚀 실행 절차 (Procedure)

### 1. 특정 HTML 파일 열기 및 서버 실행
사용자가 언급한 파일 경로를 첫 번째 인자로 전달하여 실행합니다:

```bash
# 특정 HTML 파일 경로 전달 (예: prototype/index.html)
./.agents/skills/browser-sync/scripts/start.sh prototype/index.html

# 특정 포트 지정 시 (두 번째 인자)
./.agents/skills/browser-sync/scripts/start.sh prototype/index.html 3000
```

> **에이전트 실행 요령**:
> AI 에이전트 도구 `run_command` 실행 시 `WaitMsBeforeAsync`를 3000에서 4000ms 수준으로 설정하여 초기 구동 로그를 수집한 후 백그라운드 태스크로 전환되도록 합니다.

### 2. 서버 중지 (Stop)
```bash
./.agents/skills/browser-sync/scripts/stop.sh
```

---

## 🛠️ 주요 동작 방식

1. **디렉토리 자동 감지**: 전달된 파일이 위치한 디렉토리를 웹 루트(`--server`)로 설정하고, 해당 파일명을 시작 페이지(`--startPath`)로 자동 지정합니다.
2. **핫 리로드 (Live Reload)**: 디렉토리 내의 모든 HTML/CSS/JS 파일 변경 시 브라우저가 즉각 자동 갱신됩니다.
3. **접속 안내**: 로컬 URL (`http://localhost:<포트>/<파일명>`) 및 관리 UI URL(`http://localhost:<포트+1>`)을 사용자에게 안내합니다.
