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
        $scrollDisplayType = old('scroll_display_type', $design->resolvedScrollDisplayType());
        $newsDetailDisplay = old('news_detail_display', $design->resolvedNewsDetailDisplay());
        $blogDetailDisplay = old('blog_detail_display', $design->resolvedBlogDetailDisplay());
        $galleryDetailDisplay = old('gallery_detail_display', $design->resolvedGalleryDetailDisplay());
        $modalOverlayStyle = old('modal_overlay_style', $design->resolvedModalOverlayStyle());
        $modalOverlayColor = old('modal_overlay_color', $design->resolvedModalOverlayColor());
        $footerBackground = old('footer_background_color', $design->footer_background_color);
        $footerText = old('footer_text_color', $design->footer_text_color);
        $footerLink = old('footer_link_color', $design->footer_link_color);
        $footerLinkHover = old('footer_link_hover_color', $design->footer_link_hover_color);
        $headingFont = old('heading_font', $design->heading_font);
        $bodyFont = old('body_font', $design->body_font);
        $buttonRadius = old('button_radius', $design->button_radius);
        $cardRadius = old('card_radius', $design->card_radius);
        $layoutDensity = old('layout_density', $design->layout_density);
        $shopName = $setting->shop_name ?: 'Sun＆ Me';
        $scrollColored = \App\Models\DesignSetting::SCROLL_COLORED_SCROLLBAR;
        $scrollIndicator = \App\Models\DesignSetting::SCROLL_VERTICAL_INDICATOR;
        $detailPage = \App\Models\DesignSetting::DETAIL_DISPLAY_PAGE;
        $detailModal = \App\Models\DesignSetting::DETAIL_DISPLAY_MODAL;
        $modalOverlayLabels = \App\Models\DesignSetting::MODAL_OVERLAY_LABELS;
        $modalOverlayTokens = \App\Models\DesignSetting::MODAL_OVERLAY_TOKENS;
        $detailDisplayRows = [
            'news_detail_display' => ['label' => 'お知らせ', 'value' => $newsDetailDisplay],
            'blog_detail_display' => ['label' => 'ブログ', 'value' => $blogDetailDisplay],
            'gallery_detail_display' => ['label' => 'ギャラリー', 'value' => $galleryDetailDisplay],
        ];
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
                                    class="admin-input max-w-40 font-mono uppercase"
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

                <div class="admin-card space-y-4" data-design-scroll-section>
                    <div>
                        <h2 class="text-base font-medium text-admin-text">スクロール表示</h2>
                        <p class="mt-1 text-sm text-admin-muted">公開サイトのスクロール位置の見せ方を選びます。管理画面には反映されません。</p>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2"
                        role="radiogroup"
                        aria-label="スクロール表示"
                        data-design-scroll-options
                        style="
                            --site-secondary: {{ $secondary }};
                            --site-background: {{ $background }};
                            --site-text: {{ $text }};
                            --site-scrollbar-thumb: {{ $scrollbarThumb }};
                            --site-scrollbar-track: {{ $scrollbarTrack }};
                        "
                    >
                        <label class="design-scroll-option">
                            <input
                                type="radio"
                                name="scroll_display_type"
                                value="{{ $scrollColored }}"
                                class="design-scroll-option-input"
                                data-design-field="scroll_display_type"
                                {{ $scrollDisplayType === $scrollColored ? 'checked' : '' }}
                            >
                            <span class="design-scroll-option-card">
                                <span class="design-scroll-option-preview" aria-hidden="true">
                                    <span class="design-scroll-option-preview-content">
                                        <span class="design-scroll-option-preview-line"></span>
                                        <span class="design-scroll-option-preview-line design-scroll-option-preview-line--short"></span>
                                        <span class="design-scroll-option-preview-line"></span>
                                        <span class="design-scroll-option-preview-line design-scroll-option-preview-line--short"></span>
                                    </span>
                                    <span class="design-scroll-option-preview-scrollbar"></span>
                                </span>
                                <span class="design-scroll-option-title">カラースクロールバー</span>
                                <span class="design-scroll-option-desc">ブラウザの縦スクロールバーをサイトカラーに合わせて表示します。</span>
                            </span>
                        </label>

                        <label class="design-scroll-option">
                            <input
                                type="radio"
                                name="scroll_display_type"
                                value="{{ $scrollIndicator }}"
                                class="design-scroll-option-input"
                                data-design-field="scroll_display_type"
                                {{ $scrollDisplayType === $scrollIndicator ? 'checked' : '' }}
                            >
                            <span class="design-scroll-option-card">
                                <span class="design-scroll-option-preview" aria-hidden="true">
                                    <span class="design-scroll-option-preview-content">
                                        <span class="design-scroll-option-preview-line"></span>
                                        <span class="design-scroll-option-preview-line design-scroll-option-preview-line--short"></span>
                                        <span class="design-scroll-option-preview-line"></span>
                                        <span class="design-scroll-option-preview-line design-scroll-option-preview-line--short"></span>
                                    </span>
                                    <span class="design-scroll-option-preview-indicator" aria-hidden="true">
                                        <span></span><span></span><span></span><span></span><span></span>
                                    </span>
                                </span>
                                <span class="design-scroll-option-title">縦インジケーター</span>
                                <span class="design-scroll-option-desc">画面右端付近にセクション用のドットを縦に並べ、現在位置だけをアクセント表示します。</span>
                            </span>
                        </label>
                    </div>
                    @error('scroll_display_type')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="border-t border-admin-border/60 pt-4" data-design-scrollbar-colors>
                        <div class="mb-3">
                            <h3 class="text-sm font-medium text-admin-text">スクロールバーの色</h3>
                            <p class="mt-1 text-xs text-admin-muted">「カラースクロールバー」選択時のみ公開サイトへ適用されます。</p>
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
                                        class="admin-input max-w-40 font-mono uppercase"
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

                <div class="admin-card space-y-4" data-design-detail-display-section>
                    <div>
                        <h2 class="text-base font-medium text-admin-text">詳細ページの表示方法</h2>
                        <p class="mt-1 text-sm text-admin-muted">一覧からのクリック時に、詳細ページへ遷移するかモーダルで表示するかを選びます。直接URLアクセス時は常に詳細ページを表示します。</p>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-admin-border">
                        <table class="design-detail-display-table w-full min-w-[22rem] text-left text-sm">
                            <thead>
                                <tr class="border-b border-admin-border bg-admin-bg/70">
                                    <th scope="col" class="px-4 py-3 font-medium text-admin-text">コンテンツ</th>
                                    <th scope="col" class="px-4 py-3 font-medium text-admin-text">表示方法</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($detailDisplayRows as $field => $row)
                                    <tr class="border-b border-admin-border/70 last:border-b-0">
                                        <th scope="row" class="whitespace-nowrap px-4 py-3.5 font-medium text-admin-text">
                                            {{ $row['label'] }}
                                        </th>
                                        <td class="px-4 py-3.5">
                                            <div
                                                class="admin-segmented admin-segmented--compact max-w-md"
                                                role="radiogroup"
                                                aria-label="{{ $row['label'] }}の詳細表示方法"
                                            >
                                                <label class="admin-segmented-option">
                                                    <input
                                                        type="radio"
                                                        name="{{ $field }}"
                                                        value="{{ $detailPage }}"
                                                        class="admin-segmented-input"
                                                        data-design-field="{{ $field }}"
                                                        {{ $row['value'] === $detailPage ? 'checked' : '' }}
                                                    >
                                                    <span class="admin-segmented-face">
                                                        <span class="admin-segmented-text">画面遷移</span>
                                                    </span>
                                                </label>
                                                <label class="admin-segmented-option">
                                                    <input
                                                        type="radio"
                                                        name="{{ $field }}"
                                                        value="{{ $detailModal }}"
                                                        class="admin-segmented-input"
                                                        data-design-field="{{ $field }}"
                                                        {{ $row['value'] === $detailModal ? 'checked' : '' }}
                                                    >
                                                    <span class="admin-segmented-face">
                                                        <span class="admin-segmented-text">モーダル</span>
                                                    </span>
                                                </label>
                                            </div>
                                            @error($field)
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="text-xs text-admin-muted">
                        画面遷移：従来どおり詳細ページへ移動します。モーダル：同一ページ上で詳細を表示します。
                    </p>
                </div>

                <div class="admin-card space-y-4" data-design-modal-overlay-section>
                    <div>
                        <h2 class="text-base font-medium text-admin-text">モーダル背景</h2>
                        <p class="mt-1 text-sm text-admin-muted">お知らせ・ブログ・ギャラリーなど、公開サイト共通モーダルの背面オーバーレイのみを設定します。モーダル本体の背景色・文字色には影響しません。</p>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-3 sm:grid-cols-4"
                        role="radiogroup"
                        aria-label="モーダル背景"
                        data-design-modal-overlay-options
                    >
                        @foreach ($modalOverlayLabels as $value => $label)
                            @php
                                $tokens = $modalOverlayTokens[$value];
                                $previewOpacity = number_format($tokens['opacity'], 2, '.', '');
                                $previewFilter = $tokens['blur'] === '0px' ? 'none' : 'blur('.$tokens['blur'].')';
                            @endphp
                            <label class="design-modal-overlay-option">
                                <input
                                    type="radio"
                                    name="modal_overlay_style"
                                    value="{{ $value }}"
                                    class="design-modal-overlay-option-input"
                                    data-design-field="modal_overlay_style"
                                    {{ $modalOverlayStyle === $value ? 'checked' : '' }}
                                >
                                <span class="design-modal-overlay-option-card">
                                    <span
                                        class="design-modal-overlay-option-preview"
                                        aria-hidden="true"
                                        style="
                                            --design-modal-overlay-rgb: {{ \App\Models\DesignSetting::hexToRgbChannels($modalOverlayColor) }};
                                            --design-modal-overlay-opacity: {{ $previewOpacity }};
                                            --design-modal-overlay-filter: {{ $previewFilter }};
                                        "
                                    ></span>
                                    <span class="design-modal-overlay-option-title">{{ $label }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('modal_overlay_style')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div data-design-color-field>
                        <label for="modal_overlay_color" class="admin-label">背景色</label>
                        <div class="flex flex-wrap items-center gap-3">
                            <input
                                type="color"
                                value="{{ $modalOverlayColor }}"
                                class="h-10 w-14 cursor-pointer rounded-lg border border-admin-border bg-admin-card p-1"
                                data-design-color-swatch
                                aria-label="モーダル背景色のカラーピッカー"
                            >
                            <input
                                type="text"
                                name="modal_overlay_color"
                                id="modal_overlay_color"
                                value="{{ $modalOverlayColor }}"
                                class="admin-input max-w-40 font-mono uppercase"
                                maxlength="7"
                                autocomplete="off"
                                spellcheck="false"
                                pattern="#?[0-9A-Fa-f]{6}"
                                data-design-color-hex
                                data-design-field="modal_overlay_color"
                            >
                        </div>
                        <p class="mt-1 text-xs text-admin-muted">オーバーレイの色です。濃さ（薄い／標準／濃い／ぼかしあり）とは別に設定できます。（#RRGGBB）</p>
                        @error('modal_overlay_color')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <p class="mb-2 text-xs font-medium text-admin-text">プレビュー</p>
                        @php
                            $selectedOverlayTokens = $modalOverlayTokens[$modalOverlayStyle] ?? $modalOverlayTokens[\App\Models\DesignSetting::MODAL_OVERLAY_BLUR];
                            $selectedOverlayOpacity = number_format($selectedOverlayTokens['opacity'], 2, '.', '');
                            $selectedOverlayFilter = $selectedOverlayTokens['blur'] === '0px'
                                ? 'none'
                                : 'blur('.$selectedOverlayTokens['blur'].')';
                        @endphp
                        <div
                            class="design-modal-overlay-live-preview"
                            aria-hidden="true"
                            data-design-modal-overlay-preview
                            style="
                                --site-modal-overlay-rgb: {{ \App\Models\DesignSetting::hexToRgbChannels($modalOverlayColor) }};
                                --site-modal-overlay-opacity: {{ $selectedOverlayOpacity }};
                                --site-modal-overlay-filter: {{ $selectedOverlayFilter }};
                            "
                        >
                            <div class="design-modal-overlay-live-preview-layer" data-design-modal-overlay-preview-layer></div>
                            <div class="design-modal-overlay-live-preview-card"></div>
                        </div>
                    </div>
                </div>

                <div class="admin-card space-y-4" data-design-footer-section>
                    <div>
                        <h2 class="text-base font-medium text-admin-text">フッター</h2>
                        <p class="mt-1 text-sm text-admin-muted">縦インジケーターモードの公開サイトフッターに反映されます。カラースクロールバーモードのフッターには影響しません。</p>
                    </div>

                    @foreach ([
                        'footer_background_color' => ['label' => 'フッター背景色', 'value' => $footerBackground, 'hint' => 'フッター全体の背景'],
                        'footer_text_color' => ['label' => 'フッター文字色', 'value' => $footerText, 'hint' => '店名・住所・コピーライトなど'],
                        'footer_link_color' => ['label' => 'フッターリンク色', 'value' => $footerLink, 'hint' => 'Privacy Policy などのリンク'],
                        'footer_link_hover_color' => ['label' => 'フッターリンクホバー色', 'value' => $footerLinkHover, 'hint' => 'リンクにマウスを乗せたとき'],
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
                                    class="admin-input max-w-40 font-mono uppercase"
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

                    <div>
                        <p class="mb-2 text-xs font-medium text-admin-text">プレビュー</p>
                        <div
                            class="overflow-hidden rounded-lg border border-admin-border"
                            aria-hidden="true"
                            data-design-footer-preview
                            style="
                                background: {{ $footerBackground }};
                                color: {{ $footerText }};
                                --site-footer-link-hover: {{ $footerLinkHover }};
                            "
                        >
                            <div class="px-4 py-5 text-center text-sm">
                                <p class="font-serif text-base" data-design-footer-preview-text>{{ $shopName }}</p>
                                <p class="mt-1 text-xs opacity-80" data-design-footer-preview-muted>〠…</p>
                                <a
                                    href="#"
                                    class="mt-3 inline-block text-xs underline-offset-2 hover:underline"
                                    data-design-footer-preview-link
                                    style="color: {{ $footerLink }};"
                                    tabindex="-1"
                                >Privacy Policy</a>
                                <p class="mt-3 text-[11px] opacity-75" data-design-footer-preview-muted>&copy; {{ date('Y') }} {{ $shopName }}</p>
                            </div>
                        </div>
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
                        <div class="mt-5" data-design-preview-scroll-colored>
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
                        <div class="mt-5 hidden" data-design-preview-scroll-indicator>
                            <p class="text-xs opacity-70">縦インジケーター表示例</p>
                            <div
                                class="relative mt-2 h-24 overflow-hidden rounded border px-3 py-2 text-xs leading-relaxed opacity-90"
                                style="border-color: color-mix(in srgb, var(--site-text) 12%, transparent);"
                            >
                                <p>主要セクションに対応するドットを縦に並べます。</p>
                                <p class="mt-2">現在位置のドットだけがアクセントカラーになります。</p>
                                <div
                                    class="absolute top-1/2 right-2 flex -translate-y-1/2 flex-col items-center gap-1.5"
                                    aria-hidden="true"
                                >
                                    <span class="block h-1 w-1 rounded-full" style="background: color-mix(in srgb, var(--site-text) 22%, transparent);"></span>
                                    <span class="block h-1 w-1 rounded-full" style="background: color-mix(in srgb, var(--site-text) 22%, transparent);"></span>
                                    <span class="block h-1 w-1 scale-125 rounded-full" style="background: var(--site-secondary);"></span>
                                    <span class="block h-1 w-1 rounded-full" style="background: color-mix(in srgb, var(--site-text) 22%, transparent);"></span>
                                    <span class="block h-1 w-1 rounded-full" style="background: color-mix(in srgb, var(--site-text) 22%, transparent);"></span>
                                </div>
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
            const modalOverlayTokens = @json($modalOverlayTokens);
            const scrollColored = @json($scrollColored);
            const scrollIndicator = @json($scrollIndicator);
            const scrollOptions = document.querySelector('[data-design-scroll-options]');
            const scrollbarColors = document.querySelector('[data-design-scrollbar-colors]');
            const previewScrollColored = document.querySelector('[data-design-preview-scroll-colored]');
            const previewScrollIndicator = document.querySelector('[data-design-preview-scroll-indicator]');
            const modalOverlayOptions = document.querySelector('[data-design-modal-overlay-options]');
            const modalOverlayLivePreview = document.querySelector('[data-design-modal-overlay-preview]');
            const footerPreview = document.querySelector('[data-design-footer-preview]');

            const normalizeHex = (value) => {
                let v = String(value || '').trim();
                if (v && v.charAt(0) !== '#') {
                    v = '#' + v;
                }
                return v.toLowerCase();
            };

            const isValidHex = (value) => /^#[0-9a-f]{6}$/i.test(value);

            const hexToRgbChannels = (hex) => {
                const normalized = normalizeHex(hex);
                if (!isValidHex(normalized)) {
                    return '30, 26, 22';
                }
                const channels = [1, 3, 5].map((i) => parseInt(normalized.slice(i, i + 2), 16));
                return channels.join(', ');
            };

            const formatOpacity = (value) => Number(value).toFixed(2);

            const mixHexWithWhite = (hex, amount) => {
                const normalized = normalizeHex(hex);
                if (!isValidHex(normalized)) {
                    return '#ffffff';
                }
                const weight = Math.max(0, Math.min(1, amount));
                const channels = [1, 3, 5].map((i) => parseInt(normalized.slice(i, i + 2), 16));
                const mixed = channels.map((channel) => Math.round((channel * weight) + (255 * (1 - weight))));
                return '#' + mixed.map((n) => n.toString(16).padStart(2, '0')).join('');
            };

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

            const applyScrollDisplayUi = (scrollDisplayType) => {
                const isColored = scrollDisplayType === scrollColored;
                if (scrollbarColors) {
                    scrollbarColors.hidden = !isColored;
                }
                if (previewScrollColored) {
                    previewScrollColored.classList.toggle('hidden', !isColored);
                }
                if (previewScrollIndicator) {
                    previewScrollIndicator.classList.toggle('hidden', isColored);
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
                const modalOverlayColor = normalizeHex(form.querySelector('[name="modal_overlay_color"]').value);
                const footerBackground = normalizeHex(form.querySelector('[name="footer_background_color"]').value);
                const footerText = normalizeHex(form.querySelector('[name="footer_text_color"]').value);
                const footerLink = normalizeHex(form.querySelector('[name="footer_link_color"]').value);
                const footerLinkHover = normalizeHex(form.querySelector('[name="footer_link_hover_color"]').value);
                const scrollDisplayType = getRadioValue('scroll_display_type') || scrollColored;
                const modalOverlayStyle = getRadioValue('modal_overlay_style') || defaults.modal_overlay_style || 'blur';
                const headingFont = getRadioValue('heading_font');
                const bodyFont = getRadioValue('body_font');
                const buttonRadius = getRadioValue('button_radius');
                const cardRadius = getRadioValue('card_radius');
                const density = getRadioValue('layout_density');
                const densityTokens = densityMap[density] || densityMap.standard;
                const overlayTokens = modalOverlayTokens[modalOverlayStyle] || modalOverlayTokens.blur;
                const overlayOpacity = formatOpacity(overlayTokens.opacity);
                const overlayFilter = overlayTokens.blur === '0px' ? 'none' : 'blur(' + overlayTokens.blur + ')';

                if (isValidHex(primary)) {
                    preview.style.setProperty('--site-primary', primary);
                }
                if (isValidHex(secondary)) {
                    preview.style.setProperty('--site-secondary', secondary);
                    preview.style.setProperty('--site-secondary-soft', mixHexWithWhite(secondary, {{ \App\Models\DesignSetting::SECONDARY_SOFT_MIX_AMOUNT }}));
                    if (scrollOptions) {
                        scrollOptions.style.setProperty('--site-secondary', secondary);
                    }
                }
                if (isValidHex(background)) {
                    preview.style.setProperty('--site-background', background);
                    preview.style.background = background;
                    if (scrollOptions) {
                        scrollOptions.style.setProperty('--site-background', background);
                    }
                }
                if (isValidHex(text)) {
                    preview.style.setProperty('--site-text', text);
                    preview.style.color = text;
                    if (scrollOptions) {
                        scrollOptions.style.setProperty('--site-text', text);
                    }
                }
                if (isValidHex(scrollbarThumb)) {
                    preview.style.setProperty('--site-scrollbar-thumb', scrollbarThumb);
                    if (scrollOptions) {
                        scrollOptions.style.setProperty('--site-scrollbar-thumb', scrollbarThumb);
                    }
                }
                if (isValidHex(scrollbarTrack)) {
                    preview.style.setProperty('--site-scrollbar-track', scrollbarTrack);
                    if (scrollOptions) {
                        scrollOptions.style.setProperty('--site-scrollbar-track', scrollbarTrack);
                    }
                }
                if (isValidHex(scrollbarThumbHover)) {
                    preview.style.setProperty('--site-scrollbar-thumb-hover', scrollbarThumbHover);
                }
                if (isValidHex(modalOverlayColor)) {
                    const overlayRgb = hexToRgbChannels(modalOverlayColor);
                    preview.style.setProperty('--site-modal-overlay-color', modalOverlayColor);
                    preview.style.setProperty('--site-modal-overlay-rgb', overlayRgb);
                    preview.style.setProperty('--site-modal-overlay-opacity', overlayOpacity);
                    preview.style.setProperty('--site-modal-overlay-filter', overlayFilter);
                    if (modalOverlayLivePreview) {
                        modalOverlayLivePreview.style.setProperty('--site-modal-overlay-rgb', overlayRgb);
                        modalOverlayLivePreview.style.setProperty('--site-modal-overlay-opacity', overlayOpacity);
                        modalOverlayLivePreview.style.setProperty('--site-modal-overlay-filter', overlayFilter);
                    }
                    if (modalOverlayOptions) {
                        modalOverlayOptions.querySelectorAll('.design-modal-overlay-option-preview').forEach((el) => {
                            el.style.setProperty('--design-modal-overlay-rgb', overlayRgb);
                        });
                    }
                }
                if (footerPreview) {
                    if (isValidHex(footerBackground)) {
                        footerPreview.style.background = footerBackground;
                        preview.style.setProperty('--site-footer-bg', footerBackground);
                    }
                    if (isValidHex(footerText)) {
                        footerPreview.style.color = footerText;
                        preview.style.setProperty('--site-footer-text', footerText);
                    }
                    if (isValidHex(footerLink)) {
                        preview.style.setProperty('--site-footer-link', footerLink);
                        footerPreview.querySelectorAll('[data-design-footer-preview-link]').forEach((el) => {
                            el.style.color = footerLink;
                        });
                    }
                    if (isValidHex(footerLinkHover)) {
                        preview.style.setProperty('--site-footer-link-hover', footerLinkHover);
                        footerPreview.style.setProperty('--site-footer-link-hover', footerLinkHover);
                    }
                }

                preview.style.setProperty('--site-heading-font', fontStacks[headingFont] || fontStacks.serif);
                preview.style.setProperty('--site-body-font', fontStacks[bodyFont] || fontStacks.sans);
                preview.style.fontFamily = fontStacks[bodyFont] || fontStacks.sans;
                preview.style.setProperty('--site-button-radius', buttonRadiusMap[buttonRadius] || buttonRadiusMap.large);
                preview.style.setProperty('--site-card-radius', cardRadiusMap[cardRadius] || cardRadiusMap.medium);
                preview.style.setProperty('--site-section-spacing', densityTokens.section_spacing);
                preview.style.setProperty('--site-card-padding', densityTokens.card_padding);
                preview.style.borderRadius = cardRadiusMap[cardRadius] || cardRadiusMap.medium;

                applyScrollDisplayUi(scrollDisplayType === scrollIndicator ? scrollIndicator : scrollColored);
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
                form.querySelector('[name="modal_overlay_color"]').value = defaults.modal_overlay_color;
                form.querySelector('[name="footer_background_color"]').value = defaults.footer_background_color;
                form.querySelector('[name="footer_text_color"]').value = defaults.footer_text_color;
                form.querySelector('[name="footer_link_color"]').value = defaults.footer_link_color;
                form.querySelector('[name="footer_link_hover_color"]').value = defaults.footer_link_hover_color;
                form.querySelectorAll('[data-design-color-field]').forEach((field) => {
                    const swatch = field.querySelector('[data-design-color-swatch]');
                    const hex = field.querySelector('[data-design-color-hex]');
                    if (swatch && hex) {
                        swatch.value = hex.value;
                    }
                });
                setRadioValue('scroll_display_type', defaults.scroll_display_type || scrollColored);
                setRadioValue('news_detail_display', defaults.news_detail_display || @json($detailPage));
                setRadioValue('blog_detail_display', defaults.blog_detail_display || @json($detailPage));
                setRadioValue('gallery_detail_display', defaults.gallery_detail_display || @json($detailPage));
                setRadioValue('modal_overlay_style', defaults.modal_overlay_style || 'blur');
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
