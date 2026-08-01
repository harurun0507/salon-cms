@extends('layouts.admin')

@section('heading', '店舗情報管理')

@section('content')
    @php
        $heroImages = $setting->heroImages;
        $heroCount = $heroImages->count();
        $maxHeroImages = \App\Models\HeroImage::MAX_COUNT;
    @endphp

    <div class="admin-card max-w-3xl space-y-5">
        <div class="space-y-4 rounded-lg border border-gray-200 bg-gray-50/50 p-4">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <label class="admin-label mb-0">メインビジュアル画像</label>
                <p class="text-xs text-gray-500">登録数: {{ $heroCount }} / {{ $maxHeroImages }}枚</p>
            </div>
            <p class="text-xs text-gray-500">表示順・公開状態・altテキストは下の「保存する」でまとめて反映されます。altテキストの入力を推奨します（未入力も可）。</p>

            @if($heroImages->isEmpty())
                <p class="text-sm text-gray-500">現在登録されている画像はありません</p>
            @else
                <div class="space-y-4">
                    @foreach($heroImages as $index => $image)
                        @php
                            $publishedOld = old('hero_images.'.$image->id.'.is_published', $image->is_published ? '1' : '0');
                            $isPublished = in_array((string) $publishedOld, ['1', 'true', 'on'], true);
                        @endphp
                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            <p class="mb-3 text-sm font-medium text-gray-800">画像{{ $index + 1 }}</p>
                            <div class="mb-4">
                                <img src="{{ asset('storage/'.$image->image_path) }}" alt="{{ old('hero_images.'.$image->id.'.alt_text', $image->alt_text) }}" class="h-40 w-full max-w-xl rounded object-cover">
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="hero_sort_{{ $image->id }}" class="admin-label">表示順</label>
                                    <input type="number" name="hero_images[{{ $image->id }}][sort_order]" id="hero_sort_{{ $image->id }}" form="settings-form" value="{{ old('hero_images.'.$image->id.'.sort_order', $image->sort_order) }}" min="0" max="9999" required class="admin-input">
                                </div>
                                <div>
                                    <span class="admin-label">公開</span>
                                    <label class="mt-2 flex items-center gap-2 text-sm text-gray-700">
                                        <input type="hidden" name="hero_images[{{ $image->id }}][is_published]" form="settings-form" value="0">
                                        <input type="checkbox" name="hero_images[{{ $image->id }}][is_published]" form="settings-form" value="1" {{ $isPublished ? 'checked' : '' }}>
                                        公開する（ON）
                                    </label>
                                </div>
                            </div>
                            <div class="mt-4">
                                <label for="hero_alt_{{ $image->id }}" class="admin-label">altテキスト</label>
                                <input type="text" name="hero_images[{{ $image->id }}][alt_text]" id="hero_alt_{{ $image->id }}" form="settings-form" value="{{ old('hero_images.'.$image->id.'.alt_text', $image->alt_text) }}" maxlength="255" class="admin-input" placeholder="例: サロン内観のメインビジュアル">
                                <p class="mt-1 text-xs text-gray-500">検索・アクセシビリティ向上のため入力を推奨します。未入力も保存できます。</p>
                            </div>
                            <div class="mt-4">
                                <x-admin.action-group class="justify-start">
                                    <x-admin.delete-button
                                        :action="route('admin.settings.hero-images.destroy', $image)"
                                        message="メインビジュアル画像を削除しますか？"
                                    />
                                </x-admin.action-group>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="border-t border-gray-200 pt-4">
                <p class="admin-label mb-2">新規画像を追加</p>
                <p id="hero-image-filename" class="mb-2 hidden text-sm text-gray-600"></p>
                <div id="new-hero-preview-wrap" class="mb-3 hidden grid grid-cols-2 gap-2 md:grid-cols-3"></div>
                <div id="hero-image-dropzone"
                     class="cursor-pointer rounded-lg border-2 border-dashed border-gray-300 bg-white px-4 py-8 text-center transition hover:border-gray-400 hover:bg-gray-50">
                    <p class="text-sm text-gray-700">ここに画像をドラッグ＆ドロップ、またはクリックして選択</p>
                    <p class="mt-2 text-xs text-gray-500">JPEG / PNG / WebP、5MBまで・複数選択可（合計最大{{ $maxHeroImages }}枚）</p>
                </div>
                <p id="hero-image-error" class="mt-2 hidden text-sm text-red-600" role="alert"></p>
                <input type="file" name="new_hero_images[]" id="new_hero_images" form="settings-form" accept="image/jpeg,image/png,image/webp" class="hidden" multiple>
            </div>
        </div>

        <form id="settings-form" method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf @method('PUT')

            @php
                $displayType = old('shop_name_display_type', $setting->shop_name_display_type ?: \App\Models\SalonSetting::DISPLAY_TYPE_TEXT);
            @endphp

            <div class="space-y-4 rounded-lg border border-gray-200 bg-gray-50/50 p-4">
                <div>
                    <span class="admin-label">店名の表示方法</span>
                    <div class="mt-2 flex flex-wrap gap-4 text-sm text-gray-700">
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="shop_name_display_type" value="text" {{ $displayType === 'text' ? 'checked' : '' }}>
                            文字で表示
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="shop_name_display_type" value="logo" {{ $displayType === 'logo' ? 'checked' : '' }}>
                            ロゴ画像で表示
                        </label>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">公開サイトのヘッダー左上の表示に反映されます。ロゴ未登録のまま「ロゴ画像で表示」を選んだ場合、公開画面では店名文字にフォールバックします。</p>
                    <p id="logo-missing-warning" class="mt-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 {{ ($displayType === 'logo' && ! $setting->logo_image) ? '' : 'hidden' }}">ロゴ画像が未登録です。登録するか、表示方法を「文字で表示」にしてください。</p>
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
                    <div id="logo-image-preview-wrap" class="mt-2">
                        @if($setting->logo_image)
                            <img id="logo-image-preview" src="{{ asset('storage/'.$setting->logo_image) }}" alt="{{ $setting->logoAlt() }}" class="h-16 w-auto max-w-xs rounded border border-gray-200 bg-white object-contain p-2">
                        @else
                            <p id="logo-image-empty" class="text-sm text-gray-500">現在登録されているロゴ画像はありません</p>
                        @endif
                    </div>
                    <p id="logo-image-filename" class="mt-2 hidden text-sm text-gray-600"></p>
                    <div id="logo-image-dropzone"
                         class="mt-3 cursor-pointer rounded-lg border-2 border-dashed border-gray-300 bg-white px-4 py-6 text-center transition hover:border-gray-400 hover:bg-gray-50">
                        <p class="text-sm text-gray-700">ここにロゴ画像をドラッグ＆ドロップ、またはクリックして選択</p>
                        <p class="mt-2 text-xs text-gray-500">JPEG / PNG / WebP、5MBまで（透過PNG対応）。周囲の余白を切り取った横長ロゴの方がヘッダーで大きく見えます。</p>
                    </div>
                    <p id="logo-image-error" class="mt-2 hidden text-sm text-red-600" role="alert"></p>
                    <input type="file" name="logo_image" id="logo_image" accept="image/jpeg,image/png,image/webp" class="hidden">
                    @if($setting->logo_image)
                        <div class="mt-3">
                            <x-admin.action-group class="justify-start">
                                <x-admin.delete-button
                                    form="logo-delete-form"
                                    message="ロゴ画像を削除しますか？"
                                >削除</x-admin.delete-button>
                            </x-admin.action-group>
                        </div>
                    @endif
                </div>
            </div>

            <div>
                <label for="hero_label" class="admin-label">ヒーロー英字ラベル（トップページ）</label>
                <input type="text" name="hero_label" id="hero_label" value="{{ old('hero_label', $setting->hero_label) }}" class="admin-input" placeholder="Personal Hair Salon">
                <p class="mt-1 text-xs text-gray-500">メインビジュアル上部の小さい英字テキスト。未入力時は表示しません。</p>
            </div>
            <div>
                <label for="hero_title" class="admin-label">メインコピー（トップページ）</label>
                <textarea name="hero_title" id="hero_title" rows="3" class="admin-input" placeholder="あなたらしさに、 / 少しだけ今っぽさを。">{{ old('hero_title', $setting->hero_title) }}</textarea>
                <p class="mt-1 text-xs text-gray-500">改行はトップページで反映されます。未入力時は表示しません。</p>
            </div>
            <div>
                <label for="concept_title" class="admin-label">コンセプト見出し（トップページ）</label>
                <input type="text" name="concept_title" id="concept_title" value="{{ old('concept_title', $setting->concept_title) }}" class="admin-input" placeholder="ナチュラルに、自分らしく。">
            </div>
            <div>
                <label for="concept" class="admin-label">コンセプト文（トップページ）</label>
                <textarea name="concept" id="concept" rows="5" class="admin-input">{{ old('concept', $setting->concept) }}</textarea>
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
            <div>
                <label for="google_map_url" class="admin-label">Google Map リンクURL</label>
                <input type="url" name="google_map_url" id="google_map_url" value="{{ old('google_map_url', $setting->google_map_url) }}" class="admin-input" placeholder="https://maps.app.goo.gl/...">
                <p class="mt-1 text-xs text-gray-500">「Googleマップで開く」ボタン用の通常共有URL</p>
            </div>
            <div>
                <label for="google_map_embed_url" class="admin-label">Google Map 埋め込みURL</label>
                <textarea name="google_map_embed_url" id="google_map_embed_url" rows="3" class="admin-input" placeholder="https://www.google.com/maps/embed?pb=...">{{ old('google_map_embed_url', $setting->google_map_embed_url) }}</textarea>
                <p class="mt-1 text-xs text-gray-500">iframe 用（埋め込みURL、または iframe タグ全体を貼り付け可）</p>
            </div>
            <div>
                <label for="instagram_url" class="admin-label">Instagram URL</label>
                <input type="url" name="instagram_url" id="instagram_url" value="{{ old('instagram_url', $setting->instagram_url) }}" class="admin-input">
            </div>
            <div>
                <label for="hot_pepper_url" class="admin-label">Hot Pepper 予約URL</label>
                <input type="url" name="hot_pepper_url" id="hot_pepper_url" value="{{ old('hot_pepper_url', $setting->hot_pepper_url) }}" class="admin-input">
            </div>

            <button
                type="button"
                class="admin-btn"
                data-admin-confirm-trigger
                data-confirm-form="settings-form"
                data-confirm-title="店舗情報保存の確認"
                data-confirm-message="店舗情報を保存します。&#10;よろしいですか？"
                data-confirm-note="店名、画像、店舗情報など、現在入力されている内容が反映されます。"
                data-confirm-submit-label="保存する"
            >保存する</button>
        </form>

        @if($setting->logo_image)
            <form id="logo-delete-form" method="POST" action="{{ route('admin.settings.logo.destroy') }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endif
    </div>

    <script>
        (function () {
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            const maxSize = 5 * 1024 * 1024;
            const hasExistingLogo = @json((bool) $setting->logo_image);

            // Hero multi-upload
            const input = document.getElementById('new_hero_images');
            const dropzone = document.getElementById('hero-image-dropzone');
            const previewWrap = document.getElementById('new-hero-preview-wrap');
            const filenameEl = document.getElementById('hero-image-filename');
            const maxTotal = {{ $maxHeroImages }};
            const currentCount = {{ $heroCount }};
            const heroErrorEl = document.getElementById('hero-image-error');
            const logoErrorEl = document.getElementById('logo-image-error');

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

            function setFiles(files) {
                const dt = new DataTransfer();
                Array.from(files).forEach(function (file) {
                    dt.items.add(file);
                });
                input.files = dt.files;
            }

            function showPreviews(files) {
                previewWrap.innerHTML = '';
                if (!files.length) {
                    previewWrap.classList.add('hidden');
                    filenameEl.textContent = '';
                    filenameEl.classList.add('hidden');
                    return;
                }

                previewWrap.classList.remove('hidden');
                filenameEl.textContent = '選択中: ' + Array.from(files).map(function (f) { return f.name; }).join(', ');
                filenameEl.classList.remove('hidden');

                Array.from(files).forEach(function (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = '';
                        img.className = 'h-28 w-full rounded object-cover';
                        previewWrap.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                });
            }

            function handleFiles(fileList) {
                clearImageError(heroErrorEl);
                const raw = Array.from(fileList || []);
                const files = [];

                for (let i = 0; i < raw.length; i++) {
                    if (!isValid(raw[i], heroErrorEl)) {
                        input.value = '';
                        showPreviews([]);
                        return;
                    }
                    files.push(raw[i]);
                }

                if (!files.length) {
                    input.value = '';
                    showPreviews([]);
                    return;
                }

                if (currentCount + files.length > maxTotal) {
                    showImageError(
                        heroErrorEl,
                        'メインビジュアル画像は最大' + maxTotal + '枚まで登録できます。'
                    );
                    input.value = '';
                    showPreviews([]);
                    return;
                }

                setFiles(files);
                showPreviews(files);
            }

            dropzone.addEventListener('click', function () {
                input.click();
            });

            input.addEventListener('change', function () {
                handleFiles(input.files);
            });

            ['dragenter', 'dragover'].forEach(function (eventName) {
                dropzone.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    dropzone.classList.add('border-gray-400', 'bg-gray-50');
                });
            });

            ['dragleave', 'drop'].forEach(function (eventName) {
                dropzone.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    dropzone.classList.remove('border-gray-400', 'bg-gray-50');
                });
            });

            dropzone.addEventListener('drop', function (e) {
                handleFiles(e.dataTransfer.files);
            });

            // Logo upload
            const logoInput = document.getElementById('logo_image');
            const logoDropzone = document.getElementById('logo-image-dropzone');
            const logoPreviewWrap = document.getElementById('logo-image-preview-wrap');
            const logoFilenameEl = document.getElementById('logo-image-filename');
            const logoWarning = document.getElementById('logo-missing-warning');
            let logoSelected = false;

            function updateLogoWarning() {
                const type = document.querySelector('input[name="shop_name_display_type"]:checked')?.value;
                const missing = type === 'logo' && !hasExistingLogo && !logoSelected;
                logoWarning?.classList.toggle('hidden', !missing);
            }

            function showLogoPreview(file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    let img = document.getElementById('logo-image-preview');
                    const empty = document.getElementById('logo-image-empty');
                    if (empty) empty.remove();
                    if (!img) {
                        img = document.createElement('img');
                        img.id = 'logo-image-preview';
                        img.className = 'h-16 w-auto max-w-xs rounded border border-gray-200 bg-white object-contain p-2';
                        logoPreviewWrap.appendChild(img);
                    }
                    img.src = e.target.result;
                    img.alt = '';
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
            ['dragenter', 'dragover'].forEach(function (eventName) {
                logoDropzone?.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    logoDropzone.classList.add('border-gray-400', 'bg-gray-50');
                });
            });
            ['dragleave', 'drop'].forEach(function (eventName) {
                logoDropzone?.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    logoDropzone.classList.remove('border-gray-400', 'bg-gray-50');
                });
            });
            logoDropzone?.addEventListener('drop', function (e) {
                handleLogoFile(e.dataTransfer.files[0]);
            });
            document.querySelectorAll('input[name="shop_name_display_type"]').forEach(function (radio) {
                radio.addEventListener('change', updateLogoWarning);
            });
        })();
    </script>
@endsection
