---
name: darkmode
description: 웹 애플리케이션 및 CSS 개발 시 다크 모드(Dark Mode) 테마 시스템 구축, 다크 모드 고대비 가독성 보장(Contrast Invariance), localStorage 연동 및 모바일 테마 토글 수칙 지침
---

# 다크 모드 테마 구축 및 가독성 전문가 스킬 (`darkmode`)

이 스킬은 웹 애플리케이션 개발 시 완성도 높은 **다크 모드(Dark Mode) 테마 시스템**을 구축하고, 다크 모드 환경에서 시각적 가독성 저하를 방지하기 위한 핵심 개발 수칙과 모범 사례(Best Practices)를 제공합니다.

---

## 🎯 1. 핵심 원칙: 다크 모드 가독성 보장 (Dark Mode Contrast Invariance)

다크 모드를 구현할 때 가장 빈번하게 발생하는 버그는 **라이트 모드의 어두운 브랜드 주 색상(Primary Color)이 다크 모드 텍스트/타이틀에 그대로 남아 배경과 겹쳐서 글씨가 안 보이는 현상**입니다.

### ⛔ 흔한 실수 및 문제점
- 라이트 모드: 배경 `#FBFBFA` (밝음) + 타이틀 `.section-title { color: #1E3F35; }` (어두운 그린) ➔ **선명함**
- 다크 모드: 배경 `#0C1412` (어두움) + 타이틀 `.section-title` 색상 미지정 ➔ **어두운 배경 + 어두운 텍스트 = 가독성 상실!**

### ✅ 해결 가이드라인 (Mandatory Override)
다크 모드(`[data-theme="dark"]` 또는 `.dark-mode`) 적용 시 다음 텍스트 및 시각 요소들은 **반드시 밝은 고대비 색상으로 일괄 오버라이드**해야 합니다.

1. **메인 타이틀 (`.section-title`, `h1`, `h2`)**: 순백색 (`#FFFFFF`) 또는 밝은 크림색.
2. **서브 타이틀 & 카테고리 태그 (`.section-subtitle`, `.card-category`)**: 엠버 골드 (`#E9C46A`) 또는 밝은 옐로우/그린.
3. **본문 및 카드 제목 (`.card-body h3`, `h4`, `.news-info h4`)**: 소프트 라이트 (`#F0F4F2`) 텍스트.
4. **링크 및 텍스트 버튼 (`.media-link`, `.btn-text-link`)**: 골드/샌드 고대비 색상.
5. **뱃지 및 인용문 (`.bg-green`, `.prayer-quote`)**: 반투명 어두운 태그 배경 + 밝은 아치형 텍스트.

---

## 🎨 2. CSS 변수(Custom Properties) 구조 설계 패턴

라이트 모드와 다크 모드 색상을 CSS 변수로 명확히 분리하여 관리합니다.

```css
/* 1. Light Mode Tokens (Default) */
:root {
    --bg-main: #FBFBFA;
    --surface: #FFFFFF;
    --surface-alt: #F4F6F5;
    --text-main: #2B2D42;
    --text-muted: #6C757D;
    --primary: #1E3F35;
    --secondary: #D4A373;
    --accent: #E9C46A;
    --border-color: #E2E8F0;
}

/* 2. Dark Mode Tokens Override */
[data-theme="dark"] {
    --bg-main: #0C1412;         /* Deep Forest Night Dark */
    --surface: #16221F;         /* Dark Surface Container */
    --surface-alt: #1D2B27;     /* Secondary Dark Surface */
    --text-main: #F0F4F2;       /* Soft Light Text */
    --text-muted: #94A3B8;      /* Muted Slate Text */
    --border-color: rgba(255, 255, 255, 0.12);
}

/* 3. Typography Dark Overrides (Contrast Fix) */
[data-theme="dark"] .section-title {
    color: #FFFFFF !important;
}

[data-theme="dark"] .section-subtitle,
[data-theme="dark"] .card-category,
[data-theme="dark"] .media-link {
    color: var(--accent);
}

[data-theme="dark"] .card-body h3,
[data-theme="dark"] .news-info h4,
[data-theme="dark"] .timeline-card h3 {
    color: var(--text-main);
}
```

---

## ⚙️ 3. JS 테마 스위칭 & Persistence 로직

사용자가 선택한 테마를 `localStorage`에 보존하고, 시스템(`prefers-color-scheme`) 설정을 자동 감지합니다.

```javascript
document.addEventListener('DOMContentLoaded', () => {
    const themeToggleBtns = document.querySelectorAll('.theme-toggle-btn');
    const savedTheme = localStorage.getItem('app-theme');

    const updateThemeIcon = (theme) => {
        const isDark = theme === 'dark';
        themeToggleBtns.forEach(btn => {
            const icon = btn.querySelector('i');
            if (icon) icon.className = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        });
    };

    const setTheme = (theme) => {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('app-theme', theme);
        updateThemeIcon(theme);
    };

    // 1. Initial Load: Check localStorage or System Preference
    if (savedTheme) {
        setTheme(savedTheme);
    } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        setTheme('dark');
    }

    // 2. Toggle Handler
    const toggleTheme = () => {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        setTheme(currentTheme === 'dark' ? 'light' : 'dark');
    };

    themeToggleBtns.forEach(btn => btn.addEventListener('click', toggleTheme));
});
```

---

## 📱 4. UI/UX 체크리스트

- [ ] 모바일 및 데스크톱 헤더에 원터치 테마 토글 버튼 배치 여부
- [ ] 다크 모드 전환 시 이미지 썸네일/비디오 위에 과도하게 어두운 오버레이가 중첩되지 않는지 확인
- [ ] 폼 입력창(Input), 버튼(Button), 아코디언, 드롭다운 메뉴의 다크 모드 텍스트 및 테두리 대비 확인
- [ ] `localStorage`를 통한 세션 간 테마 보존 테스트 완료
