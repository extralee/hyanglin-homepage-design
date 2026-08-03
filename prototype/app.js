/**
 * HYANGLIN CHURCH MODERN MAIN PAGE PROTOTYPE INTERACTION SCRIPT
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Drawer Navigation Toggle
    const mobileToggle = document.getElementById('mobileToggle');
    const drawerClose = document.getElementById('drawerClose');
    const mobileDrawer = document.getElementById('mobileDrawer');

    if (mobileToggle && mobileDrawer) {
        mobileToggle.addEventListener('click', () => {
            mobileDrawer.classList.add('open');
        });
    }

    if (drawerClose && mobileDrawer) {
        drawerClose.addEventListener('click', () => {
            mobileDrawer.classList.remove('open');
        });
    }

    // 2. Mobile Accordion Menu (Level 1 to Level 2)
    const accordionBtns = document.querySelectorAll('.accordion-btn');

    accordionBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const item = btn.parentElement;
            const isOpen = item.classList.contains('active');

            // Close all items first
            document.querySelectorAll('.accordion-item').forEach(el => {
                el.classList.remove('active');
                const icon = el.querySelector('.accordion-btn i');
                if (icon) {
                    icon.className = 'fa-solid fa-plus';
                }
            });

            // Toggle clicked item
            if (!isOpen) {
                item.classList.add('active');
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.className = 'fa-solid fa-minus';
                }
            }
        });
    });

    // 3. Timeline Tabs Interaction
    const tabBtns = document.querySelectorAll('.tab-btn');
    const timelineCards = document.querySelectorAll('.timeline-card');

    tabBtns.forEach(tab => {
        tab.addEventListener('click', () => {
            tabBtns.forEach(b => b.classList.remove('active'));
            tab.classList.add('active');

            // Simple tab switch effect
            timelineCards.forEach(card => {
                card.style.opacity = '0.3';
                setTimeout(() => {
                    card.style.opacity = '1';
                }, 150);
            });
        });
    });

    // 4. Dark Mode Theme Switching System
    const themeToggle = document.getElementById('themeToggle');
    const drawerThemeToggle = document.getElementById('drawerThemeToggle');
    const savedTheme = localStorage.getItem('hyanglin-theme');

    const updateThemeIcon = (theme) => {
        const isDark = theme === 'dark';
        const iconClass = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        
        [themeToggle, drawerThemeToggle].forEach(btn => {
            if (btn) {
                const icon = btn.querySelector('i');
                if (icon) icon.className = iconClass;
            }
        });
    };

    const setTheme = (theme) => {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('hyanglin-theme', theme);
        updateThemeIcon(theme);
    };

    // Apply saved theme or system preference
    if (savedTheme) {
        setTheme(savedTheme);
    } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        setTheme('dark');
    }

    const toggleTheme = () => {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        setTheme(newTheme);
    };

    if (themeToggle) themeToggle.addEventListener('click', toggleTheme);
    if (drawerThemeToggle) drawerThemeToggle.addEventListener('click', toggleTheme);

    // 5. Mobile Bottom Bar "전체메뉴" Button Handler
    const bottomMenuToggle = document.getElementById('bottomMenuToggle');
    if (bottomMenuToggle && mobileDrawer) {
        bottomMenuToggle.addEventListener('click', () => {
            mobileDrawer.classList.add('open');
        });
    }

    // 6. Video Mockup Interaction
    const playBtn = document.querySelector('.play-btn');
    if (playBtn) {
        playBtn.addEventListener('click', () => {
            alert('🎥 최근 하늘뜻(설교) 동영상 플레이어 목업입니다.\n실제 구현 시 유튜브 API 및 HTML5 비디오 플레이어로 재생됩니다.');
        });
    }
});
