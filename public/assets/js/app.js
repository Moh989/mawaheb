document.documentElement.classList.add('js');

const menuButton = document.querySelector('[data-menu-toggle]');
const nav = document.querySelector('[data-nav]');
const servicesDropdown = document.querySelector('[data-services-dropdown]');
const servicesToggle = document.querySelector('[data-services-toggle]');
const servicesMenu = document.querySelector('[data-services-menu]');
const language = document.querySelector('[data-language]');
const languageButton = document.querySelector('[data-language-button]');
const languageMenu = document.querySelector('[data-language-menu]');
const closeServices = () => { if (servicesDropdown) servicesDropdown.open = false; };
const closeLanguage = () => {
    if (!languageButton || !languageMenu) return;
    languageButton.setAttribute('aria-expanded', 'false');
    languageMenu.hidden = true;
};
const setNavigationOpen = open => {
    if (!menuButton || !nav) return;
    menuButton.setAttribute('aria-expanded', String(open));
    nav.classList.toggle('is-open', open);
    const label = menuButton.querySelector('[data-menu-label]');
    if (label) label.textContent = open ? label.dataset.close : label.dataset.open;
    if (!open) {
        closeServices();
        closeLanguage();
    }
};
menuButton?.addEventListener('click', () => setNavigationOpen(menuButton.getAttribute('aria-expanded') !== 'true'));

if (servicesDropdown && servicesToggle && servicesMenu) {
    const links = [...servicesMenu.querySelectorAll('a')];
    servicesDropdown.addEventListener('toggle', () => {
        if (servicesDropdown.open) closeLanguage();
    });
    servicesToggle.addEventListener('keydown', event => {
        if (!['ArrowDown', 'ArrowUp'].includes(event.key)) return;
        event.preventDefault();
        servicesDropdown.open = true;
        (event.key === 'ArrowDown' ? links[0] : links.at(-1))?.focus();
    });
    servicesMenu.addEventListener('keydown', event => {
        const index = links.indexOf(document.activeElement);
        if (index < 0 || !['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        const next = event.key === 'Home' ? 0 : event.key === 'End' ? links.length - 1 : (index + (event.key === 'ArrowDown' ? 1 : -1) + links.length) % links.length;
        links[next]?.focus();
    });
    servicesDropdown.addEventListener('focusout', event => {
        // WebKit can blur the summary without focusing a pointer-activated link.
        // A null target must not hide that link before pointerup/click reaches it.
        // Outside clicks and keyboard departures are handled separately.
        if (event.relatedTarget && !servicesDropdown.contains(event.relatedTarget)) closeServices();
    });
    servicesDropdown.addEventListener('keydown', event => {
        if (event.key !== 'Tab') return;
        // Tab can leave the document itself, also producing a null relatedTarget.
        // Check after native focus movement, without delaying pointer activation.
        window.setTimeout(() => {
            if (!servicesDropdown.contains(document.activeElement)) closeServices();
        }, 0);
    });
    servicesMenu.addEventListener('click', event => {
        const link = event.target.closest('a');
        if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        closeServices();
        if (menuButton?.getAttribute('aria-expanded') === 'true') setNavigationOpen(false);
        const url = new URL(link.href);
        if (url.pathname === location.pathname && url.hash) {
            const target = document.getElementById(decodeURIComponent(url.hash.slice(1)));
            requestAnimationFrame(() => target?.focus({preventScroll: true}));
        }
    });
    const syncServiceLocation = () => {
        const serviceLinks = [...servicesMenu.querySelectorAll('[data-service-link]')];
        let matched = false;
        serviceLinks.forEach(link => {
            const url = new URL(link.href);
            const active = url.pathname.replace(/\/$/, '') === location.pathname.replace(/\/$/, '') && url.hash === location.hash;
            if (active) { link.setAttribute('aria-current', 'location'); matched = true; }
            else link.removeAttribute('aria-current');
        });
        if (matched) languageMenu?.querySelectorAll('a').forEach(link => {
            const url = new URL(link.href);
            url.hash = location.hash;
            link.href = url.href;
        });
        else languageMenu?.querySelectorAll('a').forEach(link => {
            const url = new URL(link.href);
            url.hash = '';
            link.href = url.href;
        });
    };
    window.addEventListener('hashchange', syncServiceLocation);
    syncServiceLocation();
}

if (languageButton && languageMenu) {
    languageButton.addEventListener('click', () => {
        const open = languageButton.getAttribute('aria-expanded') !== 'true';
        closeServices();
        languageButton.setAttribute('aria-expanded', String(open));
        languageMenu.hidden = !open;
        if (open) languageMenu.querySelector('a')?.focus();
    });
}
document.addEventListener('click', event => {
    if (language && !language.contains(event.target)) closeLanguage();
    if (servicesDropdown && !servicesDropdown.contains(event.target)) closeServices();
});
document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    if (servicesDropdown?.open) {
        closeServices();
        servicesToggle?.focus();
    } else if (languageButton?.getAttribute('aria-expanded') === 'true') {
        closeLanguage();
        languageButton.focus();
    } else if (menuButton?.getAttribute('aria-expanded') === 'true') {
        setNavigationOpen(false);
        menuButton.focus();
    } else return;
    event.preventDefault();
});
window.matchMedia('(max-width: 860px)').addEventListener('change', () => setNavigationOpen(false));

const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
let reduceMotion = motionPreference.matches;
motionPreference.addEventListener('change', event => { reduceMotion = event.matches; });

document.querySelectorAll('[data-slider]').forEach(slider => {
    const slides = [...slider.querySelectorAll('[data-slide]')];
    const dots = [...slider.querySelectorAll('[data-slider-dot]')];
    const previousButton = slider.querySelector('[data-slider-previous]');
    const nextButton = slider.querySelector('[data-slider-next]');
    const pauseButton = slider.querySelector('[data-slider-pause]');
    const currentLabel = slider.querySelector('[data-slider-current]');
    const progressBar = slider.querySelector('[data-slider-progress]');
    const videos = slides.map(slide => slide.querySelector('[data-slide-video]'));
    const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    let activeIndex = 0;
    let timer = 0;
    let progressAnimation = null;
    let manuallyPaused = false;
    let focusPaused = false;
    let sliderVisible = slider.getBoundingClientRect().bottom > 0 && slider.getBoundingClientRect().top < window.innerHeight;
    let touchStartX = null;
    let touchStartY = null;
    const rtl = document.documentElement.dir === 'rtl';

    if (slides.length < 2) return;

    const stopTimer = () => {
        window.clearTimeout(timer);
        timer = 0;
        if (progressAnimation) {
            progressAnimation.cancel();
            progressAnimation = null;
        }
        if (progressBar) progressBar.style.transform = 'scaleX(0)';
    };

    const setSlideFocus = (slide, enabled) => {
        slide.querySelectorAll('a, button, input, select, textarea, [tabindex]').forEach(element => {
            if (enabled) element.removeAttribute('tabindex');
            else element.setAttribute('tabindex', '-1');
        });
    };

    const preloadSlide = requestedIndex => {
        const image = slides[(requestedIndex + slides.length) % slides.length]?.querySelector('img');
        if (!image) return;
        image.loading = 'eager';
        if (typeof image.decode === 'function') image.decode().catch(() => {});
    };

    const mediaRestricted = () => reduceMotion || Boolean(connection?.saveData);
    const mayAdvance = () => !reduceMotion && !manuallyPaused && !focusPaused && !document.hidden && sliderVisible;
    const videoMayPlay = index => index === activeIndex && mayAdvance() && !mediaRestricted();
    const usesVideoClock = index => Boolean(videos[index]) && !mediaRestricted() && videos[index].dataset.failed !== 'true' && videos[index].dataset.blocked !== 'true';

    const updateVideoProgress = (video, index) => {
        if (!progressBar || index !== activeIndex || !usesVideoClock(index)) return;
        const progress = Number.isFinite(video.duration) && video.duration > 0 ? Math.min(1, video.currentTime / video.duration) : 0;
        progressBar.style.transform = `scaleX(${progress})`;
    };

    const syncVideos = () => {
        videos.forEach((video, index) => {
            if (!video) return;
            if (!videoMayPlay(index) || !usesVideoClock(index)) {
                video.pause();
                if (mediaRestricted()) video.classList.remove('is-ready');
                return;
            }
            // A completed clip waits for advancement, never starts another loop.
            if (video.ended) return;
            // Defer even the source assignment: inactive slides and reduced-motion
            // visitors should not download a video they will not see.
            if (!video.getAttribute('src')) {
                video.muted = true;
                video.defaultMuted = true;
                const fallback = slides[index].querySelector('.hero-slide-image');
                video.poster = fallback?.currentSrc || fallback?.src || '';
                video.src = window.matchMedia('(max-width: 860px)').matches ? video.dataset.mobileSrc : video.dataset.src;
                video.load();
            }
            if (video.paused) {
                const attempt = video.play();
                attempt?.catch(error => {
                    // Low-power mode, network errors or autoplay policy: keep the poster.
                    if (error?.name === 'AbortError') return;
                    video.dataset.blocked = 'true';
                    video.classList.remove('is-ready');
                    if (index === activeIndex) scheduleNext();
                });
            } else if (video.readyState >= 2) {
                video.classList.add('is-ready');
            }
        });
    };

    videos.forEach((video, index) => {
        if (!video) return;
        video.loop = false;
        video.addEventListener('playing', () => {
            if (videoMayPlay(index)) video.classList.add('is-ready');
            else video.pause();
        });
        video.addEventListener('error', () => {
            video.dataset.failed = 'true';
            video.classList.remove('is-ready');
            if (index === activeIndex) scheduleNext();
        });
        video.addEventListener('timeupdate', () => updateVideoProgress(video, index));
        video.addEventListener('durationchange', () => updateVideoProgress(video, index));
        video.addEventListener('ended', () => {
            if (index === activeIndex && video.ended && usesVideoClock(index) && mayAdvance()) showSlide(index + 1);
        });
    });

    const scheduleNext = () => {
        stopTimer();
        syncVideos();
        if (usesVideoClock(activeIndex)) {
            const video = videos[activeIndex];
            updateVideoProgress(video, activeIndex);
            if (video.ended && mayAdvance()) showSlide(activeIndex + 1);
            return;
        }
        if (!mayAdvance()) return;
        // Only images (including unavailable-video fallbacks) use a fixed timer.
        const autoDuration = Math.min(20000, Math.max(4000, Number(slides[activeIndex].dataset.duration) || 6500));
        if (progressBar?.animate) {
            progressAnimation = progressBar.animate(
                [{ transform: 'scaleX(0)' }, { transform: 'scaleX(1)' }],
                { duration: autoDuration, easing: 'linear', fill: 'forwards' }
            );
        }
        timer = window.setTimeout(() => showSlide(activeIndex + 1), autoDuration);
    };

    const showSlide = requestedIndex => {
        const nextIndex = (requestedIndex + slides.length) % slides.length;
        const changed = nextIndex !== activeIndex;
        activeIndex = nextIndex;
        const video = videos[activeIndex];
        if (changed && video) {
            delete video.dataset.blocked;
            if (video.readyState > 0) video.currentTime = 0;
        }
        slides.forEach((slide, index) => {
            const active = index === activeIndex;
            slide.classList.toggle('is-active', active);
            slide.setAttribute('aria-hidden', String(!active));
            setSlideFocus(slide, active);
        });
        dots.forEach((dot, index) => {
            const active = index === activeIndex;
            dot.setAttribute('aria-pressed', String(active));
        });
        if (currentLabel) currentLabel.textContent = String(activeIndex + 1).padStart(2, '0');
        preloadSlide(activeIndex + 1);
        scheduleNext();
    };

    const setManualPause = paused => {
        manuallyPaused = paused;
        if (!paused && videos[activeIndex]) delete videos[activeIndex].dataset.blocked;
        pauseButton?.classList.toggle('is-paused', paused);
        if (pauseButton) {
            pauseButton.setAttribute('aria-label', paused ? pauseButton.dataset.playLabel : pauseButton.dataset.pauseLabel);
            pauseButton.setAttribute('aria-pressed', String(paused));
        }
        scheduleNext();
    };

    dots.forEach((dot, index) => dot.addEventListener('click', () => showSlide(index)));
    previousButton?.addEventListener('click', () => showSlide(activeIndex - 1));
    nextButton?.addEventListener('click', () => showSlide(activeIndex + 1));
    pauseButton?.addEventListener('click', () => setManualPause(!manuallyPaused));

    // Keep a keyboard-focused link inside a slide visible. Hovering or focusing
    // persistent carousel controls must not prevent an ended clip from advancing.
    slider.addEventListener('focusin', event => {
        focusPaused = slides.some(slide => slide.contains(event.target));
        scheduleNext();
    });
    slider.addEventListener('focusout', event => {
        focusPaused = slides.some(slide => slide.contains(event.relatedTarget));
        scheduleNext();
    });
    slider.addEventListener('keydown', event => {
        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            showSlide(activeIndex + (rtl ? 1 : -1));
        }
        if (event.key === 'ArrowRight') {
            event.preventDefault();
            showSlide(activeIndex + (rtl ? -1 : 1));
        }
    });
    slider.addEventListener('touchstart', event => {
        touchStartX = event.changedTouches[0]?.clientX ?? null;
        touchStartY = event.changedTouches[0]?.clientY ?? null;
    }, { passive: true });
    slider.addEventListener('touchend', event => {
        if (touchStartX === null) return;
        const distance = (event.changedTouches[0]?.clientX ?? touchStartX) - touchStartX;
        const vertical = (event.changedTouches[0]?.clientY ?? touchStartY) - touchStartY;
        touchStartX = null;
        touchStartY = null;
        if (Math.abs(distance) < 45 || Math.abs(distance) <= Math.abs(vertical)) return;
        showSlide(activeIndex + (distance < 0 ? 1 : -1) * (rtl ? -1 : 1));
    }, { passive: true });
    document.addEventListener('visibilitychange', scheduleNext);
    connection?.addEventListener?.('change', scheduleNext);
    if ('IntersectionObserver' in window) {
        const visibilityObserver = new IntersectionObserver(entries => {
            sliderVisible = entries[0].isIntersecting && entries[0].intersectionRatio >= 0.02;
            scheduleNext();
        }, { threshold: [0, 0.02] });
        visibilityObserver.observe(slider);
    }
    window.addEventListener('pagehide', () => {
        sliderVisible = false;
        scheduleNext();
    });
    window.addEventListener('pageshow', () => {
        const bounds = slider.getBoundingClientRect();
        sliderVisible = bounds.bottom > 0 && bounds.top < window.innerHeight;
        scheduleNext();
    });
    motionPreference.addEventListener('change', () => {
        if (pauseButton) pauseButton.hidden = reduceMotion;
        scheduleNext();
    });

    if (pauseButton && reduceMotion) pauseButton.hidden = true;
    showSlide(0);
});

const revealItems = document.querySelectorAll('.reveal');
if (reduceMotion || !('IntersectionObserver' in window)) {
    revealItems.forEach(item => item.classList.add('is-visible'));
} else {
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                entry.target.classList.remove('is-pending');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -35px' });
    revealItems.forEach(item => {
        if (item.getBoundingClientRect().top > window.innerHeight) item.classList.add('is-pending');
        observer.observe(item);
    });
}
