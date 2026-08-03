@extends('layouts.admin')

@section('heading', 'デザイン設定')

@section('save-bar')
    <div class="flex min-w-0 flex-wrap items-center gap-3">
        <button
            type="button"
            class="admin-btn shadow-md shrink-0"
            data-admin-confirm-trigger
            data-confirm-form="design-form"
            data-confirm-title="デザイン設定保存の確認"
            data-confirm-message="デザイン設定を保存します。&#10;よろしいですか？"
            data-confirm-note="保存すると公開サイトの見た目に反映されます。管理画面の見た目は変わりません。"
            data-confirm-submit-label="保存する"
        >保存する</button>
        <button
            type="button"
            class="admin-btn-secondary shrink-0"
            data-admin-confirm-trigger
            data-confirm-callback="design-settings-reset"
            data-confirm-title="初期値に戻す確認"
            data-confirm-message="デザイン設定を初期値へ戻します。保存するまでは公開サイトへ反映されません。"
            data-confirm-submit-label="初期値に戻す"
            data-design-reset
        >初期値に戻す</button>
        <p class="text-sm text-admin-muted">
            公開サイトの色・フォント・角丸・余白などを設定します。
        </p>
    </div>

@endsection

@section('content')
    @php
        $defaultsJson = $defaults;
        $fontStacksJson = \App\Models\DesignSetting::FONT_STACKS;
        $buttonRadiusJson = \App\Models\DesignSetting::BUTTON_RADIUS_PX;
        $cardRadiusJson = \App\Models\DesignSetting::CARD_RADIUS_PX;
        $densityJson = \App\Models\DesignSetting::DENSITY_TOKENS;

        $primary = old('primary_color', $design->primary_color);
        $secondary = old('secondary_color', $design->secondary_color);
        $background = old('background_color', $design->background_color);
        $text = old('text_color', $design->text_color);
        $scrollbarThumb = old('scrollbar_thumb_color', $design->scrollbar_thumb_color);
        $scrollbarTrack = old('scrollbar_track_color', $design->scrollbar_track_color);
        $scrollbarThumbHover = old('scrollbar_thumb_hover_color', $design->scrollbar_thumb_hover_color);
        $headingFont = old('heading_font', $design->heading_font);
        $bodyFont = old('body_font', $design->body_font);
        $buttonRadius = old('button_radius', $design->button_radius);
        $cardRadius = old('card_radius', $design->card_radius);
        $layoutDensity = old('layout_density', $design->layout_density);
        $shopName = $setting->shop_name ?: 'Sun＆ Me';
    @endphp

    <form id="design-form" method="POST" action="{{ route('admin.system.design.update') }}" data-design-form>
        @csrf @method('PUT')

        <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-2">
            <div class="min-w-0 space-y-5">
                <div class="admin-card space-y-4">
                    <div>
                        <h2 class="text-base font-medium text-admin-text">カラー</h2>
                        <p class="mt-1 text-sm text-admin-muted">公開サイトの基本カラーです。管理画面の色は変わりません。</p>
                    </div>

                    @foreach ([
                        'primary_color' => ['label' => 'メインカラー（ボタン）', 'value' => $primary, 'hint' => '予約ボタンなどの主色'],
                        'secondary_color' => ['label' => 'アクセントカラー', 'value' => $secondary, 'hint' => 'リンクホバーなどの補助色'],
                        'background_color' => ['label' => '背景色', 'value' => $background, 'hint' => 'ページ全体の背景'],
                        'text_color' => ['label' => '文字色', 'value' => $text, 'hint' => '本文・見出しの基本色'],
                    ] as $name => $meta)
                        <div data-design-color-field>
                            <label for="{{ $name }}" class="admin-label">{{ $meta['label'] }}</label>
                            <div class="flex flex-wrap items-center gap-3">
                                <input
                                    type="color"
                                    value="{{ $meta['value'] }}"
                                    class="h-10 w-14 cursor-pointer rounded-lg border border-admin-border bg-admin-card p-1"
                                    data-design-color-swatch
                                    aria-label="{{ $meta['label'] }}のカラーピッカー"
                                >
                                <input
                                    type="text"
                                    name="{{ $name }}"
                                    id="{{ $name }}"
                                    value="{{ $meta['value'] }}"
                                    class="admin-input max-w-[10rem] font-mono uppercase"
                                    maxlength="7"
                                    autocomplete="off"
                                    spellcheck="false"
                                    pattern="#?[0-9A-Fa-f]{6}"
                                    data-design-color-hex
                                    data-design-field="{{ $name }}"
                                >
                            </div>
                            <p class="mt-1 text-xs text-admin-muted">{{ $meta['hint'] }}（#RRGGBB）</p>
                            @error($name)
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach

                    <div class="border-t border-admin-border/60 pt-4">
                        <div class="mb-3">
                            <h3 class="text-sm font-medium text-admin-text">スクロールバー</h3>
                            <p class="mt-1 text-xs text-admin-muted">公開サイトのスクロールバー色です。管理画面のスクロールバーは変わりません。</p>
                        </div>
                        @foreach ([
                            'scrollbar_thumb_color' => ['label' => 'つまみ色', 'value' => $scrollbarThumb, 'hint' => 'スクロールバーのつまみ'],
                            'scrollbar_track_color' => ['label' => '背景色', 'value' => $scrollbarTrack, 'hint' => 'スクロールバーの背景'],
                            'scrollbar_thumb_hover_color' => ['label' => 'ホバー色', 'value' => $scrollbarThumbHover, 'hint' => 'つまみにマウスを乗せたとき'],
                        ] as $name => $meta)
                            <div class="mt-3" data-design-color-field>
                                <label for="{{ $name }}" class="admin-label">{{ $meta['label'] }}</label>
                                <div class="flex flex-wrap items-center gap-3">
                                    <input
                                        type="color"
                                        value="{{ $meta['value'] }}"
                                        class="h-10 w-14 cursor-pointer rounded-lg border border-admin-border bg-admin-card p-1"
                                        data-design-color-swatch
                                        aria-label="{{ $meta['label'] }}のカラーピッカー"
                                    >
                                    <input
                                        type="text"
                                        name="{{ $name }}"
                                        id="{{ $name }}"
                                        value="{{ $meta['value'] }}"
                                        class="admin-input max-w-[10rem] font-mono uppercase"
                                        maxlength="7"
                                        autocomplete="off"
                                        spellcheck="false"
                                        pattern="#?[0-9A-Fa-f]{6}"
                                        data-design-color-hex
                                        data-design-field="{{ $name }}"
                                    >
                                </div>
                                <p class="mt-1 text-xs text-admin-muted">{{ $meta['hint'] }}（#RRGGBB）</p>
                                @error($name)
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="admin-card space-y-4">
                    <div>
                        <h2 class="text-base font-medium text-admin-text">フォント</h2>
                        <p class="mt-1 text-sm text-admin-muted">公開サイトで読み込み済みのフォントから選択します。</p>
                    </div>

                    <div>
                        <span class="admin-label" id="heading_font_label">見出しフォント</span>
                        <div class="admin-segmented mt-1" role="radiogroup" aria-labelledby="heading_font_label" data-design-font-group="heading_font">
                            @foreach (['serif' => '明朝体', 'sans' => 'ゴシック体', 'rounded' => '丸ゴシック体'] as $value => $label)
                                <label class="admin-segmented-option">
                                    <input
                                        type="radio"
                                        name="heading_font"
                                        value="{{ $value }}"
                                        class="admin-segmented-input"
                                        data-design-field="heading_font"
                                        {{ $headingFont === $value ? 'checked' : '' }}
                                    >
                                    <span class="admin-segmented-face">
                                        <span class="admin-segmented-text">{{ $label }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('heading_font')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <span class="admin-label" id="body_font_label">本文フォント</span>
                        <div class="admin-segmented mt-1" role="radiogroup" aria-labelledby="body_font_label" data-design-font-group="body_font">
                            @foreach (['serif' => '明朝体', 'sans' => 'ゴシック体', 'rounded' => '丸ゴシック体'] as $value => $label)
                                <label class="admin-segmented-option">
                                    <input
                                        type="radio"
                                        name="body_font"
                                        value="{{ $value }}"
                                        class="admin-segmented-input"
                                        data-design-field="body_font"
                                        {{ $bodyFont === $value ? 'checked' : '' }}
                                    >
                                    <span class="admin-segmented-face">
                                        <span class="admin-segmented-text">{{ $label }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('body_font')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="admin-card space-y-4">
                    <div>
                        <h2 class="text-base font-medium text-admin-text">角丸</h2>
                        <p class="mt-1 text-sm text-admin-muted">ボタンとカードの角の丸みを調整します。</p>
                    </div>

                    <div>
                        <span class="admin-label" id="button_radius_label">ボタンの角丸</span>
                        <div class="admin-segmented mt-1" role="radiogroup" aria-labelledby="button_radius_label">
                            @foreach (['small' => '小さめ', 'medium' => '標準', 'large' => '大きめ（丸）'] as $value => $label)
                                <label class="admin-segmented-option">
                                    <input
                                        type="radio"
                                        name="button_radius"
                                        value="{{ $value }}"
                                        class="admin-segmented-input"
                                        data-design-field="button_radius"
                                        {{ $buttonRadius === $value ? 'checked' : '' }}
                                    >
                                    <span class="admin-segmented-face">
                                        <span class="admin-segmented-text">{{ $label }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('button_radius')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <span class="admin-label" id="card_radius_label">カードの角丸</span>
                        <div class="admin-segmented mt-1" role="radiogroup" aria-labelledby="card_radius_label">
                            @foreach (['small' => '小さめ', 'medium' => '標準', 'large' => '大きめ'] as $value => $label)
                                <label class="admin-segmented-option">
                                    <input
                                        type="radio"
                                        name="card_radius"
                                        value="{{ $value }}"
                                        class="admin-segmented-input"
                                        data-design-field="card_radius"
                                        {{ $cardRadius === $value ? 'checked' : '' }}
                                    >
                                    <span class="admin-segmented-face">
                                        <span class="admin-segmented-text">{{ $label }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('card_radius')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="admin-card space-y-4">
                    <div>
                        <h2 class="text-base font-medium text-admin-text">レイアウト</h2>
                        <p class="mt-1 text-sm text-admin-muted">セクションの余白とカード内の余白を調整します。「標準」が現在の公開サイトと同じです。</p>
                    </div>

                    <div>
                        <span class="admin-label" id="layout_density_label">余白の密度</span>
                        <div class="admin-segmented mt-1" role="radiogroup" aria-labelledby="layout_density_label">
                            @foreach (['compact' => 'コンパクト', 'standard' => '標準', 'relaxed' => 'ゆったり'] as $value => $label)
                                <label class="admin-segmented-option">
                                    <input
                                        type="radio"
                                        name="layout_density"
                                        value="{{ $value }}"
                                        class="admin-segmented-input"
                                        data-design-field="layout_density"
                                        {{ $layoutDensity === $value ? 'checked' : '' }}
                                    >
                                    <span class="admin-segmented-face">
                                        <span class="admin-segmented-text">{{ $label }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('layout_density')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="admin-card min-w-0 space-y-4" data-design-preview-card>
                <div>
                    <h2 class="text-base font-medium text-admin-text">プレビュー</h2>
                    <p class="mt-1 text-sm text-admin-muted">保存前の見た目を確認できます。公開サイトへは保存後に反映されます。</p>
                </div>
                <div
                    class="design-preview overflow-hidden border border-admin-border/50"
                    data-design-preview
                    style="
                        --site-primary: {{ $primary }};
                        --site-secondary: {{ $secondary }};
                        --site-background: {{ $background }};
                        --site-text: {{ $text }};
                        --site-scrollbar-thumb: {{ $scrollbarThumb }};
                        --site-scrollbar-track: {{ $scrollbarTrack }};
                        --site-scrollbar-thumb-hover: {{ $scrollbarThumbHover }};
                        background: var(--site-background);
                        color: var(--site-text);
                        border-radius: var(--site-card-radius, 8px);
                        font-family: var(--site-body-font, sans-serif);
                    "
                >
                    <div
                        class="flex items-center justify-between gap-3 border-b px-4 py-3"
                        style="border-color: color-mix(in srgb, var(--site-text) 12%, transparent); background: color-mix(in srgb, var(--site-background) 95%, white);"
                        data-design-preview-header
                    >
                        <span class="text-lg tracking-widest" style="font-family: var(--site-heading-font, serif);" data-design-preview-shop>{{ $shopName }}</span>
                        <a href="#" class="text-sm" style="color: var(--site-secondary);" data-design-preview-link onclick="return false;">Menu</a>
                    </div>
                    <div class="px-4" style="padding-block: var(--site-section-spacing, 5rem);" data-design-preview-section>
                        <h3 class="text-2xl tracking-wide" style="font-family: var(--site-heading-font, serif);" data-design-preview-heading>ナチュラルに、自分らしく。</h3>
                        <p class="mt-3 text-sm leading-relaxed opacity-90" data-design-preview-body>
                            ☆一人一人の　”　ラ シ サ　”　を大切にするパーソナルサロン☆
                        </p>
                        <div class="mt-5 flex flex-wrap items-center gap-3">
                            <a
                                href="#"
                                class="btn-primary"
                                data-design-preview-button
                                onclick="return false;"
                            >予約する</a>
                            <a
                                href="#"
                                class="btn-outline"
                                data-design-preview-outline
                                onclick="return false;"
                            >Googleマップで開く</a>
                        </div>
                        <div
                            class="mt-6 border"
                            style="
                                border-color: color-mix(in srgb, var(--site-text) 12%, transparent);
                                border-radius: var(--site-card-radius, 8px);
                                padding: var(--site-card-padding, 1.5rem);
                                background: color-mix(in srgb, var(--site-background) 70%, white);
                            "
                            data-design-preview-site-card
                        >
                            <p class="text-sm font-medium" style="font-family: var(--site-heading-font, serif);">アクセス情報</p>
                            <p class="mt-2 text-sm opacity-80">埼玉県川口市幸町２－14－27－102号</p>
                            <a href="#" class="btn-outline mt-4" data-design-preview-card-link onclick="return false;">詳細を見る</a>
                        </div>
                        <div class="mt-5">
                            <p class="text-xs opacity-70">スクロールバー表示例</p>
                            <div
                                class="design-preview-scrollbar mt-2 h-24 overflow-y-auto rounded border px-3 py-2 text-xs leading-relaxed opacity-90"
                                style="border-color: color-mix(in srgb, var(--site-text) 12%, transparent);"
                                data-design-preview-scrollbar
                            >
                                <p>公開サイトのスクロールバー色です。</p>
                                <p class="mt-2">つまみ・背景・ホバー色を変更すると、この見本にも反映されます。</p>
                                <p class="mt-2">余白を増やしてスクロールできるようにしています。</p>
                                <p class="mt-2">保存後に公開サイトへ適用されます。</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <script>
        (function () {
            const form = document.querySelector('[data-design-form]');
            const preview = document.querySelector('[data-design-preview]');
            if (!form || !preview) {
                return;
            }

            const defaults = @json($defaultsJson);
            const fontStacks = @json($fontStacksJson);
            const buttonRadiusMap = @json($buttonRadiusJson);
            const cardRadiusMap = @json($cardRadiusJson);
            const densityMap = @json($densityJson);

            const normalizeHex = (value) => {
                let v = String(value || '').trim();
                if (v && v.charAt(0) !== '#') {
                    v = '#' + v;
                }
                return v.toLowerCase();
            };

            const isValidHex = (value) => /^#[0-9a-f]{6}$/i.test(value);

            const getRadioValue = (name) => {
                const el = form.querySelector('input[name="' + name + '"]:checked');
                return el ? el.value : defaults[name];
            };

            const setRadioValue = (name, value) => {
                const el = form.querySelector('input[name="' + name + '"][value="' + value + '"]');
                if (el) {
                    el.checked = true;
                }
            };

            const applyPreview = () => {
                const primary = normalizeHex(form.querySelector('[name="primary_color"]').value);
                const secondary = normalizeHex(form.querySelector('[name="secondary_color"]').value);
                const background = normalizeHex(form.querySelector('[name="background_color"]').value);
                const text = normalizeHex(form.querySelector('[name="text_color"]').value);
                const scrollbarThumb = normalizeHex(form.querySelector('[name="scrollbar_thumb_color"]').value);
                const scrollbarTrack = normalizeHex(form.querySelector('[name="scrollbar_track_color"]').value);
                const scrollbarThumbHover = normalizeHex(form.querySelector('[name="scrollbar_thumb_hover_color"]').value);
                const headingFont = getRadioValue('heading_font');
                const bodyFont = getRadioValue('body_font');
                const buttonRadius = getRadioValue('button_radius');
                const cardRadius = getRadioValue('card_radius');
                const density = getRadioValue('layout_density');
                const densityTokens = densityMap[density] || densityMap.standard;

                if (isValidHex(primary)) {
                    preview.style.setProperty('--site-primary', primary);
                }
                if (isValidHex(secondary)) {
                    preview.style.setProperty('--site-secondary', secondary);
                }
                if (isValidHex(background)) {
                    preview.style.setProperty('--site-background', background);
                    preview.style.background = background;
                }
                if (isValidHex(text)) {
                    preview.style.setProperty('--site-text', text);
                    preview.style.color = text;
                }
                if (isValidHex(scrollbarThumb)) {
                    preview.style.setProperty('--site-scrollbar-thumb', scrollbarThumb);
                }
                if (isValidHex(scrollbarTrack)) {
                    preview.style.setProperty('--site-scrollbar-track', scrollbarTrack);
                }
                if (isValidHex(scrollbarThumbHover)) {
                    preview.style.setProperty('--site-scrollbar-thumb-hover', scrollbarThumbHover);
                }

                preview.style.setProperty('--site-heading-font', fontStacks[headingFont] || fontStacks.serif);
                preview.style.setProperty('--site-body-font', fontStacks[bodyFont] || fontStacks.sans);
                preview.style.fontFamily = fontStacks[bodyFont] || fontStacks.sans;
                preview.style.setProperty('--site-button-radius', buttonRadiusMap[buttonRadius] || buttonRadiusMap.large);
                preview.style.setProperty('--site-card-radius', cardRadiusMap[cardRadius] || cardRadiusMap.medium);
                preview.style.setProperty('--site-section-spacing', densityTokens.section_spacing);
                preview.style.setProperty('--site-card-padding', densityTokens.card_padding);
                preview.style.borderRadius = cardRadiusMap[cardRadius] || cardRadiusMap.medium;
            };

            form.querySelectorAll('[data-design-color-field]').forEach((field) => {
                const swatch = field.querySelector('[data-design-color-swatch]');
                const hex = field.querySelector('[data-design-color-hex]');
                if (!swatch || !hex) {
                    return;
                }

                const syncFromHex = () => {
                    const normalized = normalizeHex(hex.value);
                    hex.value = normalized;
                    if (isValidHex(normalized)) {
                        swatch.value = normalized;
                    }
                    applyPreview();
                };

                const syncFromSwatch = () => {
                    hex.value = swatch.value.toLowerCase();
                    applyPreview();
                };

                hex.addEventListener('input', syncFromHex);
                hex.addEventListener('change', syncFromHex);
                swatch.addEventListener('input', syncFromSwatch);
                swatch.addEventListener('change', syncFromSwatch);
            });

            form.querySelectorAll('input[type="radio"]').forEach((input) => {
                input.addEventListener('change', applyPreview);
            });

            document.addEventListener('design-settings-reset', () => {
                form.querySelector('[name="primary_color"]').value = defaults.primary_color;
                form.querySelector('[name="secondary_color"]').value = defaults.secondary_color;
                form.querySelector('[name="background_color"]').value = defaults.background_color;
                form.querySelector('[name="text_color"]').value = defaults.text_color;
                form.querySelector('[name="scrollbar_thumb_color"]').value = defaults.scrollbar_thumb_color;
                form.querySelector('[name="scrollbar_track_color"]').value = defaults.scrollbar_track_color;
                form.querySelector('[name="scrollbar_thumb_hover_color"]').value = defaults.scrollbar_thumb_hover_color;
                form.querySelectorAll('[data-design-color-field]').forEach((field) => {
                    const swatch = field.querySelector('[data-design-color-swatch]');
                    const hex = field.querySelector('[data-design-color-hex]');
                    if (swatch && hex) {
                        swatch.value = hex.value;
                    }
                });
                setRadioValue('heading_font', defaults.heading_font);
                setRadioValue('body_font', defaults.body_font);
                setRadioValue('button_radius', defaults.button_radius);
                setRadioValue('card_radius', defaults.card_radius);
                setRadioValue('layout_density', defaults.layout_density);
                applyPreview();
            });

            applyPreview();
        })();
    </script>
@endsection
