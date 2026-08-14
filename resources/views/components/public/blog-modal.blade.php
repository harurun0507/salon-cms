@props(['blogs'])

@php
    $blogSource = $blogs instanceof \Illuminate\Contracts\Pagination\Paginator
        ? $blogs->items()
        : $blogs;
    $modalItems = collect($blogSource)
        ->filter(fn ($blog) => $blog instanceof \App\Models\Blog)
        ->values()
        ->map(fn (\App\Models\Blog $blog) => $blog->toPublicModalData())
        ->all();
@endphp

@if(count($modalItems) > 0)
    <x-public.modal-scroll-lock />
    <div
        id="blog-modal"
        class="content-modal"
        hidden
        data-blog-modal
        role="dialog"
        aria-modal="true"
        aria-hidden="true"
        aria-labelledby="blog-modal-title"
    >
        <div class="content-modal__backdrop" data-blog-modal-backdrop></div>
        <div class="content-modal__dialog content-modal__dialog--blog" data-blog-modal-dialog tabindex="-1">
            <button type="button" class="content-modal__close" data-blog-modal-close aria-label="ブログを閉じる">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </button>

            <div class="content-modal__body content-modal__body--blog">
                <div class="content-modal__blog-cover">
                    <header class="content-modal__header">
                        <p class="content-modal__eyebrow">BLOG</p>
                        <time class="content-modal__date" data-blog-modal-date></time>
                        <h2 id="blog-modal-title" class="content-modal__title" data-blog-modal-title></h2>
                    </header>

                    <div class="content-modal__eyecatch" data-blog-modal-eyecatch hidden>
                        <img src="" alt="" data-blog-modal-eyecatch-image>
                    </div>
                </div>

                <div class="content-modal__blog-article" data-blog-modal-article>
                    <div class="content-modal__blog-article-head" aria-hidden="true">
                        <p class="content-modal__blog-article-label">ARTICLE</p>
                        <span class="content-modal__blog-article-rule"></span>
                    </div>
                    <div class="content-modal__body-text content-modal__body-text--blog" data-blog-modal-body></div>
                    <footer class="content-modal__blog-article-foot" aria-hidden="true">
                        <span class="content-modal__blog-article-meta" data-blog-modal-meta-date></span>
                        <span class="content-modal__blog-article-meta" data-blog-modal-meta-no></span>
                    </footer>
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" data-blog-modal-data>{!! json_encode(
        ['items' => $modalItems],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) !!}</script>

    <script>
        (function () {
            const modal = document.querySelector('[data-blog-modal]');
            const dataEl = document.querySelector('[data-blog-modal-data]');
            if (!modal || !dataEl) return;

            let payload = { items: [] };
            try { payload = JSON.parse(dataEl.textContent || '{}'); } catch (e) { return; }
            const items = Array.isArray(payload.items) ? payload.items : [];
            if (!items.length) return;

            const backdrop = modal.querySelector('[data-blog-modal-backdrop]');
            const dialog = modal.querySelector('[data-blog-modal-dialog]');
            const articleEl = modal.querySelector('[data-blog-modal-article]');
            const closeBtn = modal.querySelector('[data-blog-modal-close]');
            const dateEl = modal.querySelector('[data-blog-modal-date]');
            const titleEl = modal.querySelector('[data-blog-modal-title]');
            const eyeCatchRoot = modal.querySelector('[data-blog-modal-eyecatch]');
            const eyeCatchImg = modal.querySelector('[data-blog-modal-eyecatch-image]');
            const bodyEl = modal.querySelector('[data-blog-modal-body]');
            const metaDateEl = modal.querySelector('[data-blog-modal-meta-date]');
            const metaNoEl = modal.querySelector('[data-blog-modal-meta-no]');

            const modalScroll = window.SalonPublicModalScroll;
            let isOpen = false;
            let lastFocus = null;

            function itemById(id) {
                const numericId = Number(id);
                return items.findIndex(function (item) { return Number(item.id) === numericId; });
            }

            function padArticleNo(value) {
                const numeric = Number(value);
                if (!Number.isFinite(numeric) || numeric < 1) {
                    return 'No.01';
                }
                return 'No.' + String(Math.floor(numeric)).padStart(2, '0');
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function bodyParagraphs(text) {
                const raw = String(text || '').replace(/\r\n/g, '\n').trim();
                if (!raw) return [];

                let paragraphs = raw.split(/\n{2,}/).map(function (part) {
                    return part.trim();
                }).filter(Boolean);

                if (paragraphs.length === 1 && paragraphs[0].indexOf('\n') !== -1) {
                    paragraphs = paragraphs[0].split(/\n/).map(function (part) {
                        return part.trim();
                    }).filter(Boolean);
                }

                return paragraphs;
            }

            function renderBody(text) {
                if (!bodyEl) return;
                const paragraphs = bodyParagraphs(text);
                if (!paragraphs.length) {
                    bodyEl.innerHTML = '';
                    return;
                }

                bodyEl.innerHTML = paragraphs.map(function (paragraph, index) {
                    const className = index === 0
                        ? 'content-modal__body-p content-modal__body-p--lead'
                        : 'content-modal__body-p';
                    return '<p class="' + className + '">' + escapeHtml(paragraph) + '</p>';
                }).join('');
            }

            function renderItem(item, index) {
                if (dateEl) dateEl.textContent = item.date || '';
                if (titleEl) titleEl.textContent = item.title || '';
                renderBody(item.body || '');

                if (metaDateEl) {
                    metaDateEl.textContent = item.date || '';
                }
                if (metaNoEl) {
                    metaNoEl.textContent = padArticleNo((index >= 0 ? index : 0) + 1);
                }

                if (eyeCatchRoot && eyeCatchImg) {
                    if (item.eyeCatch) {
                        eyeCatchRoot.hidden = false;
                        eyeCatchImg.src = item.eyeCatch;
                        eyeCatchImg.alt = item.title || '';
                    } else {
                        eyeCatchRoot.hidden = true;
                        eyeCatchImg.removeAttribute('src');
                        eyeCatchImg.alt = '';
                    }
                }
            }

            function openModal(id) {
                const index = itemById(id);
                if (index < 0) return;
                const item = items[index];
                if (!isOpen) {
                    lastFocus = document.activeElement;
                    isOpen = true;
                    modalScroll?.lock(modal);
                    modal.hidden = false;
                    modal.setAttribute('aria-hidden', 'false');
                    modal.classList.add('is-open');
                }
                renderItem(item, index);
                if (articleEl) {
                    articleEl.scrollTop = 0;
                }
                if (dialog) {
                    dialog.scrollTop = 0;
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
                const trigger = event.target.closest('[data-blog-modal-trigger]');
                if (!trigger) return;
                const id = trigger.getAttribute('data-blog-id');
                if (!id || itemById(id) < 0) return;
                event.preventDefault();
                openModal(id);
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
