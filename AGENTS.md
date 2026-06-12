<!-- BEGIN:skills-inventory -->
# 스킬 인벤토리 (Skills Inventory)

이 프로젝트에는 다음 스킬이 설치되어 있습니다. 스킬 파일 경로는 `.agents/skills/` 하위에 위치합니다.

| 스킬 | 용도 | Antigravity |
|---|---|---|
| `legacy-docker-migration` | 지원이 종료된(EOL) 레거시 웹 서비스/홈페이지를 Docker 환경으로 안전하게 마이그레이션하고 격리·보안화하는 베스트 프랙티스 가이드 | 자동 로딩 |
| `migration-verification-expert` | 이전(Migration) 작업 시 스크린샷 등 표면적인 결과에 의존하지 않고, 실제 서버 환경을 기반으로 철저하게 검증(채점)하는 전문가 스킬 | 자동 로딩 |
<!-- END:skills-inventory -->

<!-- BEGIN:nextjs-agent-rules -->
# This is NOT the Next.js you know

This version has breaking changes — APIs, conventions, and file structure may all differ from your training data. Read the relevant guide in `node_modules/next/dist/docs/` before writing any code. Heed deprecation notices.
<!-- END:nextjs-agent-rules -->

<!-- BEGIN:anti-strikethrough-rules -->
# Global Rule: Anti-Strikethrough (마크다운 물결표 제약)

## 🎯 목표
옵시디언(Obsidian) 및 기타 마크다운 렌더러에서 두 개의 물결표(`~`)가 등장할 때 그 사이의 텍스트가 의도치 않게 **취소선(Strikethrough)**으로 처리되는 렌더링 버그를 방지합니다.

## ⚠️ 제약 사항 (Constraints)
1. **숫자 범위(Range) 표현 시 `~` 사용 금지:**
   - ❌ 잘못된 예: `20~24px`, `40~45자`
   - ✅ 올바른 예: `20-24px`, `20에서 24px`, `40에서 45자`
2. **부득이한 사용 시 이스케이프 처리:**
   - 반드시 물결표 기호 자체를 출력해야 한다면 백슬래시(`\`)를 사용하여 이스케이프(`\~`) 처리하거나 양옆에 공백(` ~ `)을 두십시오.

## 🤖 AI 행동 지침
모든 AI 봇/에이전트는 사용자에게 답변을 생성하거나 마크다운 문서(`.md`)를 작성할 때, 위 제약 사항을 기본 포맷팅 규칙으로 항상 적용해야 합니다.
<!-- END:anti-strikethrough-rules -->
