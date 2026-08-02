@php
    $maxHeroImages = $maxHeroImages ?? \App\Models\HeroImage::MAX_COUNT;
    $existingMaxSort = (int) ($heroImages->max('sort_order') ?? 0);
@endphp

<script>
    (function () {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        const maxSize = 5 * 1024 * 1024;
        const maxTotal = {{ $maxHeroImages }};
        const existingMaxSort = {{ $existingMaxSort }};
        const heroErrorEl = document.getElementById('hero-image-error');
        const heroList = document.getElementById('hero-images-list');
        const heroAddCard = document.getElementById('hero-image-add-card');
        const heroCountEl = document.getElementById('hero-image-count');
        const heroAddBtn = document.getElementById('hero-image-add-card-btn');
        let heroSlotSeq = 0;

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

        function heroBlockCount() {
            return heroList ? heroList.querySelectorAll('.hero-image-block').length : 0;
        }

        function nextHeroSortOrder() {
            let maxSort = existingMaxSort;
            heroList?.querySelectorAll('input[name*="[sort_order]"]').forEach(function (input) {
                const value = parseInt(input.value, 10);
                if (!Number.isNaN(value)) {
                    maxSort = Math.max(maxSort, value);
                }
            });
            return maxSort + 1;
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

        function renumberHeroBlocks() {
            const blocks = heroList?.querySelectorAll('.hero-image-block') || [];
            blocks.forEach(function (block, index) {
                const label = block.querySelector('.hero-image-label');
                if (label) {
                    label.textContent = '画像' + (index + 1);
                }
            });
            syncHeroAddUi();
        }

        function setSlotPreview(block, file) {
            const dropzone = block.querySelector('[data-hero-slot-dropzone]');
            const preview = block.querySelector('[data-hero-slot-preview]');
            const placeholder = block.querySelector('[data-hero-slot-placeholder]');
            if (!preview || !placeholder) {
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                let img = preview.querySelector('img');
                if (!img) {
                    img = document.createElement('img');
                    img.className = 'h-40 w-full max-w-xl rounded object-cover';
                    img.alt = '';
                    preview.appendChild(img);
                }
                img.src = e.target.result;
                placeholder.classList.add('hidden');
                preview.classList.remove('hidden');
                if (dropzone) {
                    dropzone.className = 'cursor-pointer';
                }
            };
            reader.readAsDataURL(file);
        }

        function bindHeroSlot(block) {
            const dropzone = block.querySelector('[data-hero-slot-dropzone]');
            const input = block.querySelector('input[type="file"]');
            const removeBtn = block.querySelector('[data-hero-slot-remove]');

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
                setSlotPreview(block, file);
            }

            dropzone?.addEventListener('click', function () {
                input?.click();
            });
            input?.addEventListener('change', function () {
                applyFile(input.files[0]);
            });
            ['dragenter', 'dragover'].forEach(function (eventName) {
                dropzone?.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    dropzone.classList.add('border-gray-400', 'bg-gray-50');
                });
            });
            ['dragleave', 'drop'].forEach(function (eventName) {
                dropzone?.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    dropzone.classList.remove('border-gray-400', 'bg-gray-50');
                });
            });
            dropzone?.addEventListener('drop', function (e) {
                applyFile(e.dataTransfer.files[0]);
            });
            removeBtn?.addEventListener('click', function () {
                block.remove();
                renumberHeroBlocks();
                clearImageError(heroErrorEl);
            });
        }

        function createHeroSlot() {
            if (heroBlockCount() >= maxTotal) {
                showImageError(heroErrorEl, 'メインビジュアル画像は最大' + maxTotal + '枚まで登録できます。');
                return;
            }

            heroSlotSeq += 1;
            const key = 'new_' + heroSlotSeq;
            const sortOrder = nextHeroSortOrder();
            const block = document.createElement('div');
            block.className = 'hero-image-block rounded-lg border border-gray-200 bg-white p-4';
            block.dataset.heroNew = '1';
            // Future: append catch_copy / link_url inputs in this template.
            block.innerHTML =
                '<div class="mb-3 flex items-center justify-between gap-3">' +
                    '<p class="hero-image-label text-sm font-medium text-gray-800">画像</p>' +
                    '<button type="button" class="admin-icon-btn admin-icon-btn-delete" data-hero-slot-remove aria-label="削除" title="削除">' +
                        '<span aria-hidden="true">&times;</span>' +
                    '</button>' +
                '</div>' +
                '<div class="mb-4">' +
                    '<div data-hero-slot-dropzone class="cursor-pointer rounded-lg border-2 border-dashed border-gray-300 bg-white px-4 py-8 text-center transition hover:border-gray-400 hover:bg-gray-50">' +
                        '<div data-hero-slot-placeholder>' +
                            '<p class="text-sm text-gray-700">ここに画像をドラッグ＆ドロップ、またはクリックして選択</p>' +
                            '<p class="mt-2 text-xs text-gray-500">JPEG / PNG / WebP、5MBまで</p>' +
                        '</div>' +
                        '<div data-hero-slot-preview class="hidden"></div>' +
                    '</div>' +
                    '<input type="file" name="new_hero_images[' + key + ']" accept="image/jpeg,image/png,image/webp" class="hidden">' +
                '</div>' +
                '<div class="grid gap-4 sm:grid-cols-2">' +
                    '<div>' +
                        '<label for="hero_new_sort_' + key + '" class="admin-label">表示順</label>' +
                        '<input type="number" name="new_hero_meta[' + key + '][sort_order]" id="hero_new_sort_' + key + '" value="' + sortOrder + '" min="0" max="9999" required class="admin-input">' +
                    '</div>' +
                    '<div>' +
                        '<span class="admin-label">公開</span>' +
                        '<label class="menu-published-control mt-2" data-published-control>' +
                            '<input type="hidden" name="new_hero_meta[' + key + '][is_published]" value="0">' +
                            '<input type="checkbox" name="new_hero_meta[' + key + '][is_published]" value="1" class="menu-published-checkbox" data-published-checkbox checked aria-label="公開状態">' +
                            '<span class="menu-published-label is-published" data-published-label>' +
                                '<span class="menu-published-dot" data-published-dot aria-hidden="true"></span>' +
                                '<span data-published-text>公開</span>' +
                            '</span>' +
                        '</label>' +
                    '</div>' +
                '</div>' +
                '<div class="mt-4">' +
                    '<label for="hero_new_alt_' + key + '" class="admin-label">altテキスト</label>' +
                    '<input type="text" name="new_hero_meta[' + key + '][alt_text]" id="hero_new_alt_' + key + '" value="" maxlength="255" class="admin-input" placeholder="例: サロン内観のメインビジュアル">' +
                    '<p class="mt-1 text-xs text-gray-500">検索・アクセシビリティ向上のため入力を推奨します。未入力も保存できます。</p>' +
                '</div>';

            if (heroAddCard) {
                heroAddCard.before(block);
            } else {
                heroList?.appendChild(block);
            }
            bindHeroSlot(block);
            renumberHeroBlocks();
            clearImageError(heroErrorEl);
        }

        heroAddBtn?.addEventListener('click', createHeroSlot);

        function syncPublishedLabel(checkbox) {
            const control = checkbox.closest('[data-published-control]');
            if (!control) {
                return;
            }
            const label = control.querySelector('[data-published-label]');
            const text = control.querySelector('[data-published-text]');
            if (!label) {
                return;
            }
            const published = !!checkbox.checked;
            label.classList.toggle('is-published', published);
            label.classList.toggle('is-unpublished', !published);
            if (text) {
                text.textContent = published ? '公開' : '非公開';
            }
        }

        document.addEventListener('change', function (e) {
            const checkbox = e.target.closest('[data-published-checkbox]');
            if (checkbox) {
                syncPublishedLabel(checkbox);
            }
        });
    })();
</script>
