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
            {{-- 店舗情報 --}}
            <div class="admin-card space-y-5">
                <div>
                    <h2 class="text-base font-medium text-admin-text">店舗情報</h2>
                    <p class="mt-1 text-sm text-admin-muted">住所、営業時間、定休日、電話番号などの基本情報を設定します。</p>
                </div>
                <div>
                    <label for="address" class="admin-label">住所</label>
                    <input type="text" name="address" id="address" value="{{ old('address', $setting->address) }}" class="admin-input">
                </div>
                <div>
                    <label for="access_directions" class="admin-label">アクセス・道案内</label>
                    <textarea name="access_directions" id="access_directions" rows="5" class="admin-input">{{ old('access_directions', $setting->access_directions) }}</textarea>
                </div>
                <div>
                    <span class="admin-label">営業時間</span>
                    <div class="mt-2 space-y-3">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                            <span class="w-14 shrink-0 text-sm text-admin-text">平日</span>
                            <input
                                type="time"
                                name="weekday_open_time"
                                id="weekday_open_time"
                                value="{{ old('weekday_open_time', $setting->weekdayOpenTimeInputValue()) }}"
                                class="admin-input max-w-[9rem]"
                                aria-label="平日の開店時間"
                            >
                            <span class="text-sm text-admin-muted" aria-hidden="true">～</span>
                            <input
                                type="time"
                                name="weekday_close_time"
                                id="weekday_close_time"
                                value="{{ old('weekday_close_time', $setting->weekdayCloseTimeInputValue()) }}"
                                class="admin-input max-w-[9rem]"
                                aria-label="平日の閉店時間"
                            >
                        </div>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                            <span class="w-14 shrink-0 text-sm text-admin-text">土日祝</span>
                            <input
                                type="time"
                                name="weekend_open_time"
                                id="weekend_open_time"
                                value="{{ old('weekend_open_time', $setting->weekendOpenTimeInputValue()) }}"
                                class="admin-input max-w-[9rem]"
                                aria-label="土日祝の開店時間"
                            >
                            <span class="text-sm text-admin-muted" aria-hidden="true">～</span>
                            <input
                                type="time"
                                name="weekend_close_time"
                                id="weekend_close_time"
                                value="{{ old('weekend_close_time', $setting->weekendCloseTimeInputValue()) }}"
                                class="admin-input max-w-[9rem]"
                                aria-label="土日祝の閉店時間"
                            >
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-admin-muted">公開サイトには「平日 10:00 - 20:00」の形式で表示されます。</p>
                    @error('weekday_open_time')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('weekday_close_time')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('weekend_open_time')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('weekend_close_time')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                @php
                    $weekdayLabels = $weekdayLabels ?? \App\Models\SalonSetting::WEEKDAY_SHORT_LABELS;
                    $selectedClosedWeekdays = collect(old('closed_weekdays', $setting->closedWeekdayValues()))
                        ->map(fn ($v) => (int) $v)
                        ->all();
                    $selectedClosedNth = old('closed_nth');
                    if (! is_array($selectedClosedNth)) {
                        $selectedClosedNth = collect($setting->closedNthWeekdayRules())
                            ->map(fn (array $rule) => [
                                'week' => $rule['week'],
                                'weekday' => $rule['weekday'],
                            ])
                            ->all();
                    }
                @endphp
                <div data-closed-days-settings>
                    <span class="admin-label">定休日</span>
                    <div class="mt-3 space-y-4">
                        <div>
                            <p class="text-sm text-admin-text">毎週</p>
                            <div class="news-weekday-choices mt-1 notranslate" role="group" aria-label="毎週の定休日" translate="no" lang="ja">
                                @foreach($weekdayLabels as $weekdayValue => $weekdayLabel)
                                    <label class="news-weekday-option">
                                        <input
                                            type="checkbox"
                                            name="closed_weekdays[]"
                                            value="{{ $weekdayValue }}"
                                            class="news-weekday-input"
                                            @checked(in_array((int) $weekdayValue, $selectedClosedWeekdays, true))
                                        >
                                        <span class="news-weekday-face">{{ $weekdayLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <p class="text-sm text-admin-text">追加定休日（第○週の○曜日）</p>
                            <div class="mt-2 space-y-2 notranslate" data-closed-nth-list translate="no" lang="ja">
                                @foreach($selectedClosedNth as $index => $rule)
                                    <div class="flex flex-wrap items-center gap-2" data-closed-nth-row>
                                        <select name="closed_nth[{{ $index }}][week]" class="admin-input max-w-[7.5rem] notranslate" aria-label="週" translate="no" lang="ja">
                                            @for($week = 1; $week <= 5; $week++)
                                                <option value="{{ $week }}" @selected((int) ($rule['week'] ?? 0) === $week)>第{{ $week }}週</option>
                                            @endfor
                                        </select>
                                        <select name="closed_nth[{{ $index }}][weekday]" class="admin-input max-w-[7rem] notranslate" aria-label="曜日" translate="no" lang="ja">
                                            @foreach($weekdayLabels as $weekdayValue => $weekdayLabel)
                                                <option value="{{ $weekdayValue }}" @selected((int) ($rule['weekday'] ?? -1) === (int) $weekdayValue)>{{ $weekdayLabel }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="admin-icon-btn admin-icon-btn-delete" data-closed-nth-remove aria-label="削除" title="削除">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                            {{-- Always present so week labels stay as 第N週 even when no rows are saved yet. --}}
                            <select class="sr-only notranslate" aria-hidden="true" tabindex="-1" translate="no" lang="ja" data-closed-nth-week-labels>
                                @for($week = 1; $week <= 5; $week++)
                                    <option value="{{ $week }}">第{{ $week }}週</option>
                                @endfor
                            </select>
                            <button type="button" class="admin-btn-secondary mt-2 text-sm" data-closed-nth-add>＋ 追加定休日を追加</button>
                            <p class="mt-1 text-xs text-admin-muted">例：第3水曜日、第1・第3水曜日</p>
                        </div>
                    </div>
                    @error('closed_weekdays')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('closed_nth')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('closed_nth.*.week')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('closed_nth.*.weekday')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="phone" class="admin-label">電話番号</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $setting->phone) }}" class="admin-input">
                </div>
                <div>
                    <label for="payment_methods" class="admin-label">支払い方法</label>
                    <textarea name="payment_methods" id="payment_methods" rows="3" class="admin-input">{{ old('payment_methods', $setting->payment_methods) }}</textarea>
                </div>
                <div>
                    <label for="cut_price" class="admin-label">カット価格</label>
                    <input type="text" name="cut_price" id="cut_price" value="{{ old('cut_price', $setting->cut_price) }}" class="admin-input" placeholder="例：¥5,940">
                </div>
                <div>
                    <label for="seat_count" class="admin-label">席数</label>
                    <input type="text" name="seat_count" id="seat_count" value="{{ old('seat_count', $setting->seat_count) }}" class="admin-input" placeholder="例：セット面3席">
                </div>
                <div>
                    <label for="staff_count" class="admin-label">スタッフ数</label>
                    <input type="text" name="staff_count" id="staff_count" value="{{ old('staff_count', $setting->staff_count) }}" class="admin-input" placeholder="例：スタイリスト1人">
                </div>
                <div>
                    <label for="parking" class="admin-label">駐車場</label>
                    <textarea name="parking" id="parking" rows="3" class="admin-input">{{ old('parking', $setting->parking) }}</textarea>
                </div>
            </div>

            <div class="space-y-5">
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
                                        class="banner-dropzone-placeholder {{ $hasLogo ? 'hidden' : '' }} min-h-30 flex-col items-center justify-center px-4 text-center"
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

                {{-- サービス・補足情報 --}}
                <div class="admin-card space-y-5">
                    <div>
                        <h2 class="text-base font-medium text-admin-text">サービス・補足情報</h2>
                        <p class="mt-1 text-sm text-admin-muted">こだわり条件や備考など、補足情報を設定します。空欄の項目は公開サイトに表示されません。</p>
                    </div>
                    <div>
                        <label for="notes" class="admin-label">備考</label>
                        <textarea name="notes" id="notes" rows="4" class="admin-input">{{ old('notes', $setting->notes) }}</textarea>
                        <p class="mt-1 text-xs text-admin-muted">電話番号は上の「電話番号」欄を使用してください。ここへ重複して書かないでください。</p>
                    </div>
                    <div>
                        <label for="commitment_conditions" class="admin-label">こだわり条件</label>
                        <textarea name="commitment_conditions" id="commitment_conditions" rows="5" class="admin-input">{{ old('commitment_conditions', $setting->commitment_conditions) }}</textarea>
                    </div>
                    <div>
                        <label for="other_info" class="admin-label">その他</label>
                        <textarea name="other_info" id="other_info" rows="3" class="admin-input">{{ old('other_info', $setting->other_info) }}</textarea>
                    </div>
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

            const closedNthList = document.querySelector('[data-closed-nth-list]');
            const closedNthAdd = document.querySelector('[data-closed-nth-add]');
            const weekdayOptions = @json($weekdayLabels ?? \App\Models\SalonSetting::WEEKDAY_SHORT_LABELS);
            let closedNthIndex = {{ count($selectedClosedNth ?? []) }};

            function closedNthRowHtml(index) {
                const weekOptions = [1, 2, 3, 4, 5].map(function (week) {
                    return '<option value="' + week + '">第' + week + '週</option>';
                }).join('');
                const dayOptions = Object.keys(weekdayOptions).map(function (value) {
                    return '<option value="' + value + '">' + weekdayOptions[value] + '</option>';
                }).join('');

                return '' +
                    '<div class="flex flex-wrap items-center gap-2" data-closed-nth-row>' +
                        '<select name="closed_nth[' + index + '][week]" class="admin-input max-w-[7.5rem] notranslate" aria-label="週" translate="no" lang="ja">' + weekOptions + '</select>' +
                        '<select name="closed_nth[' + index + '][weekday]" class="admin-input max-w-[7rem] notranslate" aria-label="曜日" translate="no" lang="ja">' + dayOptions + '</select>' +
                        '<button type="button" class="admin-icon-btn admin-icon-btn-delete" data-closed-nth-remove aria-label="削除" title="削除">' +
                            '<span aria-hidden="true">&times;</span>' +
                        '</button>' +
                    '</div>';
            }

            closedNthAdd?.addEventListener('click', function () {
                if (!closedNthList) {
                    return;
                }
                closedNthList.insertAdjacentHTML('beforeend', closedNthRowHtml(closedNthIndex));
                closedNthIndex += 1;
            });

            closedNthList?.addEventListener('click', function (event) {
                const button = event.target.closest('[data-closed-nth-remove]');
                if (!button || !closedNthList.contains(button)) {
                    return;
                }
                const row = button.closest('[data-closed-nth-row]');
                row?.remove();
            });
        })();
    </script>
@endsection
