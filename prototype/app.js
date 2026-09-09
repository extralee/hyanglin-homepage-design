/**
 * HYANGLIN CHURCH MODERN MAIN PAGE PROTOTYPE INTERACTION SCRIPT
 */

const addDays = (date, days) => {
    const next = new Date(date);
    next.setDate(next.getDate() + days);
    return next;
};

const calculateEasterSunday = (year) => {
    const a = year % 19;
    const b = Math.floor(year / 100);
    const c = year % 100;
    const d = Math.floor(b / 4);
    const e = b % 4;
    const f = Math.floor((b + 8) / 25);
    const g = Math.floor((b - f + 1) / 3);
    const h = (19 * a + b - d - g + 15) % 30;
    const i = Math.floor(c / 4);
    const k = c % 4;
    const l = (32 + 2 * e + 2 * i - h - k) % 7;
    const m = Math.floor((a + 11 * h + 22 * l) / 451);
    const month = Math.floor((h + l - 7 * m + 114) / 31);
    const day = ((h + l - 7 * m + 114) % 31) + 1;
    return new Date(year, month - 1, day);
};

const calculateLiturgicalSeason = (today = new Date()) => {
    const year = today.getFullYear();
    const easterSunday = calculateEasterSunday(year);
    const ashWednesday = addDays(easterSunday, -46);
    const palmSunday = addDays(easterSunday, -7);
    const pentecostSunday = addDays(easterSunday, 49);
    const christmasDay = new Date(year, 11, 25);
    const adventStart = new Date(year, 10, 25);

    while (adventStart.getDay() !== 0) {
        adventStart.setDate(adventStart.getDate() - 1);
    }
    adventStart.setDate(adventStart.getDate() - 21);

    const trinitySunday = addDays(pentecostSunday, 7);
    const epiphany = new Date(year, 0, 6);

    if (today >= palmSunday && today < easterSunday) return 'red';
    if (today >= pentecostSunday && today < addDays(pentecostSunday, 1)) return 'red';
    if (today >= easterSunday && today < pentecostSunday) return 'white';
    if (today >= ashWednesday && today < easterSunday) return 'purple';
    if (today >= adventStart && today < christmasDay) return 'purple';
    if (today >= christmasDay && today <= addDays(christmasDay, 12)) return 'white';
    if (today >= epiphany && today < ashWednesday) return 'green';
    if (today >= trinitySunday && today < adventStart) return 'green';

    return 'green';
};

const LITURGICAL_META = {
    purple: { label: '대림절 / 사순절', icon: '✦', note: '기다림과 참회' },
    white: { label: '성탄절 / 부활절', icon: '✧', note: '기쁨과 빛' },
    green: { label: '주현절 / 창조절 / 평상주일', icon: '❀', note: '생명과 성장' },
    red: { label: '종려주일 / 성령강림절', icon: '✹', note: '성령과 증언' },
    black: { label: '감람절 / 장례절기', icon: '✧', note: '묵상과 고요' }
};

const HERO_IMAGES = ['main-image-1.jpg', 'main-image-2.jpg', 'main-image-3.jpg', 'main-image-4.jpg', 'main-image-5.jpg', 'main-image-6.jpg', 'main-image-7.jpg'];

let heroImageIndex = -1;

const getHeroImageUrl = (index) => `url("images/${HERO_IMAGES[index]}")`;

const pickRandomHeroImage = () => {
    const randomIndex = Math.floor(Math.random() * HERO_IMAGES.length);
    heroImageIndex = randomIndex;
    return randomIndex;
};

const applyHeroImage = (nextIndex = heroImageIndex) => {
    const safeIndex = Number.isInteger(nextIndex) ? nextIndex : Math.floor(Math.random() * HERO_IMAGES.length);
    const normalizedIndex = (safeIndex + HERO_IMAGES.length) % HERO_IMAGES.length;
    heroImageIndex = normalizedIndex;

    const heroImage = getHeroImageUrl(normalizedIndex);
    document.documentElement.style.setProperty('--hero-image-url', heroImage);
    const heroSection = document.querySelector('.hero-section');
    if (heroSection) {
        heroSection.style.setProperty('--hero-image-url', heroImage);
    }
};

const showHeroImage = (direction = 1) => {
    const nextIndex = heroImageIndex >= 0 ? heroImageIndex + direction : pickRandomHeroImage();
    applyHeroImage(nextIndex);
};

const isSundayWorshipLive = () => {
    // TEST MODE: 항상 LIVE 상태로 노출
    return true;
};

const updateLiveWorshipState = () => {
    const heroCard = document.querySelector('.hero-worship-card');
    if (!heroCard) return;

    if (isSundayWorshipLive()) {
        heroCard.classList.add('is-live');
        return;
    }

    heroCard.classList.remove('is-live');
};

const applyLiturgicalTheme = (forcedSeason = null) => {
    const season = forcedSeason || calculateLiturgicalSeason();
    document.documentElement.setAttribute('data-season', season);
    document.documentElement.style.setProperty('--theme-season', `'${season}'`);

    const label = document.getElementById('liturgicalSeasonText');
    const icon = document.getElementById('liturgicalSeasonIcon');
    if (label) {
        label.textContent = `${LITURGICAL_META[season].label || '평상주일'} · ${new Date().toLocaleDateString('ko-KR', { month: 'numeric', day: 'numeric' })}`;
    }
    if (icon) {
        icon.textContent = LITURGICAL_META[season].icon || '❀';
    }
};

document.addEventListener('DOMContentLoaded', () => {
    applyHeroImage(pickRandomHeroImage());
    updateLiveWorshipState();

    const prevButton = document.querySelector('.hero-image-nav-prev');
    const nextButton = document.querySelector('.hero-image-nav-next');

    if (prevButton) {
        prevButton.addEventListener('click', () => showHeroImage(-1));
    }

    if (nextButton) {
        nextButton.addEventListener('click', () => showHeroImage(1));
    }

    const seasonSelector = document.getElementById('seasonSelector');
    applyLiturgicalTheme();

    if (seasonSelector) {
        seasonSelector.addEventListener('change', (event) => {
            const value = event.target.value;
            if (value === 'auto') {
                applyLiturgicalTheme();
                return;
            }
            applyLiturgicalTheme(value);
        });
    }

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

    // 4. Mobile Bottom Bar "전체메뉴" Button Handler
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
