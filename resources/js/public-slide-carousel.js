/**
 * Shared horizontal track carousel (hero / gallery detail).
 * Drag/swipe with 1:1 follow, snap, clones for looping.
 */

const DEFAULT_THRESHOLD_PX = 56;
const DEFAULT_SLIDE_MS = 450;

/**
 * @param {HTMLElement} root
 * @param {object} [options]
 */
export function initSlideCarousel(root, options = {}) {
    if (!(root instanceof HTMLElement)) {
        return null;
    }

    const viewport = root.querySelector(options.viewportSelector || '[data-slide-carousel-viewport]');
    const track = root.querySelector(options.trackSelector || '[data-slide-carousel-track]');
    if (!viewport || !track) {
        return null;
    }

    const realSlides = Array.from(
        track.querySelectorAll(options.slideSelector || '[data-slide-carousel-slide]')
    ).filter((slide) => !slide.classList.contains('is-clone'));

    const slideCount = realSlides.length;
    if (slideCount < 2) {
        return null;
    }

    const dots = Array.from(root.querySelectorAll(options.dotSelector || '[data-slide-carousel-dot]'));
    const prevBtn = root.querySelector(options.prevSelector || '[data-slide-carousel-prev]');
    const nextBtn = root.querySelector(options.nextSelector || '[data-slide-carousel-next]');
    const ignoreSelector = options.controlsIgnoreSelector
        || 'a, button, input, textarea, select, label, [data-slide-carousel-controls]';

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const intervalMs = Number.isFinite(options.intervalMs)
        ? options.intervalMs
        : parseInt(root.getAttribute('data-slide-carousel-interval') || root.dataset.autoplay || '5000', 10);
    const thresholdPx = Number.isFinite(options.thresholdPx) ? options.thresholdPx : DEFAULT_THRESHOLD_PX;
    const slideMs = reduceMotion ? 0 : (Number.isFinite(options.slideMs) ? options.slideMs : DEFAULT_SLIDE_MS);
    const pauseOnHover = options.pauseOnHover ?? root.hasAttribute('data-slide-carousel-pause-hover');
    const pauseOnFocus = options.pauseOnFocus ?? root.hasAttribute('data-slide-carousel-pause-focus');
    const enableKeyboard = options.enableKeyboard ?? root.hasAttribute('data-slide-carousel-keyboard');

    // Loop clones
    const firstClone = realSlides[0].cloneNode(true);
    const lastClone = realSlides[slideCount - 1].cloneNode(true);
    [firstClone, lastClone].forEach((clone) => {
        clone.classList.add('is-clone');
        clone.removeAttribute('data-hero-index');
        clone.removeAttribute('data-gallery-index');
        clone.removeAttribute('data-slide-carousel-index');
        clone.setAttribute('aria-hidden', 'true');
    });
    track.insertBefore(lastClone, realSlides[0]);
    track.appendChild(firstClone);

    let index = 0;
    let position = 1;
    let timer = null;
    let paused = false;
    let isAnimating = false;
    let dragPointerId = null;
    let dragStartX = null;
    let dragActive = false;

    function viewportWidth() {
        return viewport.clientWidth || root.clientWidth || 1;
    }

    function setTrackOffset(offsetPx, withTransition) {
        if (withTransition && slideMs > 0) {
            root.classList.add('is-animating');
            track.style.transitionDuration = `${slideMs}ms`;
        } else {
            root.classList.remove('is-animating');
            track.style.transitionDuration = '0ms';
        }
        track.style.transform = `translate3d(${offsetPx}px, 0, 0)`;
    }

    function offsetForPosition(pos, dragPx) {
        return -pos * viewportWidth() + (dragPx || 0);
    }

    function updateDotsAndAria() {
        realSlides.forEach((slide, i) => {
            const active = i === index;
            slide.setAttribute('aria-hidden', active ? 'false' : 'true');
            slide.classList.toggle('is-active', active);
        });
        dots.forEach((dot, i) => {
            const active = i === index;
            dot.classList.toggle('is-active', active);
            if (active) {
                dot.setAttribute('aria-current', 'true');
            } else {
                dot.removeAttribute('aria-current');
            }
        });
    }

    function normalizePosition() {
        if (position === 0) {
            position = slideCount;
            index = slideCount - 1;
            setTrackOffset(offsetForPosition(position, 0), false);
        } else if (position === slideCount + 1) {
            position = 1;
            index = 0;
            setTrackOffset(offsetForPosition(position, 0), false);
        } else {
            index = position - 1;
        }
        updateDotsAndAria();
    }

    function afterSlideTransition(callback) {
        if (slideMs <= 0) {
            callback();
            return;
        }
        let done = false;
        const finish = () => {
            if (done) return;
            done = true;
            track.removeEventListener('transitionend', onEnd);
            window.clearTimeout(fallback);
            callback();
        };
        const onEnd = (event) => {
            if (event.target !== track) return;
            if (event.propertyName && event.propertyName !== 'transform') return;
            finish();
        };
        track.addEventListener('transitionend', onEnd);
        const fallback = window.setTimeout(finish, slideMs + 80);
    }

    function startTimer() {
        stopTimer();
        if (reduceMotion || paused || slideCount < 2 || document.hidden) {
            return;
        }
        timer = window.setInterval(() => {
            goNext();
        }, intervalMs);
    }

    function stopTimer() {
        if (timer) {
            window.clearInterval(timer);
            timer = null;
        }
    }

    function setPaused(nextPaused) {
        paused = !!nextPaused;
        if (paused) {
            stopTimer();
        } else {
            startTimer();
        }
    }

    function goToPosition(nextPosition, restart) {
        if (isAnimating || dragActive) return;
        if (nextPosition === position) {
            if (restart) startTimer();
            return;
        }

        isAnimating = true;
        stopTimer();
        position = nextPosition;
        setTrackOffset(offsetForPosition(position, 0), true);

        afterSlideTransition(() => {
            normalizePosition();
            isAnimating = false;
            root.classList.remove('is-animating');
            if (restart !== false) startTimer();
        });
    }

    function goPrev() {
        goToPosition(position - 1, true);
    }

    function goNext() {
        goToPosition(position + 1, true);
    }

    function goToIndex(targetIndex) {
        const next = ((targetIndex % slideCount) + slideCount) % slideCount;
        if (next === index && !isAnimating) {
            startTimer();
            return;
        }
        goToPosition(next + 1, true);
    }

    function isDragIgnoredTarget(target) {
        if (!(target instanceof Element)) return true;
        return Boolean(target.closest(ignoreSelector));
    }

    function clearDragListeners() {
        window.removeEventListener('pointermove', onWindowPointerMove, true);
        window.removeEventListener('pointerup', onWindowPointerUp, true);
        window.removeEventListener('pointercancel', onWindowPointerCancel, true);
    }

    function endDragState() {
        dragPointerId = null;
        dragStartX = null;
        dragActive = false;
        root.classList.remove('is-dragging');
        clearDragListeners();
    }

    function onWindowPointerMove(event) {
        if (!dragActive || event.pointerId !== dragPointerId) return;
        const dragDeltaX = event.clientX - dragStartX;
        event.preventDefault();
        setTrackOffset(offsetForPosition(position, dragDeltaX), false);
    }

    function onWindowPointerUp(event) {
        if (!dragActive) return;
        if (typeof event.pointerId === 'number' && event.pointerId !== dragPointerId) return;

        const deltaX = event.clientX - dragStartX;
        endDragState();

        if (deltaX <= -thresholdPx) {
            goNext();
            return;
        }
        if (deltaX >= thresholdPx) {
            goPrev();
            return;
        }

        isAnimating = true;
        setTrackOffset(offsetForPosition(position, 0), true);
        afterSlideTransition(() => {
            isAnimating = false;
            root.classList.remove('is-animating');
            startTimer();
        });
    }

    function onWindowPointerCancel() {
        if (!dragActive) return;
        endDragState();
        isAnimating = true;
        setTrackOffset(offsetForPosition(position, 0), true);
        afterSlideTransition(() => {
            isAnimating = false;
            root.classList.remove('is-animating');
            startTimer();
        });
    }

    function onPointerDown(event) {
        if (isAnimating || dragActive) return;
        if (event.pointerType === 'mouse' && event.button !== 0) return;
        if (isDragIgnoredTarget(event.target)) return;

        dragActive = true;
        dragPointerId = event.pointerId;
        dragStartX = event.clientX;
        stopTimer();
        root.classList.add('is-dragging');
        setTrackOffset(offsetForPosition(position, 0), false);

        window.addEventListener('pointermove', onWindowPointerMove, true);
        window.addEventListener('pointerup', onWindowPointerUp, true);
        window.addEventListener('pointercancel', onWindowPointerCancel, true);
    }

    prevBtn?.addEventListener('click', () => goPrev());
    nextBtn?.addEventListener('click', () => goNext());
    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            const raw = dot.getAttribute('data-slide-carousel-index')
                || dot.getAttribute('data-gallery-index')
                || dot.getAttribute('data-hero-dot')
                || '0';
            goToIndex(parseInt(raw, 10));
        });
    });

    root.addEventListener('pointerdown', onPointerDown);
    root.addEventListener('dragstart', (event) => {
        event.preventDefault();
    });

    if (enableKeyboard) {
        root.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                goPrev();
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                goNext();
            }
        });
    }

    if (pauseOnHover) {
        root.addEventListener('mouseenter', () => setPaused(true));
        root.addEventListener('mouseleave', () => {
            if (!root.contains(document.activeElement)) {
                setPaused(false);
            }
        });
    }

    if (pauseOnFocus) {
        root.addEventListener('focusin', () => setPaused(true));
        root.addEventListener('focusout', (event) => {
            if (!root.contains(event.relatedTarget)) {
                setPaused(false);
            }
        });
    }

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopTimer();
        } else if (!paused) {
            startTimer();
        }
    });

    window.addEventListener('resize', () => {
        setTrackOffset(offsetForPosition(position, 0), false);
    });

    root.classList.add('site-slide-carousel--ready');
    setTrackOffset(offsetForPosition(position, 0), false);
    updateDotsAndAria();
    startTimer();

    return {
        goPrev,
        goNext,
        goToIndex,
        destroy() {
            stopTimer();
            endDragState();
        },
    };
}

export function initAllSlideCarousels(selector = '[data-slide-carousel]') {
    return Array.from(document.querySelectorAll(selector))
        .map((root) => initSlideCarousel(root))
        .filter(Boolean);
}
