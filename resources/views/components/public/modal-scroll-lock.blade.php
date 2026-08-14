{{-- Shared body scroll lock for public detail modals (news / blog / gallery). --}}
@once
    <script>
        (function () {
            if (window.SalonPublicModalScroll) {
                return;
            }

            var lockedScrollY = 0;
            var lockCount = 0;
            var touchMoveOptions = { passive: false, capture: true };
            var wheelOptions = { passive: false, capture: true };

            function currentScrollY() {
                return window.scrollY
                    || window.pageYOffset
                    || document.documentElement.scrollTop
                    || document.body.scrollTop
                    || 0;
            }

            function applyScrollY(y) {
                var html = document.documentElement;
                var previousBehavior = html.style.scrollBehavior;
                var hadSmoothClass = html.classList.contains('scroll-smooth');

                html.style.scrollBehavior = 'auto';
                if (hadSmoothClass) {
                    html.classList.remove('scroll-smooth');
                }

                html.scrollTop = y;
                if (document.body) {
                    document.body.scrollTop = y;
                }

                if (hadSmoothClass) {
                    html.classList.add('scroll-smooth');
                }
                html.style.scrollBehavior = previousBehavior;
            }

            function isScrollableModalSurface(target) {
                if (!target || typeof target.closest !== 'function') {
                    return false;
                }
                return Boolean(target.closest(
                    '.content-modal__dialog, .gallery-modal__dialog, .content-modal__body, .content-modal__blog-article, .gallery-modal__body'
                ));
            }

            function preventBackgroundTouch(event) {
                if (isScrollableModalSurface(event.target)) {
                    return;
                }
                event.preventDefault();
            }

            function preventBackgroundWheel(event) {
                if (isScrollableModalSurface(event.target)) {
                    return;
                }
                event.preventDefault();
            }

            function pinSiteHeader() {
                var header = document.querySelector('[data-site-header]');
                if (!header || header.dataset.modalPinned === '1') {
                    return;
                }

                var height = Math.round(header.getBoundingClientRect().height)
                    || Math.round(header.offsetHeight)
                    || 0;
                if (height > 0 && !document.querySelector('[data-modal-header-spacer]')) {
                    var spacer = document.createElement('div');
                    spacer.setAttribute('data-modal-header-spacer', '');
                    spacer.setAttribute('aria-hidden', 'true');
                    spacer.style.height = height + 'px';
                    spacer.style.width = '100%';
                    spacer.style.pointerEvents = 'none';
                    header.parentNode.insertBefore(spacer, header);
                }

                header.dataset.modalPinned = '1';
                header.classList.add('is-public-modal-header-pinned');
            }

            function unpinSiteHeader() {
                var header = document.querySelector('[data-site-header]');
                var spacer = document.querySelector('[data-modal-header-spacer]');
                if (spacer) {
                    spacer.remove();
                }
                if (header) {
                    header.classList.remove('is-public-modal-header-pinned');
                    delete header.dataset.modalPinned;
                }
            }

            function mountModal(modalEl) {
                if (!modalEl || !document.body || modalEl.parentNode === document.body) {
                    return;
                }
                document.body.appendChild(modalEl);
            }

            function lock(modalEl) {
                if (lockCount === 0) {
                    lockedScrollY = currentScrollY();
                    document.documentElement.classList.add('is-public-modal-open');
                    document.body.classList.add('is-public-modal-open');

                    /*
                     * Freeze scroll with overflow:hidden (keeps scrollY).
                     * Do NOT use body { position:fixed; top:-scrollY }: it (and
                     * overflow:hidden) unsticks the header and exposes Concept
                     * in the header band above News/Blog.
                     * Pin the header to the viewport so that band stays covered.
                     */
                    document.documentElement.style.overflow = 'hidden';
                    document.body.style.overflow = 'hidden';
                    document.body.style.top = '';
                    pinSiteHeader();
                    document.addEventListener('touchmove', preventBackgroundTouch, touchMoveOptions);
                    document.addEventListener('wheel', preventBackgroundWheel, wheelOptions);
                }
                mountModal(modalEl);
                lockCount += 1;
            }

            function unlock() {
                if (lockCount === 0) {
                    return lockedScrollY;
                }
                lockCount -= 1;
                if (lockCount > 0) {
                    return lockedScrollY;
                }

                var y = lockedScrollY;

                document.removeEventListener('touchmove', preventBackgroundTouch, touchMoveOptions);
                document.removeEventListener('wheel', preventBackgroundWheel, wheelOptions);
                unpinSiteHeader();
                document.documentElement.style.overflow = '';
                document.body.style.overflow = '';
                document.body.style.top = '';
                document.documentElement.classList.remove('is-public-modal-open');
                document.body.classList.remove('is-public-modal-open');

                if (Math.abs(currentScrollY() - y) > 1) {
                    applyScrollY(y);
                }

                return y;
            }

            function focusWithoutScroll(el) {
                if (!el || typeof el.focus !== 'function') {
                    return;
                }
                try {
                    el.focus({ preventScroll: true });
                } catch (e) {
                    el.focus();
                }
            }

            window.SalonPublicModalScroll = {
                lock: lock,
                unlock: unlock,
                focusWithoutScroll: focusWithoutScroll,
            };
        })();
    </script>
@endonce
