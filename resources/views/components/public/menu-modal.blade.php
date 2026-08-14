@props(['categories'])

@php
    $modalCategories = collect($categories ?? [])
        ->filter(fn ($category) => $category instanceof \App\Models\MenuCategory)
        ->values()
        ->map(fn (\App\Models\MenuCategory $category) => $category->toPublicModalData())
        ->filter(fn (array $category) => count($category['menus']) > 0)
        ->values()
        ->all();
@endphp

@if(count($modalCategories) > 0)
    <x-public.modal-scroll-lock />
    <div
        id="menu-modal"
        class="content-modal"
        hidden
        data-menu-modal
        role="dialog"
        aria-modal="true"
        aria-hidden="true"
        aria-labelledby="menu-modal-title"
    >
        <div class="content-modal__backdrop" data-menu-modal-backdrop></div>
        <div class="content-modal__dialog content-modal__dialog--menu" data-menu-modal-dialog tabindex="-1">
            <header class="content-modal__menu-bar">
                <div class="content-modal__menu-bar-copy">
                    <p class="content-modal__eyebrow content-modal__menu-bar-eyebrow" data-menu-modal-eyebrow hidden></p>
                    <h2 id="menu-modal-title" class="content-modal__title content-modal__menu-bar-title" data-menu-modal-title></h2>
                </div>
                <button type="button" class="content-modal__close content-modal__close--menu" data-menu-modal-close aria-label="メニューを閉じる">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </button>
            </header>

            <div class="content-modal__body content-modal__body--menu" data-menu-modal-scroll>
                <div class="content-modal__menu-root" data-menu-modal-root></div>
            </div>
        </div>
    </div>

    <script type="application/json" data-menu-modal-data>{!! json_encode(
        ['categories' => $modalCategories],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) !!}</script>

    <script>
        (function () {
            const modal = document.querySelector('[data-menu-modal]');
            const dataEl = document.querySelector('[data-menu-modal-data]');
            if (!modal || !dataEl) return;

            let payload = { categories: [] };
            try { payload = JSON.parse(dataEl.textContent || '{}'); } catch (e) { return; }
            const categories = Array.isArray(payload.categories) ? payload.categories : [];
            if (!categories.length) return;

            const backdrop = modal.querySelector('[data-menu-modal-backdrop]');
            const dialog = modal.querySelector('[data-menu-modal-dialog]');
            const scrollEl = modal.querySelector('[data-menu-modal-scroll]');
            const closeBtn = modal.querySelector('[data-menu-modal-close]');
            const eyebrowEl = modal.querySelector('[data-menu-modal-eyebrow]');
            const titleEl = modal.querySelector('[data-menu-modal-title]');
            const rootEl = modal.querySelector('[data-menu-modal-root]');

            const modalScroll = window.SalonPublicModalScroll;
            let isOpen = false;
            let lastFocus = null;

            function categoryById(id) {
                const numericId = Number(id);
                return categories.find(function (item) { return Number(item.id) === numericId; }) || null;
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function renderMenuItem(menu) {
                const tags = Array.isArray(menu.tags) ? menu.tags : [];
                let tagsHtml = '';
                if (tags.length) {
                    tagsHtml = '<ul class="menu-price-tags" aria-label="含まれるカテゴリ">'
                        + tags.map(function (tag) {
                            return '<li class="menu-price-tag"><span class="menu-price-tag-label">'
                                + escapeHtml(tag)
                                + '</span></li>';
                        }).join('')
                        + '</ul>';
                }

                let priceHtml = '';
                if (menu.isInquiryPrice && menu.price) {
                    priceHtml = '<span class="menu-price-inquiry">' + escapeHtml(menu.price) + '</span>';
                } else if (menu.price) {
                    priceHtml = '<span class="menu-price-value">' + escapeHtml(menu.price) + '</span>';
                }

                const descHtml = menu.description
                    ? '<p class="menu-price-desc">' + escapeHtml(menu.description) + '</p>'
                    : '';

                return '<li class="menu-price-item content-modal__menu-item">'
                    + tagsHtml
                    + '<div class="menu-price-row content-modal__menu-row">'
                    + '<span class="menu-price-name">' + escapeHtml(menu.name) + '</span>'
                    + priceHtml
                    + '</div>'
                    + descHtml
                    + '</li>';
            }

            function renderCategoryBlock(category, options) {
                const showHeading = !options || options.showHeading !== false;
                const menus = Array.isArray(category.menus) ? category.menus : [];
                let headingHtml = '';
                if (showHeading) {
                    headingHtml = '<header class="content-modal__menu-heading">'
                        + (category.englishName
                            ? '<p class="content-modal__menu-heading-en">' + escapeHtml(category.englishName) + '</p>'
                            : '')
                        + '<h3 class="content-modal__menu-heading-ja">' + escapeHtml(category.name) + '</h3>'
                        + '</header>';
                }

                return '<section class="content-modal__menu-block">'
                    + headingHtml
                    + '<ul class="menu-price-list content-modal__menu-list">'
                    + menus.map(renderMenuItem).join('')
                    + '</ul>'
                    + '</section>';
            }

            function renderView(view, categoryId) {
                if (!rootEl || !titleEl) return;

                if (view === 'all') {
                    if (eyebrowEl) {
                        eyebrowEl.hidden = false;
                        eyebrowEl.textContent = 'Menu';
                    }
                    titleEl.textContent = 'メニュー・料金';
                    rootEl.innerHTML = categories.map(function (category) {
                        return renderCategoryBlock(category, { showHeading: true });
                    }).join('');
                    return;
                }

                const category = categoryById(categoryId);
                if (!category) return;

                if (eyebrowEl) {
                    if (category.englishName) {
                        eyebrowEl.hidden = false;
                        eyebrowEl.textContent = category.englishName;
                    } else {
                        eyebrowEl.hidden = true;
                        eyebrowEl.textContent = '';
                    }
                }
                titleEl.textContent = category.name || '';
                rootEl.innerHTML = renderCategoryBlock(category, { showHeading: false });
            }

            function openModal(view, categoryId) {
                if (view === 'category' && !categoryById(categoryId)) return;
                if (view === 'all' && !categories.length) return;

                if (!isOpen) {
                    lastFocus = document.activeElement;
                    isOpen = true;
                    modalScroll?.lock(modal);
                    modal.hidden = false;
                    modal.setAttribute('aria-hidden', 'false');
                    modal.classList.add('is-open');
                }
                renderView(view, categoryId);
                if (scrollEl) {
                    scrollEl.scrollTop = 0;
                }
                window.setTimeout(function () {
                    modalScroll?.focusWithoutScroll(closeBtn || dialog);
                }, 0);
            }

            function closeModal() {
                if (!isOpen) return;
                isOpen = false;
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

            document.addEventListener('click', function (event) {
                if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }
                const trigger = event.target.closest('[data-menu-modal-trigger]');
                if (!trigger || !modal) return;

                const view = trigger.getAttribute('data-menu-view') || 'category';
                if (view === 'all') {
                    event.preventDefault();
                    openModal('all');
                    return;
                }

                const id = trigger.getAttribute('data-menu-category-id');
                if (!id || !categoryById(id)) return;
                event.preventDefault();
                openModal('category', id);
            });

            closeBtn?.addEventListener('click', closeModal);
            backdrop?.addEventListener('click', closeModal);
            document.addEventListener('keydown', function (event) {
                if (isOpen && event.key === 'Escape') {
                    event.preventDefault();
                    closeModal();
                }
            });
        })();
    </script>
@endif
