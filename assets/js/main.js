// assets/js/main.js — Vueports Solutions

document.addEventListener('DOMContentLoaded', () => {
    console.log('Vueports Solutions v1.2 — All systems ready');

    /* ========================================
       Theme Toggle
       ======================================== */
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    const savedTheme = localStorage.getItem('vueports_theme') || 'light';
    html.setAttribute('data-theme', savedTheme);

    function updateThemeIcon(theme) {
        if (!themeToggle) return;
        const icon = themeToggle.querySelector('i, svg');
        if (icon) {
            icon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        }
        themeToggle.innerHTML = theme === 'light'
            ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>'
            : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>';
    }
    updateThemeIcon(savedTheme);

    themeToggle?.addEventListener('click', () => {
        const current = html.getAttribute('data-theme');
        const next = current === 'light' ? 'dark' : 'light';
        html.setAttribute('data-theme', next);
        localStorage.setItem('vueports_theme', next);
        updateThemeIcon(next);
    });

    /* ========================================
       Mobile Navigation
       ======================================== */
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.getElementById('navMenu');
    const mobileNav = document.getElementById('mobileNav');

    function closeMobileNav() {
        hamburger?.classList.remove('active');
        navMenu?.classList.remove('active');
        mobileNav?.classList.remove('open');
        hamburger?.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    hamburger?.addEventListener('click', () => {
        const isActive = hamburger.classList.toggle('active');
        navMenu?.classList.toggle('active');
        mobileNav?.classList.toggle('open');
        hamburger.setAttribute('aria-expanded', isActive ? 'true' : 'false');
        document.body.style.overflow = isActive ? 'hidden' : '';
    });

    document.querySelectorAll('#mobileNav a, #navMenu a').forEach(link => {
        link.addEventListener('click', closeMobileNav);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeMobileNav();
    });

    document.addEventListener('click', (e) => {
        if (mobileNav?.classList.contains('open') && !mobileNav.contains(e.target) && !hamburger?.contains(e.target)) {
            closeMobileNav();
        }
    });

    /* ========================================
       Navbar Scroll Effect
       ======================================== */
    const navbar = document.getElementById('navbar');
    let lastScroll = 0;
    let ticking = false;

    window.addEventListener('scroll', () => {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                const currentScroll = window.scrollY;
                navbar?.classList.toggle('scrolled', currentScroll > 50);
                if (currentScroll > lastScroll && currentScroll > 200) {
                    navbar?.classList.add('nav-hidden');
                } else {
                    navbar?.classList.remove('nav-hidden');
                }
                lastScroll = currentScroll;
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });

    /* ========================================
       Stats Counter Animation
       ======================================== */
    const statsBar = document.querySelector('.stats-bar');
    let statsAnimated = false;

    function animateStats() {
        if (statsAnimated) return;
        statsAnimated = true;
        const statItems = document.querySelectorAll('.stat-item');
        statItems.forEach((item, index) => {
            const target = parseInt(item.dataset.count || item.dataset.target || '0');
            const numEl = item.querySelector('.stat-number');
            const suffix = item.dataset.suffix || '';
            if (!target || !numEl) return;
            setTimeout(() => {
                let current = 0;
                const duration = 2000;
                const startTime = performance.now();
                function updateNumber(currentTime) {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    const easeOut = 1 - Math.pow(1 - progress, 3);
                    current = Math.floor(target * easeOut);
                    numEl.textContent = current.toLocaleString() + suffix;
                    if (progress < 1) {
                        requestAnimationFrame(updateNumber);
                    } else {
                        numEl.textContent = target.toLocaleString() + suffix;
                    }
                }
                requestAnimationFrame(updateNumber);
            }, index * 150);
        });
    }

    if (statsBar && 'IntersectionObserver' in window) {
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateStats();
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });
        statsObserver.observe(statsBar);
    }

    /* ========================================
       Testimonial Slider
       ======================================== */
    const track = document.getElementById('testimonialsTrack');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const dotsContainer = document.getElementById('sliderDots');

    if (track && prevBtn && nextBtn) {
        const cards = Array.from(track.children);
        let currentIndex = 0;
        let autoplayInterval;
        let touchStartX = 0;
        let touchEndX = 0;

        function getVisibleCount() {
            if (window.innerWidth >= 1024) return 3;
            if (window.innerWidth >= 768) return 2;
            return 1;
        }

        function getMaxIndex() {
            const visible = getVisibleCount();
            return Math.max(0, cards.length - visible);
        }

        function createDots() {
            if (!dotsContainer) return;
            dotsContainer.innerHTML = '';
            const maxIndex = getMaxIndex();
            for (let i = 0; i <= maxIndex; i++) {
                const dot = document.createElement('button');
                dot.className = 'slider-dot' + (i === 0 ? ' active' : '');
                dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));
                dot.addEventListener('click', () => goToSlide(i));
                dotsContainer.appendChild(dot);
            }
        }

        function updateSlider() {
            const visible = getVisibleCount();
            const gap = 24;
            const cardWidth = cards[0]?.offsetWidth || 0;
            const offset = currentIndex * (cardWidth + gap);
            track.style.transform = `translateX(-${offset}px)`;
            if (dotsContainer) {
                const dots = dotsContainer.querySelectorAll('.slider-dot');
                dots.forEach((dot, i) => dot.classList.toggle('active', i === currentIndex));
            }
            prevBtn.disabled = currentIndex === 0;
            nextBtn.disabled = currentIndex >= getMaxIndex();
        }

        function goToSlide(index) {
            currentIndex = Math.max(0, Math.min(index, getMaxIndex()));
            updateSlider();
            resetAutoplay();
        }

        function nextSlide() {
            const max = getMaxIndex();
            currentIndex = currentIndex < max ? currentIndex + 1 : 0;
            updateSlider();
        }

        function prevSlide() {
            currentIndex = currentIndex > 0 ? currentIndex - 1 : getMaxIndex();
            updateSlider();
        }

        function startAutoplay() {
            autoplayInterval = setInterval(nextSlide, 5000);
        }

        function resetAutoplay() {
            clearInterval(autoplayInterval);
            startAutoplay();
        }

        nextBtn.addEventListener('click', () => { nextSlide(); resetAutoplay(); });
        prevBtn.addEventListener('click', () => { prevSlide(); resetAutoplay(); });

        track.addEventListener('touchstart', (e) => { touchStartX = e.changedTouches[0].screenX; }, { passive: true });
        track.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            const diff = touchStartX - touchEndX;
            if (Math.abs(diff) > 50) {
                diff > 0 ? nextSlide() : prevSlide();
                resetAutoplay();
            }
        }, { passive: true });

        track.addEventListener('mouseenter', () => clearInterval(autoplayInterval));
        track.addEventListener('mouseleave', startAutoplay);

        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                currentIndex = Math.min(currentIndex, getMaxIndex());
                createDots();
                updateSlider();
            }, 250);
        });

        createDots();
        updateSlider();
        startAutoplay();
    }

    /* ========================================
       Typewriter Effect
       ======================================== */
    const typewriter = document.getElementById('typewriter');
    if (typewriter) {
        let words;
        try {
            words = JSON.parse(typewriter.dataset.words || '["Software Architect", "Data Engineer", "AI Agent Builder", "Cloud Specialist"]');
        } catch {
            words = ["Software Architect", "Data Engineer", "AI Agent Builder", "Cloud Specialist"];
        }
        let wordIndex = 0;
        let charIndex = 0;
        let isDeleting = false;
        let typeTimeout;
        let isPaused = false;

        function type() {
            if (isPaused) return;
            const current = words[wordIndex];
            const displayText = isDeleting
                ? current.substring(0, charIndex - 1)
                : current.substring(0, charIndex + 1);
            typewriter.textContent = displayText;
            typewriter.setAttribute('aria-label', `Current role: ${displayText}`);
            const typeSpeed = isDeleting ? 50 : 100;
            if (!isDeleting && charIndex < current.length) {
                charIndex++;
                typeTimeout = setTimeout(type, typeSpeed);
            } else if (isDeleting && charIndex > 0) {
                charIndex--;
                typeTimeout = setTimeout(type, typeSpeed);
            } else {
                isDeleting = !isDeleting;
                if (!isDeleting) wordIndex = (wordIndex + 1) % words.length;
                typeTimeout = setTimeout(type, isDeleting ? 2000 : 500);
            }
        }
        type();

        if ('IntersectionObserver' in window) {
            new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (!entry.isIntersecting) {
                        isPaused = true;
                        clearTimeout(typeTimeout);
                    } else {
                        isPaused = false;
                        if (!typewriter.textContent) type();
                    }
                });
            }, { threshold: 0.1 }).observe(typewriter);
        }
    }

    /* ========================================
       FAQ Accordion
       ======================================== */
    document.querySelectorAll('.faq-item:not(details)').forEach(item => {
        const question = item.querySelector('.faq-question');
        question?.addEventListener('click', () => {
            const isActive = item.classList.contains('active');
            document.querySelectorAll('.faq-item:not(details)').forEach(other => {
                if (other !== item) other.classList.remove('active');
            });
            item.classList.toggle('active', !isActive);
        });
    });

    /* ========================================
       Toast Notification System
       ======================================== */
    window.showToast = (message, type = 'success', duration = 3000) => {
        let toast = document.getElementById('toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'toast';
            document.body.appendChild(toast);
        }
        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };
        toast.className = 'toast-' + type;
        toast.innerHTML = `<span>${message}</span>`;
        toast.style.cssText = `
            position: fixed; bottom: 24px; right: 24px;
            background: ${colors[type] || colors.info}; color: white;
            padding: 14px 24px; border-radius: 12px; font-weight: 500;
            font-size: 14px; box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            z-index: 9999; display: flex; align-items: center; gap: 10px;
            transform: translateX(150%);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            max-width: 400px; word-wrap: break-word;
        `;
        requestAnimationFrame(() => { toast.style.transform = 'translateX(0)'; });
        const hideTimeout = setTimeout(() => {
            toast.style.transform = 'translateX(150%)';
            setTimeout(() => { toast.style.display = 'none'; }, 400);
        }, duration);
        toast.addEventListener('click', () => {
            clearTimeout(hideTimeout);
            toast.style.transform = 'translateX(150%)';
        }, { once: true });
    };

    /* ========================================
       Scroll Animations
       ======================================== */
    const animateElements = document.querySelectorAll('.animate-on-scroll, .animate, .reveal');
    if ('IntersectionObserver' in window) {
        const scrollObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const delay = parseInt(el.dataset.delay || '0');
                    setTimeout(() => {
                        el.classList.add('visible', 'animated');
                    }, delay);
                    if (!el.dataset.repeat) scrollObserver.unobserve(el);
                } else if (el.dataset.repeat) {
                    entry.target.classList.remove('visible', 'animated');
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
        animateElements.forEach(el => scrollObserver.observe(el));
    } else {
        animateElements.forEach(el => el.classList.add('visible', 'animated'));
    }

    /* ========================================
       Smooth Scroll
       ======================================== */
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                const navHeight = navbar?.offsetHeight || 0;
                const targetPosition = target.getBoundingClientRect().top + window.scrollY - navHeight - 20;
                window.scrollTo({ top: targetPosition, behavior: 'smooth' });
                history.pushState(null, '', targetId);
            }
        });
    });

    /* ========================================
       Form Validation Helpers
       ======================================== */
    window.validateEmail = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    window.validatePhone = (phone) => /^(\+27|0)[6-8][0-9]{8}$/.test(phone.replace(/\s/g, ''));
    window.validateRequired = (value) => value.trim().length > 0;

    /* ========================================
       AJAX Form Submit
       ======================================== */
    window.submitFormAjax = async (form, options = {}) => {
        const { onSuccess = () => {}, onError = () => {}, showLoading = true } = options;
        const submitBtn = form.querySelector('[type="submit"]');
        const originalText = submitBtn?.innerHTML || 'Submit';
        if (showLoading && submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Processing...';
        }
        try {
            const formData = new FormData(form);
            const response = await fetch(form.action || window.location.href, {
                method: form.method || 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            let data;
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                data = { success: response.ok, message: response.ok ? 'Success!' : 'Request failed' };
            }
            if (data.success) {
                window.showToast(data.message || 'Success!', 'success');
                onSuccess(data);
                if (data.redirect) setTimeout(() => window.location.href = data.redirect, 1500);
            } else {
                window.showToast(data.message || 'Something went wrong.', 'error');
                onError(data);
            }
            return data;
        } catch (err) {
            window.showToast('Network error. Please try again.', 'error');
            onError({ error: err.message });
            return { success: false, error: err.message };
        } finally {
            if (showLoading && submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
    };

    /* ========================================
       Calculator
       ======================================== */
    window.calculateEstimate = function() {
        const service = document.getElementById('calc-service')?.value;
        const complexity = document.getElementById('calc-complexity')?.value || 'medium';
        const checkboxes = document.querySelectorAll('#projectCalculator [name="features[]"]:checked');
        const resultDisplay = document.getElementById('calculatorResult');
        if (!service) {
            window.showToast('Please select a service type', 'warning');
            return;
        }
        const basePrices = {
            'custom-software-web': { min: 15000, max: 250000 },
            'data-engineering-analytics': { min: 25000, max: 350000 },
            'ai-agent-development': { min: 20000, max: 400000 }
        };
        const multipliers = { 'low': 0.7, 'medium': 1.0, 'high': 1.5, 'enterprise': 2.5 };
        const base = basePrices[service] || { min: 10000, max: 100000 };
        const mult = multipliers[complexity] || 1.0;
        let featureAdd = 0;
        checkboxes.forEach(cb => featureAdd += parseInt(cb.dataset.price || '0'));
        const min = Math.round(base.min * mult + featureAdd);
        const max = Math.round(base.max * mult + featureAdd);
        if (resultDisplay) {
            resultDisplay.innerHTML = `
                <div class="result-label">Estimated Investment</div>
                <div class="result-range">R${min.toLocaleString()} — R${max.toLocaleString()}</div>
                <div class="result-note">Final quote after scoping session</div>
            `;
            resultDisplay.style.display = 'block';
            resultDisplay.classList.add('animate-on-scroll', 'visible');
        }
    };

    /* ========================================
       Booking Calendar
       ======================================== */
    const bookingDateInput = document.querySelector('[name="booking_date"]');
    if (bookingDateInput) {
        const today = new Date().toISOString().split('T')[0];
        bookingDateInput.setAttribute('min', today);
        bookingDateInput.addEventListener('change', async (e) => {
            const timeSelect = document.querySelector('[name="booking_time"]');
            if (!timeSelect) return;
            timeSelect.innerHTML = '<option value="">Loading slots...</option>';
            timeSelect.disabled = true;
            try {
                const response = await fetch(`api/v1/bookings.php?date=${e.target.value}`);
                if (!response.ok) throw new Error('Failed to load');
                const data = await response.json();
                timeSelect.innerHTML = '<option value="">Select a time</option>';
                data.slots?.forEach(slot => {
                    const option = document.createElement('option');
                    option.value = slot.time;
                    option.textContent = slot.time + (slot.available ? '' : ' (Booked)');
                    option.disabled = !slot.available;
                    timeSelect.appendChild(option);
                });
                timeSelect.disabled = false;
            } catch (err) {
                timeSelect.innerHTML = '<option value="">Error loading slots</option>';
                console.error('Booking slots error:', err);
            }
        });
    }

    /* ========================================
       Lazy Loading Images
       ======================================== */
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img[data-src]');
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    img.classList.add('loaded');
                    img.addEventListener('load', () => img.classList.add('img-loaded'), { once: true });
                    imageObserver.unobserve(img);
                }
            });
        }, { rootMargin: '100px' });
        lazyImages.forEach(img => imageObserver.observe(img));
    }

    /* ========================================
       Back to Top
       ======================================== */
    const backToTop = document.getElementById('backToTop');
    if (backToTop) {
        window.addEventListener('scroll', () => {
            backToTop.classList.toggle('visible', window.scrollY > 500);
        }, { passive: true });
        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /* ========================================
       Copy to Clipboard
       ======================================== */
    window.copyToClipboard = async (text, successMessage = 'Copied!') => {
        try {
            await navigator.clipboard.writeText(text);
            window.showToast(successMessage, 'success');
            return true;
        } catch (err) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.cssText = 'position:fixed;opacity:0;pointer-events:none;';
            document.body.appendChild(textarea);
            textarea.select();
            const success = document.execCommand('copy');
            document.body.removeChild(textarea);
            if (success) {
                window.showToast(successMessage, 'success');
                return true;
            }
            window.showToast('Failed to copy', 'error');
            return false;
        }
    };

    /* ========================================
       Cookie Consent Banner
       ======================================== */
    (function() {
        const banner = document.createElement('div');
        banner.className = 'cookie-banner';
        banner.innerHTML = `
            <div class="container">
                <p>We use cookies to enhance your experience. By continuing, you agree to our
                    <a href="legal/cookies.php">Cookie Policy</a> and
                    <a href="legal/privacy.php">Privacy Policy</a>.
                </p>
                <div class="cookie-actions">
                    <button class="btn btn-outline btn-sm" id="cookieReject">Essential Only</button>
                    <button class="btn btn-primary btn-sm" id="cookieAccept">Accept All</button>
                </div>
            </div>
        `;
        document.body.appendChild(banner);
        const consentKey = 'vueports_consent';
        const consent = localStorage.getItem(consentKey);
        if (!consent) {
            setTimeout(() => banner.classList.add('active'), 1000);
        }
        document.getElementById('cookieAccept')?.addEventListener('click', () => {
            localStorage.setItem(consentKey, JSON.stringify({ essential: true, analytics: true, functional: true, date: new Date().toISOString() }));
            banner.classList.remove('active');
            window.showToast('Cookies accepted. Thank you!', 'success');
        });
        document.getElementById('cookieReject')?.addEventListener('click', () => {
            localStorage.setItem(consentKey, JSON.stringify({ essential: true, analytics: false, functional: false, date: new Date().toISOString() }));
            banner.classList.remove('active');
            window.showToast('Only essential cookies enabled.', 'info');
        });
    })();
});
