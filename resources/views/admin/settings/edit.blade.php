@extends('layouts.admin')

@section('heading', '基本情報')

@section('save-bar')
    <div class="flex min-w-0 flex-wrap items-center gap-3">
        <button
            type="button"
            class="admin-btn shadow-md shrink-0"
            data-admin-confirm-trigger
            data-confirm-form="settings-form"
            data-confirm-title="店舗情報保存の確認"
            data-confirm-message="店舗情報を保存します。&#10;よろしいですか？"
            data-confirm-note="店名、ロゴ、店舗情報など、現在入力されている内容が反映されます。"
            data-confirm-submit-label="保存する"
        >保存する</button>
        <p class="text-sm text-admin-muted">
            公開サイトに表示する店舗名・ロゴ・住所・営業時間などを設定します。
        </p>
    </div>

@endsection

@section('content')
    @php
        $displayType = old('shop_name_display_type', $setting->shop_name_display_type ?: \App\Models\SalonSetting::DISPLAY_TYPE_TEXT);
        $hasLogo = (bool) $setting->logo_image;
    @endphp

    
    <form id="settings-form" method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-2">
            {{-- 店舗表示 --}}
            <div class="admin-card space-y-5">
                <div>
                    <h2 class="text-base font-medium text-admin-text">店舗表示</h2>
                    <p class="mt-1 text-sm text-admin-muted">公開サイトのヘッダーに表示する店名・ロゴを設定します。</p>
                </div>

                <div>
                    <span class="admin-label" id="shop_name_display_type_label">店名の表示方法</span>
                    <div class="admin-segmented mt-1" role="radiogroup" aria-labelledby="shop_name_display_type_label">
                        <label class="admin-segmented-option">
                            <input type="radio" name="shop_name_display_type" value="text" class="admin-segmented-input" {{ $displayType === 'text' ? 'checked' : '' }}>
                            <span class="admin-segmented-face">
                                <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                    <path d="M3 4.5h10M8 4.5v7M5.5 12h5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span class="admin-segmented-text">文字で表示</span>
                            </span>
                        </label>
                        <label class="admin-segmented-option">
                            <input type="radio" name="shop_name_display_type" value="logo" class="admin-segmented-input" {{ $displayType === 'logo' ? 'checked' : '' }}>
                            <span class="admin-segmented-face">
                                <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                    <rect x="2.5" y="3.5" width="11" height="9" rx="1.5" stroke="currentColor" stroke-width="1.35"/>
                                    <circle cx="6" cy="7" r="1.1" fill="currentColor" opacity="0.75"/>
                                    <path d="M3.75 11.25 6.5 8.75 8.1 10.1 10.25 7.75 12.25 11.25" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span class="admin-segmented-text">ロゴ画像で表示</span>
                            </span>
                        </label>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">公開サイトのヘッダー左上の表示に反映されます。ロゴ未登録のまま「ロゴ画像で表示」を選んだ場合、公開画面では店名文字にフォールバックします。</p>
                    <p id="logo-missing-warning" class="mt-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 {{ ($displayType === 'logo' && ! $hasLogo) ? '' : 'hidden' }}">ロゴ画像が未登録です。登録するか、表示方法を「文字で表示」にしてください。</p>
                </div>

                <div>
                    <label for="shop_name" class="admin-label">店名</label>
                    <input type="text" name="shop_name" id="shop_name" value="{{ old('shop_name', $setting->shop_name) }}" required class="admin-input">
                    <p class="mt-1 text-xs text-gray-500">ロゴ表示時も必須です（alt・代替表示に使用します）。</p>
                </div>

                <div>
                    <label for="logo_alt_text" class="admin-label">ロゴ altテキスト（任意）</label>
                    <input type="text" name="logo_alt_text" id="logo_alt_text" value="{{ old('logo_alt_text', $setting->logo_alt_text) }}" maxlength="255" class="admin-input" placeholder="未入力時は店名を使用">
                </div>

                <div>
                    <span class="admin-label">ロゴ画像</span>
                    <div class="mt-2 flex flex-wrap items-start gap-3">
                        <div class="min-w-0 flex-1">
                            <div
                                id="logo-image-dropzone"
                                data-logo-dropzone
                                class="banner-dropzone cursor-pointer {{ $hasLogo ? 'overflow-hidden rounded-lg' : 'is-empty' }}"
                            >
                                <div data-logo-preview class="{{ $hasLogo ? '' : 'hidden' }} flex items-center justify-center bg-white p-4">
                                    @if($hasLogo)
                                        <img
                                            id="logo-image-preview"
                                            src="{{ asset('storage/'.$setting->logo_image) }}"
                                            alt="{{ $setting->logoAlt() }}"
                                            class="h-16 w-auto max-w-full object-contain"
                                            data-logo-image
                                        >
                                    @endif
                                </div>
                                <div
                                    data-logo-placeholder
                                    class="banner-dropzone-placeholder {{ $hasLogo ? 'hidden' : '' }} min-h-[7.5rem] flex-col items-center justify-center px-4 text-center"
                                >
                                    <div class="banner-dropzone-main">
                                        <svg class="banner-dropzone-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <rect x="3.5" y="5.5" width="17" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                            <circle cx="9" cy="10.5" r="1.5" fill="currentColor" opacity="0.7"/>
                                            <path d="M5.5 16.5l4-3.5 2.5 2 3.5-3.5 3 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <p class="banner-dropzone-text text-sm text-gray-700">ロゴ画像をドラッグ＆ドロップ、またはクリックして選択</p>
                                    </div>
                                    <p class="banner-dropzone-hint mt-2 text-xs text-gray-500">
                                        JPEG・PNG・WebP、5MBまで。<br>
                                        透過PNG対応。周囲の余白を切り取った横長ロゴを推奨します。
                                    </p>
                                </div>
                                <p class="banner-dropzone-drag-message" aria-hidden="true">ここにロゴ画像をドロップしてください</p>
                            </div>
                            <p id="logo-image-filename" class="mt-2 hidden text-sm text-gray-600"></p>
                            <p class="mt-1 text-xs text-admin-muted {{ $hasLogo ? '' : 'hidden' }}" data-logo-replace-hint>クリックまたは DnD でロゴを変更できます</p>
                            <p id="logo-image-error" class="mt-2 hidden text-sm text-red-600" role="alert"></p>
                        </div>
                        @if($hasLogo)
                            <x-admin.delete-button
                                form="logo-delete-form"
                                message="ロゴ画像を削除しますか？"
                            >削除</x-admin.delete-button>
                        @endif
                    </div>
                    <input type="file" name="logo_image" id="logo_image" accept="image/jpeg,image/png,image/webp" class="hidden">
                </div>
            </div>

            {{-- 店舗情報 --}}
            <div class="admin-card space-y-5">
                <div>
                    <h2 class="text-base font-medium text-admin-text">店舗情報</h2>
                    <p class="mt-1 text-sm text-admin-muted">住所、営業時間、定休日、電話番号を設定します。</p>
                </div>
                <div>
                    <label for="address" class="admin-label">住所</label>
                    <input type="text" name="address" id="address" value="{{ old('address', $setting->address) }}" class="admin-input">
                </div>
                <div>
                    <label for="business_hours" class="admin-label">営業時間</label>
                    <textarea name="business_hours" id="business_hours" rows="4" class="admin-input">{{ old('business_hours', $setting->business_hours) }}</textarea>
                </div>
                <div>
                    <label for="closed_days" class="admin-label">定休日</label>
                    <input type="text" name="closed_days" id="closed_days" value="{{ old('closed_days', $setting->closed_days) }}" class="admin-input">
                </div>
                <div>
                    <label for="phone" class="admin-label">電話番号</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $setting->phone) }}" class="admin-input">
                </div>
            </div>
        </div>

        {{-- アクセス --}}
        <div class="admin-card space-y-5">
            <div>
                <h2 class="text-base font-medium text-admin-text">アクセス</h2>
                <p class="mt-1 text-sm text-admin-muted">Googleマップへのリンクと埋め込み表示を設定します。</p>
            </div>
            <div>
                <label for="google_map_url" class="admin-label">Googleマップ リンクURL</label>
                <input type="url" name="google_map_url" id="google_map_url" value="{{ old('google_map_url', $setting->google_map_url) }}" class="admin-input" placeholder="https://maps.app.goo.gl/...">
                <p class="mt-1 text-xs text-gray-500">Googleマップの「共有」から取得したURLを入力してください。「Googleマップで開く」ボタンに使用されます。</p>
            </div>
            <div>
                <label for="google_map_embed_url" class="admin-label">Googleマップ 埋め込みURL</label>
                <textarea name="google_map_embed_url" id="google_map_embed_url" rows="5" class="admin-input" placeholder="https://www.google.com/maps/embed?pb=...">{{ old('google_map_embed_url', $setting->google_map_embed_url) }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Googleマップの「地図を埋め込む」から取得したiframeタグ、または埋め込みURLを入力してください。公開サイトの地図表示に使用されます。</p>
            </div>
        </div>
    </form>

    @if($hasLogo)
        <form id="logo-delete-form" method="POST" action="{{ route('admin.settings.logo.destroy') }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endif

    <script>
        (function () {
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            const maxSize = 5 * 1024 * 1024;
            const hasExistingLogo = @json($hasLogo);
            const logoErrorEl = document.getElementById('logo-image-error');
            const logoInput = document.getElementById('logo_image');
            const logoDropzone = document.getElementById('logo-image-dropzone');
            const logoPreview = logoDropzone?.querySelector('[data-logo-preview]');
            const logoPlaceholder = logoDropzone?.querySelector('[data-logo-placeholder]');
            const logoFilenameEl = document.getElementById('logo-image-filename');
            const logoWarning = document.getElementById('logo-missing-warning');
            const logoReplaceHint = document.querySelector('[data-logo-replace-hint]');
            let logoSelected = false;

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

            function updateLogoWarning() {
                const type = document.querySelector('input[name="shop_name_display_type"]:checked')?.value;
                const missing = type === 'logo' && !hasExistingLogo && !logoSelected;
                logoWarning?.classList.toggle('hidden', !missing);
            }

            function clearDropzoneDragState() {
                if (!logoDropzone) {
                    return;
                }
                logoDropzone._logoDragCounter = 0;
                logoDropzone.classList.remove('is-drag-active');
            }

            function showLogoPreview(file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    let img = document.getElementById('logo-image-preview');
                    if (!img && logoPreview) {
                        img = document.createElement('img');
                        img.id = 'logo-image-preview';
                        img.className = 'h-16 w-auto max-w-full object-contain';
                        img.setAttribute('data-logo-image', '');
                        img.alt = '';
                        logoPreview.appendChild(img);
                    }
                    if (img) {
                        img.src = e.target.result;
                        img.alt = '';
                    }
                    logoPlaceholder?.classList.add('hidden');
                    logoPreview?.classList.remove('hidden');
                    logoDropzone?.classList.remove('is-empty');
                    logoDropzone?.classList.add('overflow-hidden', 'rounded-lg');
                    logoReplaceHint?.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }

            function handleLogoFile(file) {
                clearImageError(logoErrorEl);

                if (!file || !isValid(file, logoErrorEl)) {
                    logoInput.value = '';
                    logoSelected = false;
                    logoFilenameEl.textContent = '';
                    logoFilenameEl.classList.add('hidden');
                    updateLogoWarning();
                    return;
                }

                const dt = new DataTransfer();
                dt.items.add(file);
                logoInput.files = dt.files;
                logoSelected = true;
                logoFilenameEl.textContent = '選択中: ' + file.name;
                logoFilenameEl.classList.remove('hidden');
                showLogoPreview(file);
                updateLogoWarning();
            }

            logoDropzone?.addEventListener('click', function () {
                logoInput.click();
            });
            logoInput?.addEventListener('change', function () {
                handleLogoFile(logoInput.files[0]);
            });
            logoDropzone?.addEventListener('dragenter', function (e) {
                e.preventDefault();
                e.stopPropagation();
                logoDropzone._logoDragCounter = (logoDropzone._logoDragCounter || 0) + 1;
                logoDropzone.classList.add('is-drag-active');
            });
            logoDropzone?.addEventListener('dragover', function (e) {
                e.preventDefault();
                e.stopPropagation();
                logoDropzone.classList.add('is-drag-active');
            });
            logoDropzone?.addEventListener('dragleave', function (e) {
                e.preventDefault();
                e.stopPropagation();
                logoDropzone._logoDragCounter = Math.max(0, (logoDropzone._logoDragCounter || 0) - 1);
                if (logoDropzone._logoDragCounter === 0) {
                    logoDropzone.classList.remove('is-drag-active');
                }
            });
            logoDropzone?.addEventListener('drop', function (e) {
                e.preventDefault();
                e.stopPropagation();
                clearDropzoneDragState();
                handleLogoFile(e.dataTransfer.files[0]);
            });
            document.querySelectorAll('input[name="shop_name_display_type"]').forEach(function (radio) {
                radio.addEventListener('change', updateLogoWarning);
            });
        })();
    </script>
@endsection
