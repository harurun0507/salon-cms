@props([
    'galleries',
    'reserveUrl' => null,
])

@php
    $modalGalleries = collect($galleries)
        ->filter(fn ($gallery) => $gallery instanceof \App\Models\Gallery && $gallery->images->isNotEmpty())
        ->values();
    $modalItems = $modalGalleries
        ->map(fn (\App\Models\Gallery $gallery) => $gallery->toPublicModalData())
        ->all();
    $reserveUrl = filled($reserveUrl) ? (string) $reserveUrl : null;
@endphp

@if($modalGalleries->isNotEmpty())
    <x-public.modal-scroll-lock />
    <div
        id="gallery-modal"
        class="gallery-modal"
        hidden
        data-gallery-modal
        role="dialog"
        aria-modal="true"
        aria-hidden="true"
        aria-labelledby="gallery-modal-title"
    >
        <div class="gallery-modal__backdrop" data-gallery-modal-backdrop></div>

        <div class="gallery-modal__dialog" data-gallery-modal-dialog tabindex="-1">
            <button
                type="button"
                class="gallery-modal__close"
                data-gallery-modal-close
                aria-label="ギャラリーを閉じる"
            >
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </button>

            <button
                type="button"
                class="gallery-modal__gallery-nav gallery-modal__gallery-nav--prev"
                data-gallery-modal-prev
                aria-label="前のギャラリー"
            >
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M12.5 4.5 7 10l5.5 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <button
                type="button"
                class="gallery-modal__gallery-nav gallery-modal__gallery-nav--next"
                data-gallery-modal-next
                aria-label="次のギャラリー"
            >
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M7.5 4.5 13 10l-5.5 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <div class="gallery-modal__body">
                <div class="gallery-modal__media" data-gallery-modal-media>
                    <div class="gallery-modal__stage-wrap" data-gallery-modal-stage-wrap>
                        <div
                            class="gallery-modal__stage"
                            data-gallery-modal-stage
                            data-slide-carousel-viewport
                        ></div>
                        <div
                            class="gallery-modal__image-controls"
                            data-gallery-modal-image-controls
                            data-slide-carousel-controls
                            hidden
                        >
                            <button
                                type="button"
                                class="gallery-modal__image-nav gallery-modal__image-nav--prev"
                                data-gallery-modal-image-prev
                                data-slide-carousel-prev
                                aria-label="前の画像"
                            >
                                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                    <path d="M12.5 4.5 7 10l5.5 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <div class="gallery-modal__dots" data-gallery-modal-dots role="tablist" aria-label="画像選択"></div>
                            <button
                                type="button"
                                class="gallery-modal__image-nav gallery-modal__image-nav--next"
                                data-gallery-modal-image-next
                                data-slide-carousel-next
                                aria-label="次の画像"
                            >
                                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                    <path d="M7.5 4.5 13 10l-5.5 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="gallery-modal__info">
                    <p class="gallery-modal__category" data-gallery-modal-category hidden></p>
                    <h2 id="gallery-modal-title" class="gallery-modal__title" data-gallery-modal-title></h2>
                    <p class="gallery-modal__description" data-gallery-modal-description hidden></p>

                    <div class="gallery-modal__staff" data-gallery-modal-staff hidden>
                        <div class="gallery-modal__staff-photo" data-gallery-modal-staff-photo></div>
                        <div class="gallery-modal__staff-copy">
                            <p class="gallery-modal__staff-label">担当スタイリスト</p>
                            <p class="gallery-modal__staff-role" data-gallery-modal-staff-role hidden></p>
                            <p class="gallery-modal__staff-name" data-gallery-modal-staff-name></p>
                            @if($reserveUrl)
                                <a
                                    href="{{ $reserveUrl }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="btn-primary gallery-modal__reserve"
                                >予約する</a>
                            @endif
                        </div>
                    </div>
                </div>

                <div
                    class="gallery-modal__thumbs"
                    data-gallery-modal-thumbs
                    role="tablist"
                    aria-label="画像サムネイル"
                    hidden
                ></div>
            </div>
        </div>
    </div>

    <script type="application/json" data-gallery-modal-data>{!! json_encode(
        [
            'items' => $modalItems,
            'reserveUrl' => $reserveUrl,
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) !!}</script>

    <script>
        (function () {
            const modal = document.querySelector('[data-gallery-modal]');
            const dataEl = document.querySelector('[data-gallery-modal-data]');
            if (!modal || !dataEl) {
                return;
            }

            let payload = { items: [] };
            try {
                payload = JSON.parse(dataEl.textContent || '{}');
            } catch (e) {
                return;
            }

            const items = Array.isArray(payload.items) ? payload.items : [];
            if (!items.length) {
                return;
            }

            const backdrop = modal.querySelector('[data-gallery-modal-backdrop]');
            const dialog = modal.querySelector('[data-gallery-modal-dialog]');
            const closeBtn = modal.querySelector('[data-gallery-modal-close]');
            const prevGalleryBtn = modal.querySelector('[data-gallery-modal-prev]');
            const nextGalleryBtn = modal.querySelector('[data-gallery-modal-next]');
            const stageWrap = modal.querySelector('[data-gallery-modal-stage-wrap]');
            const stage = modal.querySelector('[data-gallery-modal-stage]');
            const imageControls = modal.querySelector('[data-gallery-modal-image-controls]');
            const dotsRoot = modal.querySelector('[data-gallery-modal-dots]');
            const thumbsRoot = modal.querySelector('[data-gallery-modal-thumbs]');
            const categoryEl = modal.querySelector('[data-gallery-modal-category]');
            const titleEl = modal.querySelector('[data-gallery-modal-title]');
            const descriptionEl = modal.querySelector('[data-gallery-modal-description]');
            const staffRoot = modal.querySelector('[data-gallery-modal-staff]');
            const staffPhotoEl = modal.querySelector('[data-gallery-modal-staff-photo]');
            const staffRoleEl = modal.querySelector('[data-gallery-modal-staff-role]');
            const staffNameEl = modal.querySelector('[data-gallery-modal-staff-name]');

            function isVerticalIndicator() {
                return document.documentElement.getAttribute('data-scroll-display') === 'vertical_indicator';
            }

            const modalScroll = window.SalonPublicModalScroll;
            let galleryIndex = 0;
            let imageIndex = 0;
            let isOpen = false;
            let lastFocus = null;
            let imageCarousel = null;

            function itemById(id) {
                const numericId = Number(id);
                return items.findIndex(function (item) {
                    return Number(item.id) === numericId;
                });
            }

            function currentItem() {
                return items[galleryIndex] || null;
            }

            function destroyImageCarousel() {
                if (imageCarousel && typeof imageCarousel.destroy === 'function') {
                    imageCarousel.destroy();
                }
                imageCarousel = null;
                if (stageWrap) {
                    stageWrap.removeAttribute('data-slide-carousel');
                    stageWrap.removeAttribute('data-slide-carousel-interval');
                    stageWrap.removeAttribute('data-slide-carousel-draggable');
                    stageWrap.classList.remove(
                        'site-slide-carousel--ready',
                        'site-slide-carousel--draggable',
                        'is-dragging',
                        'is-animating'
                    );
                }
            }

            function syncThumbSelectors() {
                if (!thumbsRoot) {
                    return;
                }
                Array.prototype.forEach.call(thumbsRoot.querySelectorAll('[data-gallery-modal-thumb]'), function (thumb, i) {
                    const active = i === imageIndex;
                    thumb.classList.toggle('is-active', active);
                    if (active) {
                        thumb.setAttribute('aria-current', 'true');
                    } else {
                        thumb.removeAttribute('aria-current');
                    }
                });
            }

            function createSlideFigure(image, item, index) {
                const figure = document.createElement('figure');
                figure.className = 'gallery-modal__slide' + (index === 0 ? ' is-active' : '');
                figure.setAttribute('data-gallery-modal-slide', '');
                figure.setAttribute('data-slide-carousel-slide', '');
                figure.setAttribute('data-slide-carousel-index', String(index));
                if (index !== 0) {
                    figure.setAttribute('aria-hidden', 'true');
                }

                const img = document.createElement('img');
                img.src = image.src;
                img.alt = image.alt || item.title || '';
                img.className = 'gallery-modal__image';
                img.draggable = false;
                if (index > 0) {
                    img.loading = 'lazy';
                }
                figure.appendChild(img);
                return figure;
            }

            function renderImages(item) {
                if (!stage || !imageControls || !stageWrap) {
                    return;
                }

                destroyImageCarousel();

                const images = Array.isArray(item.images) ? item.images : [];
                const multi = images.length > 1;
                const useThumbs = isVerticalIndicator() && multi;
                stage.innerHTML = '';
                if (dotsRoot) {
                    dotsRoot.innerHTML = '';
                    dotsRoot.hidden = useThumbs;
                }
                if (thumbsRoot) {
                    thumbsRoot.innerHTML = '';
                    thumbsRoot.hidden = !useThumbs;
                }

                imageIndex = 0;

                const track = document.createElement('div');
                track.className = 'gallery-modal__track';
                track.setAttribute('data-slide-carousel-track', '');

                images.forEach(function (image, index) {
                    track.appendChild(createSlideFigure(image, item, index));

                    if (multi && !useThumbs && dotsRoot) {
                        const dot = document.createElement('button');
                        dot.type = 'button';
                        dot.className = 'site-carousel-dot' + (index === 0 ? ' is-active' : '');
                        dot.setAttribute('data-gallery-modal-dot', '');
                        dot.setAttribute('data-slide-carousel-dot', '');
                        dot.setAttribute('data-slide-carousel-index', String(index));
                        dot.setAttribute('data-gallery-modal-image-index', String(index));
                        dot.setAttribute('aria-label', '画像' + (index + 1) + 'を表示');
                        if (index === 0) {
                            dot.setAttribute('aria-current', 'true');
                        }
                        dotsRoot.appendChild(dot);
                    }

                    if (useThumbs && thumbsRoot) {
                        const thumb = document.createElement('button');
                        thumb.type = 'button';
                        thumb.className = 'gallery-modal__thumb' + (index === 0 ? ' is-active' : '');
                        thumb.setAttribute('data-gallery-modal-thumb', '');
                        thumb.setAttribute('data-gallery-modal-image-index', String(index));
                        thumb.setAttribute('aria-label', '画像' + (index + 1) + 'を表示');
                        if (index === 0) {
                            thumb.setAttribute('aria-current', 'true');
                        }
                        const thumbImg = document.createElement('img');
                        thumbImg.src = image.src;
                        thumbImg.alt = '';
                        thumbImg.className = 'gallery-modal__thumb-image';
                        thumbImg.loading = 'lazy';
                        thumbImg.draggable = false;
                        thumb.appendChild(thumbImg);
                        thumb.addEventListener('click', function () {
                            if (imageCarousel && typeof imageCarousel.goToIndex === 'function') {
                                imageCarousel.goToIndex(index);
                            }
                        });
                        thumbsRoot.appendChild(thumb);
                    }
                });

                stage.appendChild(track);

                if (multi) {
                    stageWrap.setAttribute('data-slide-carousel', '');
                    stageWrap.setAttribute('data-slide-carousel-interval', '5000');
                    stageWrap.setAttribute('data-slide-carousel-draggable', '');

                    const api = window.SalonPublicSlideCarousel;
                    if (api && typeof api.init === 'function') {
                        imageCarousel = api.init(stageWrap, {
                            onIndexChange: function (index) {
                                imageIndex = index;
                                syncThumbSelectors();
                            },
                        });
                    }
                }

                imageControls.hidden = !multi;
                modal.classList.toggle('gallery-modal--multi-image', multi);
            }

            function renderInfo(item) {
                if (categoryEl) {
                    const categoryLabel = item.categoryLabel ? String(item.categoryLabel) : '';
                    if (categoryLabel && isVerticalIndicator()) {
                        categoryEl.hidden = false;
                        categoryEl.textContent = categoryLabel;
                    } else {
                        categoryEl.hidden = true;
                        categoryEl.textContent = '';
                    }
                }

                if (titleEl) {
                    titleEl.textContent = item.title || '';
                }

                if (descriptionEl) {
                    const description = item.description ? String(item.description) : '';
                    if (description) {
                        descriptionEl.hidden = false;
                        descriptionEl.textContent = description;
                    } else {
                        descriptionEl.hidden = true;
                        descriptionEl.textContent = '';
                    }
                }

                if (!staffRoot || !staffPhotoEl || !staffNameEl || !staffRoleEl) {
                    return;
                }

                if (!item.staff) {
                    staffRoot.hidden = true;
                    staffPhotoEl.innerHTML = '';
                    staffNameEl.textContent = '';
                    staffRoleEl.hidden = true;
                    staffRoleEl.textContent = '';
                    return;
                }

                staffRoot.hidden = false;
                staffNameEl.textContent = item.staff.name || '';
                if (item.staff.role && !isVerticalIndicator()) {
                    staffRoleEl.hidden = false;
                    staffRoleEl.textContent = item.staff.role;
                } else {
                    staffRoleEl.hidden = true;
                    staffRoleEl.textContent = '';
                }

                staffPhotoEl.innerHTML = '';
                if (isVerticalIndicator()) {
                    return;
                }

                if (item.staff.photo) {
                    const img = document.createElement('img');
                    img.src = item.staff.photo;
                    img.alt = item.staff.name || '';
                    img.className = 'gallery-modal__staff-image';
                    staffPhotoEl.appendChild(img);
                } else {
                    const placeholder = document.createElement('div');
                    placeholder.className = 'gallery-modal__staff-placeholder';
                    placeholder.setAttribute('aria-hidden', 'true');
                    placeholder.textContent = 'Staff';
                    staffPhotoEl.appendChild(placeholder);
                }
            }

            function updateGalleryNav() {
                // VI: open one gallery from the list — no cross-gallery arrows.
                const hideGalleryNav = isVerticalIndicator() || items.length < 2;
                if (prevGalleryBtn) {
                    prevGalleryBtn.hidden = hideGalleryNav;
                }
                if (nextGalleryBtn) {
                    nextGalleryBtn.hidden = hideGalleryNav;
                }
            }

            function canSwitchImages() {
                const item = currentItem();
                return !!(item && item.images && item.images.length > 1);
            }

            function canSwitchGalleries() {
                return !isVerticalIndicator() && items.length > 1;
            }

            function renderGallery(index) {
                if (!items.length) {
                    return;
                }
                galleryIndex = ((index % items.length) + items.length) % items.length;
                const item = currentItem();
                if (!item) {
                    return;
                }
                // Always start from the first image when (re)opening a gallery.
                renderImages(item);
                renderInfo(item);
                updateGalleryNav();
            }

            function openModal(id) {
                const index = itemById(id);
                if (index < 0) {
                    return;
                }

                if (isOpen) {
                    renderGallery(index);
                    return;
                }

                lastFocus = document.activeElement;
                isOpen = true;
                modalScroll?.lock(modal);
                modal.hidden = false;
                modal.setAttribute('aria-hidden', 'false');
                modal.classList.add('is-open');
                renderGallery(index);
                window.setTimeout(function () {
                    modalScroll?.focusWithoutScroll(closeBtn || dialog);
                }, 0);
            }

            function closeModal() {
                if (!isOpen) {
                    return;
                }
                isOpen = false;
                destroyImageCarousel();
                // Blur modal focus before hide to avoid the browser scrolling to top.
                if (document.activeElement && modal.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
                modal.classList.remove('is-open');
                modal.hidden = true;
                modal.setAttribute('aria-hidden', 'true');
                modalScroll?.unlock();
                modalScroll?.focusWithoutScroll(lastFocus);
                lastFocus = null;
            }

            function onTriggerClick(event) {
                if (event.defaultPrevented) {
                    return;
                }
                if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }

                const trigger = event.target.closest('[data-gallery-modal-trigger]');
                if (!trigger) {
                    return;
                }

                const id = trigger.getAttribute('data-gallery-id');
                if (!id || itemById(id) < 0) {
                    return;
                }

                event.preventDefault();
                openModal(id);
            }

            document.addEventListener('click', onTriggerClick);

            closeBtn?.addEventListener('click', closeModal);
            backdrop?.addEventListener('click', closeModal);
            prevGalleryBtn?.addEventListener('click', function () {
                if (!canSwitchGalleries()) {
                    return;
                }
                renderGallery(galleryIndex - 1);
            });
            nextGalleryBtn?.addEventListener('click', function () {
                if (!canSwitchGalleries()) {
                    return;
                }
                renderGallery(galleryIndex + 1);
            });

            document.addEventListener('keydown', function (event) {
                if (!isOpen) {
                    return;
                }
                if (event.key === 'Escape') {
                    event.preventDefault();
                    closeModal();
                    return;
                }
                if (event.key === 'ArrowLeft') {
                    event.preventDefault();
                    if (canSwitchImages() && imageCarousel && typeof imageCarousel.goPrev === 'function') {
                        imageCarousel.goPrev();
                    } else if (canSwitchGalleries()) {
                        renderGallery(galleryIndex - 1);
                    }
                    return;
                }
                if (event.key === 'ArrowRight') {
                    event.preventDefault();
                    if (canSwitchImages() && imageCarousel && typeof imageCarousel.goNext === 'function') {
                        imageCarousel.goNext();
                    } else if (canSwitchGalleries()) {
                        renderGallery(galleryIndex + 1);
                    }
                }
            });
        })();
    </script>
@endif
