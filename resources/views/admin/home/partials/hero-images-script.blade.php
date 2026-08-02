@php
    $maxHeroImages = $maxHeroImages ?? \App\Models\HeroImage::MAX_COUNT;
@endphp

<style>
    .hero-drag-handle {
        display: inline-flex;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        width: 1.75rem;
        height: 2rem;
        color: rgba(115, 109, 101, 0.55);
        cursor: grab;
        touch-action: none;
        user-select: none;
        -webkit-user-select: none;
    }
    .hero-drag-handle:hover,
    .hero-drag-handle:focus-visible {
        color: #556344;
    }
    .hero-drag-handle:focus {
        outline: none;
    }
    .hero-drag-handle:focus-visible {
        box-shadow: inset 0 0 0 2px rgba(105, 122, 85, 0.35);
        border-radius: 0.25rem;
    }
    .hero-drag-handle:active,
    .hero-card.is-dragging .hero-drag-handle {
        cursor: grabbing;
    }
    .hero-card.is-dragging {
        opacity: 0.55;
    }
    .hero-card.is-drag-over {
        outline: 2px dashed rgba(105, 122, 85, 0.45);
        outline-offset: 2px;
    }
</style>

<script>
    (function () {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        const maxSize = 5 * 1024 * 1024;
        const maxTotal = {{ $maxHeroImages }};
        const heroErrorEl = document.getElementById('hero-image-error');
        const heroList = document.getElementById('hero-images-list');
        const heroAddCard = document.getElementById('hero-image-add-card');
        const heroCountEl = document.getElementById('hero-image-count');
        const heroAddBtn = document.getElementById('hero-image-add-card-btn');
        const deletedIdsWrap = document.getElementById('hero-deleted-ids');
        let heroSlotSeq = 0;
        let dragCard = null;

        const dropzoneMainHtml =
            '<div class="banner-dropzone-main">' +
                '<svg class="banner-dropzone-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
                    '<rect x="3.5" y="5.5" width="17" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>' +
                    '<circle cx="9" cy="10.5" r="1.5" fill="currentColor" opacity="0.7"/>' +
                    '<path d="M5.5 16.5l4-3.5 2.5 2 3.5-3.5 3 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>' +
                '</svg>' +
                '<p class="banner-dropzone-text text-sm text-gray-700">画像をドラッグ＆ドロップ、またはクリックして選択</p>' +
            '</div>';

        function clearImageError(errorEl) {
            if (!errorEl) {
                return;
            }
            errorEl.textContent = '';
            errorEl.classList.add('hidden');
        }

        function showImageError(errorEl, message) {
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.classList.remove('hidden');
            }
            if (typeof window.showToast === 'function') {
                window.showToast(message, 'error');
            }
        }

        function isValid(file, errorEl) {
            if (!allowedTypes.includes(file.type)) {
                showImageError(errorEl, 'JPEG / PNG / WebP形式の画像を選択してください。');
                return false;
            }
            if (file.size > maxSize) {
                showImageError(errorEl, '画像サイズは5MB以下にしてください。');
                return false;
            }
            return true;
        }

        function heroCards() {
            return heroList ? Array.from(heroList.querySelectorAll('[data-hero-card]')) : [];
        }

        function heroBlockCount() {
            return heroCards().length;
        }

        function fallbackHeading(index) {
            return 'メインビジュアル' + (index + 1);
        }

        function syncCardHeading(card, index) {
            const label = card.querySelector('[data-hero-card-title]');
            const input = card.querySelector('[data-hero-alt-input]');
            if (!label || !input) {
                return;
            }
            const value = (input.value || '').trim();
            const text = value !== '' ? value : fallbackHeading(index);
            label.textContent = text;
            label.setAttribute('title', text);
        }

        function syncAllHeadings() {
            heroCards().forEach(function (card, index) {
                syncCardHeading(card, index);
            });
        }

        function syncSortOrders() {
            heroCards().forEach(function (card, index) {
                const orderInput = card.querySelector('[data-hero-order]');
                if (orderInput) {
                    orderInput.value = String(index + 1);
                }
            });
            syncAllHeadings();
            syncHeroAddUi();
        }

        function syncHeroAddUi() {
            const count = heroBlockCount();
            if (heroCountEl) {
                heroCountEl.textContent = '登録数: ' + count + ' / ' + maxTotal + '枚';
            }
            const atMax = count >= maxTotal;
            heroAddCard?.classList.toggle('hidden', atMax);
            if (heroAddBtn) {
                heroAddBtn.disabled = atMax;
            }
        }

        function clearDropzoneDragState(dropzone) {
            if (!dropzone) {
                return;
            }
            dropzone._heroDragCounter = 0;
            dropzone.classList.remove('is-drag-active');
        }

        function setSlotPreview(card, file) {
            const dropzone = card.querySelector('[data-hero-slot-dropzone]');
            const preview = card.querySelector('[data-hero-slot-preview]');
            const placeholder = card.querySelector('[data-hero-slot-placeholder]');
            if (!preview || !placeholder) {
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                let img = preview.querySelector('img');
                if (!img) {
                    img = document.createElement('img');
                    img.className = 'aspect-[16/9] w-full object-cover';
                    img.alt = '';
                    preview.appendChild(img);
                }
                img.src = e.target.result;
                placeholder.classList.add('hidden');
                preview.classList.remove('hidden');
                if (dropzone) {
                    dropzone.classList.remove('is-empty');
                    dropzone.classList.add('overflow-hidden', 'rounded-lg');
                }
            };
            reader.readAsDataURL(file);
        }

        function markHeroBlockRemoved(card) {
            const existingId = card.getAttribute('data-hero-id');
            if (existingId && deletedIdsWrap) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'deleted_ids[]';
                hidden.value = existingId;
                deletedIdsWrap.appendChild(hidden);
            }
            card.remove();
            syncSortOrders();
            clearImageError(heroErrorEl);
        }

        function bindHeroCard(card) {
            const dropzone = card.querySelector('[data-hero-slot-dropzone]');
            const input = card.querySelector('input[type="file"]');
            const removeBtn = card.querySelector('[data-hero-remove]');
            const altInput = card.querySelector('[data-hero-alt-input]');

            if (dropzone) {
                dropzone._heroDragCounter = 0;
            }

            function applyFile(file) {
                clearImageError(heroErrorEl);
                if (!file || !isValid(file, heroErrorEl)) {
                    if (input) {
                        input.value = '';
                    }
                    return;
                }
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                setSlotPreview(card, file);
            }

            dropzone?.addEventListener('click', function () {
                input?.click();
            });
            input?.addEventListener('change', function () {
                applyFile(input.files[0]);
            });

            dropzone?.addEventListener('dragenter', function (e) {
                if (dragCard) {
                    return;
                }
                e.preventDefault();
                dropzone._heroDragCounter = (dropzone._heroDragCounter || 0) + 1;
                dropzone.classList.add('is-drag-active');
            });
            dropzone?.addEventListener('dragover', function (e) {
                if (dragCard) {
                    return;
                }
                e.preventDefault();
                dropzone.classList.add('is-drag-active');
            });
            dropzone?.addEventListener('dragleave', function (e) {
                if (dragCard) {
                    return;
                }
                e.preventDefault();
                dropzone._heroDragCounter = Math.max(0, (dropzone._heroDragCounter || 0) - 1);
                if (dropzone._heroDragCounter === 0) {
                    dropzone.classList.remove('is-drag-active');
                }
            });
            dropzone?.addEventListener('drop', function (e) {
                if (dragCard) {
                    return;
                }
                e.preventDefault();
                clearDropzoneDragState(dropzone);
                applyFile(e.dataTransfer.files[0]);
            });

            altInput?.addEventListener('input', function () {
                const cards = heroCards();
                syncCardHeading(card, cards.indexOf(card));
            });

            removeBtn?.addEventListener('click', function () {
                markHeroBlockRemoved(card);
            });
        }

        function createHeroSlot() {
            if (heroBlockCount() >= maxTotal) {
                showImageError(heroErrorEl, 'メインビジュアル画像は最大' + maxTotal + '枚まで登録できます。');
                return;
            }

            heroSlotSeq += 1;
            const key = 'new_' + heroSlotSeq;
            const order = heroBlockCount() + 1;
            const card = document.createElement('div');
            card.className = 'admin-card hero-card';
            card.setAttribute('data-hero-card', '');
            card.dataset.heroNew = '1';
            // Future: append catch_copy / link_url inputs in this template.
            card.innerHTML =
                '<div class="mb-3 flex items-center justify-between gap-3">' +
                    '<div class="flex min-w-0 items-center gap-2">' +
                        '<span class="hero-drag-handle" data-hero-drag-handle draggable="true" role="button" tabindex="0" aria-label="メインビジュアルを並び替え" title="ドラッグして並び替え">' +
                            '<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">' +
                                '<circle cx="7" cy="5" r="1.25"/><circle cx="13" cy="5" r="1.25"/>' +
                                '<circle cx="7" cy="10" r="1.25"/><circle cx="13" cy="10" r="1.25"/>' +
                                '<circle cx="7" cy="15" r="1.25"/><circle cx="13" cy="15" r="1.25"/>' +
                            '</svg>' +
                        '</span>' +
                        '<p class="banner-card-label truncate text-sm font-medium text-gray-800" data-hero-card-title title="' + fallbackHeading(order - 1) + '">' + fallbackHeading(order - 1) + '</p>' +
                    '</div>' +
                    '<button type="button" class="admin-icon-btn admin-icon-btn-delete" data-hero-remove aria-label="削除" title="削除">' +
                        '<span aria-hidden="true">&times;</span>' +
                    '</button>' +
                '</div>' +
                '<input type="hidden" name="new_hero_meta[' + key + '][sort_order]" value="' + order + '" data-hero-order>' +
                '<div class="mb-3">' +
                    '<div data-hero-slot-dropzone class="banner-dropzone is-empty cursor-pointer">' +
                        '<div data-hero-slot-placeholder class="banner-dropzone-placeholder">' +
                            dropzoneMainHtml +
                            '<p class="banner-dropzone-hint mt-2 text-xs text-gray-500">JPEG / PNG / WebP、5MBまで</p>' +
                        '</div>' +
                        '<div data-hero-slot-preview class="hidden"></div>' +
                        '<p class="banner-dropzone-drag-message" aria-hidden="true">ここに画像をドロップしてください</p>' +
                    '</div>' +
                    '<input type="file" name="new_hero_images[' + key + ']" accept="image/jpeg,image/png,image/webp" class="hidden">' +
                    '<p class="mt-1 hidden text-sm text-red-600" data-hero-image-error role="alert"></p>' +
                '</div>' +
                '<div class="space-y-3">' +
                    '<div>' +
                        '<label for="hero_new_alt_' + key + '" class="admin-label">altテキスト</label>' +
                        '<input type="text" name="new_hero_meta[' + key + '][alt_text]" id="hero_new_alt_' + key + '" value="" maxlength="255" class="admin-input" placeholder="例: サロン内観のメインビジュアル" data-hero-alt-input>' +
                        '<p class="mt-1 text-xs text-gray-500">検索・アクセシビリティ向上のため入力を推奨します。未入力も保存できます。</p>' +
                    '</div>' +
                    '<div>' +
                        '<span class="admin-label">公開</span>' +
                        '<div class="admin-segmented mt-1" role="radiogroup" aria-label="公開状態">' +
                            '<label class="admin-segmented-option">' +
                                '<input type="radio" name="new_hero_meta[' + key + '][is_published]" value="1" class="admin-segmented-input" checked>' +
                                '<span class="admin-segmented-face">' +
                                    '<svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">' +
                                        '<path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>' +
                                        '<circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.35"/>' +
                                    '</svg>' +
                                    '<span class="admin-segmented-text">公開</span>' +
                                '</span>' +
                            '</label>' +
                            '<label class="admin-segmented-option">' +
                                '<input type="radio" name="new_hero_meta[' + key + '][is_published]" value="0" class="admin-segmented-input">' +
                                '<span class="admin-segmented-face">' +
                                    '<svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">' +
                                        '<path d="M2 2.5 13.5 13.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>' +
                                        '<path d="M6.7 4.1A6.4 6.4 0 0 1 8 3.5c4 0 6.5 4.5 6.5 4.5a10.3 10.3 0 0 1-2.15 2.55M4.2 5.85A10.2 10.2 0 0 0 1.5 8S4 12.5 8 12.5c.7 0 1.35-.12 1.95-.34" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>' +
                                        '<path d="M6.65 7.1a2 2 0 0 0 2.35 2.35" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>' +
                                    '</svg>' +
                                    '<span class="admin-segmented-text">非公開</span>' +
                                '</span>' +
                            '</label>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            if (heroAddCard) {
                heroAddCard.before(card);
            } else {
                heroList?.appendChild(card);
            }
            bindHeroCard(card);
            syncSortOrders();
            clearImageError(heroErrorEl);
        }

        heroAddBtn?.addEventListener('click', createHeroSlot);

        if (heroList) {
            heroList.addEventListener('dragstart', function (e) {
                const handle = e.target.closest('[data-hero-drag-handle]');
                if (!handle || !heroList.contains(handle)) {
                    return;
                }
                const card = handle.closest('[data-hero-card]');
                if (!card) {
                    e.preventDefault();
                    return;
                }
                dragCard = card;
                card.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', card.getAttribute('data-hero-id') || 'new');
            });

            heroList.addEventListener('dragend', function () {
                if (dragCard) {
                    dragCard.classList.remove('is-dragging');
                }
                heroList.querySelectorAll('.is-drag-over').forEach(function (el) {
                    el.classList.remove('is-drag-over');
                });
                heroList.querySelectorAll('[data-hero-slot-dropzone]').forEach(clearDropzoneDragState);
                dragCard = null;
                syncSortOrders();
            });

            heroList.addEventListener('dragover', function (e) {
                if (!dragCard) {
                    return;
                }
                e.preventDefault();
                const over = e.target.closest('[data-hero-card]');
                if (!over || over === dragCard || !heroList.contains(over)) {
                    return;
                }
                heroList.querySelectorAll('.is-drag-over').forEach(function (el) {
                    if (el !== over) {
                        el.classList.remove('is-drag-over');
                    }
                });
                over.classList.add('is-drag-over');
                const rect = over.getBoundingClientRect();
                const before = (e.clientY - rect.top) < rect.height / 2;
                if (before) {
                    over.before(dragCard);
                } else {
                    over.after(dragCard);
                }
            });

            heroList.addEventListener('drop', function (e) {
                if (!dragCard) {
                    return;
                }
                e.preventDefault();
            });
        }

        heroCards().forEach(bindHeroCard);
        syncSortOrders();
    })();
</script>
