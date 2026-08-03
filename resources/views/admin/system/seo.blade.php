@extends('layouts.admin')

@section('heading', 'SEO')

@section('save-bar')
    <div class="flex min-w-0 flex-wrap items-center gap-3">
        <button
            type="button"
            class="admin-btn shadow-md shrink-0"
            data-admin-confirm-trigger
            data-confirm-form="seo-form"
            data-confirm-title="SEO設定保存の確認"
            data-confirm-message="SEO設定を保存します。&#10;よろしいですか？"
            data-confirm-note="サイト基本SEO、ファビコン、OGP、検索エンジン設定など、現在入力されている内容が反映されます。"
            data-confirm-submit-label="保存する"
        >保存する</button>
        <p class="text-sm text-admin-muted">
            検索結果やSNSで表示されるサイト情報を設定します。
        </p>
    </div>

@endsection

@section('content')
    @php
        $twitterCard = old('twitter_card', $setting->twitter_card ?: \App\Models\SalonSetting::TWITTER_CARD_SUMMARY_LARGE_IMAGE);
        $noindex = (string) old('noindex', $setting->noindex ? '1' : '0');
        $hasOgImage = (bool) $setting->og_image;
        $hasFavicon = (bool) $setting->favicon_path;
        $sitemapUrl = url('/sitemap.xml');
        $robotsUrl = url('/robots.txt');
        $appUrl = rtrim(config('app.url') ?: url('/'), '/');
        $shareDomain = parse_url($appUrl, PHP_URL_HOST) ?: 'example.com';
        $shopNameFallback = (string) ($setting->shop_name ?: 'Sun＆ Me');
        $initialSiteTitle = (string) old('site_title', $setting->site_title);
        $initialMetaDescription = (string) old('meta_description', $setting->meta_description);
        $initialOgTitle = (string) old('og_title', $setting->og_title);
        $initialOgDescription = (string) old('og_description', $setting->og_description);
        $ogImageUrl = $hasOgImage ? asset('storage/'.$setting->og_image) : null;
        $faviconUrl = $hasFavicon ? asset('storage/'.$setting->favicon_path) : null;
    @endphp

    
    <form
        id="seo-form"
        method="POST"
        action="{{ route('admin.system.seo.update') }}"
        enctype="multipart/form-data"
        class="space-y-5"
        data-seo-form
        data-shop-name="{{ $shopNameFallback }}"
        data-app-url="{{ $appUrl }}"
        data-share-domain="{{ $shareDomain }}"
        data-og-image-url="{{ $ogImageUrl }}"
    >
        @csrf @method('PUT')

        <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-2">
            {{-- サイト基本SEO --}}
            <div class="admin-card space-y-4">
                <div>
                    <h2 class="text-base font-medium text-admin-text">サイト基本SEO</h2>
                    <p class="mt-1 text-sm text-admin-muted">検索結果やブラウザタブに表示される基本情報を設定します。</p>
                </div>

                <div>
                    <div class="mb-1 flex items-center justify-between gap-3">
                        <label for="site_title" class="admin-label mb-0">サイトタイトル</label>
                        <span
                            class="seo-char-count text-xs text-admin-muted tabular-nums"
                            data-char-count="site_title"
                            data-guide="60"
                        >0 / 60</span>
                    </div>
                    <input
                        type="text"
                        name="site_title"
                        id="site_title"
                        value="{{ $initialSiteTitle }}"
                        maxlength="255"
                        class="admin-input"
                        placeholder="{{ $shopNameFallback }}"
                        data-seo-input="site_title"
                    >
                    <p class="mt-1 text-xs text-gray-500">未入力時は店名をサイトタイトルとして使用します。目安は60文字です。</p>
                    @error('site_title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="mb-1 flex items-center justify-between gap-3">
                        <label for="meta_description" class="admin-label mb-0">メタディスクリプション</label>
                        <span
                            class="seo-char-count text-xs text-admin-muted tabular-nums"
                            data-char-count="meta_description"
                            data-guide="120"
                        >0 / 120</span>
                    </div>
                    <textarea
                        name="meta_description"
                        id="meta_description"
                        rows="3"
                        maxlength="1000"
                        class="admin-input seo-compact-textarea"
                        placeholder="サロンの魅力が伝わる短い説明文を入力してください"
                        data-seo-input="meta_description"
                    >{{ $initialMetaDescription }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">検索結果のスニペットに使われます。目安は120文字です。</p>
                    @error('meta_description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="meta_keywords" class="admin-label">メタキーワード（任意）</label>
                    <input
                        type="text"
                        name="meta_keywords"
                        id="meta_keywords"
                        value="{{ old('meta_keywords', $setting->meta_keywords) }}"
                        maxlength="500"
                        class="admin-input"
                        placeholder="例: 美容室, ヘアサロン, カット"
                    >
                    <p class="mt-1 text-xs text-gray-500">カンマ区切りで入力できます。多くの検索エンジンでは重視されません。</p>
                    @error('meta_keywords')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <span class="admin-label">ファビコン</span>
                    <div class="mt-1.5">
                        <div
                            id="favicon-dropzone"
                            data-favicon-dropzone
                            class="banner-dropzone cursor-pointer {{ $hasFavicon ? 'overflow-hidden rounded-lg' : 'is-empty' }}"
                        >
                            <div data-favicon-preview class="{{ $hasFavicon ? '' : 'hidden' }} flex items-center justify-center bg-white p-4">
                                @if($hasFavicon)
                                    <img
                                        id="favicon-preview"
                                        src="{{ $faviconUrl }}"
                                        alt="ファビコンプレビュー"
                                        class="h-10 w-10 object-contain"
                                        data-favicon-image
                                    >
                                @endif
                            </div>
                            <div
                                data-favicon-placeholder
                                class="banner-dropzone-placeholder {{ $hasFavicon ? 'hidden' : '' }}"
                            >
                                <div class="banner-dropzone-main">
                                    <svg class="banner-dropzone-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <rect x="3.5" y="5.5" width="17" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                        <circle cx="9" cy="10.5" r="1.5" fill="currentColor" opacity="0.7"/>
                                        <path d="M5.5 16.5l4-3.5 2.5 2 3.5-3.5 3 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <p class="banner-dropzone-text text-sm text-gray-700">画像をドラッグ＆ドロップ、またはクリックして選択</p>
                                </div>
                                <p class="banner-dropzone-hint mt-1.5 text-xs text-gray-500">
                                    ICO・PNG・SVG・WebP、1MBまで。未設定時はデフォルトのサイトロゴ（S＋ビーグル）を使用します。
                                </p>
                            </div>
                            <p class="banner-dropzone-drag-message" aria-hidden="true">ここにファビコンをドロップしてください</p>
                        </div>
                        <p id="favicon-filename" class="mt-1.5 hidden text-sm text-gray-600"></p>
                        <p class="mt-1 text-xs text-admin-muted {{ $hasFavicon ? '' : 'hidden' }}" data-favicon-replace-hint>クリックまたは DnD で画像を変更できます</p>
                        <p id="favicon-error" class="mt-1.5 hidden text-sm text-red-600" role="alert"></p>
                        @error('favicon')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <input type="file" name="favicon" id="favicon" accept=".ico,image/png,image/svg+xml,image/webp,image/jpeg" class="hidden">
                </div>
            </div>

            {{-- SNS（OGP） --}}
            <div class="admin-card space-y-3.5">
                <div>
                    <h2 class="text-base font-medium text-admin-text">SNS（OGP）</h2>
                    <p class="mt-1 text-sm text-admin-muted">SNSでシェアされたときに表示されるプレビュー情報を設定します。</p>
                </div>

                <div class="space-y-3">
                    <div>
                        <div class="mb-1 flex items-center justify-between gap-3">
                            <label for="og_title" class="admin-label mb-0">OGPタイトル</label>
                            <span
                                class="seo-char-count text-xs text-admin-muted tabular-nums"
                                data-char-count="og_title"
                                data-guide="60"
                            >0 / 60</span>
                        </div>
                        <input
                            type="text"
                            name="og_title"
                            id="og_title"
                            value="{{ $initialOgTitle }}"
                            maxlength="255"
                            class="admin-input"
                            placeholder="未入力時はサイトタイトルを使用"
                            data-seo-input="og_title"
                        >
                        @error('og_title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <div class="mb-1 flex items-center justify-between gap-3">
                            <label for="og_description" class="admin-label mb-0">OGPディスクリプション</label>
                            <span
                                class="seo-char-count text-xs text-admin-muted tabular-nums"
                                data-char-count="og_description"
                                data-guide="120"
                            >0 / 120</span>
                        </div>
                        <textarea
                            name="og_description"
                            id="og_description"
                            rows="2"
                            maxlength="1000"
                            class="admin-input seo-compact-textarea"
                            placeholder="未入力時はメタディスクリプションを使用"
                            data-seo-input="og_description"
                        >{{ $initialOgDescription }}</textarea>
                        @error('og_description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <span class="admin-label">OGP画像</span>
                    <div class="mt-1.5">
                        <div
                            id="og-image-dropzone"
                            data-og-dropzone
                            class="banner-dropzone seo-og-dropzone cursor-pointer {{ $hasOgImage ? 'overflow-hidden rounded-lg' : 'is-empty' }}"
                        >
                            <div data-og-preview class="{{ $hasOgImage ? '' : 'hidden' }}">
                                @if($hasOgImage)
                                    <img
                                        id="og-image-preview"
                                        src="{{ $ogImageUrl }}"
                                        alt="OGP画像プレビュー"
                                        class="seo-og-dropzone-image w-full object-cover"
                                        data-og-image
                                    >
                                @endif
                            </div>
                            <div
                                data-og-placeholder
                                class="banner-dropzone-placeholder seo-og-dropzone-placeholder {{ $hasOgImage ? 'hidden' : '' }}"
                            >
                                <div class="banner-dropzone-main">
                                    <svg class="banner-dropzone-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <rect x="3.5" y="5.5" width="17" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                        <circle cx="9" cy="10.5" r="1.5" fill="currentColor" opacity="0.7"/>
                                        <path d="M5.5 16.5l4-3.5 2.5 2 3.5-3.5 3 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <p class="banner-dropzone-text text-sm text-gray-700">画像をドラッグ＆ドロップ、またはクリックして選択</p>
                                </div>
                                <p class="banner-dropzone-hint mt-1.5 text-xs text-gray-500">
                                    推奨 1200×630 / JPEG・PNG・WebP、5MBまで
                                </p>
                            </div>
                            <p class="banner-dropzone-drag-message" aria-hidden="true">ここにOGP画像をドロップしてください</p>
                        </div>
                        <p id="og-image-filename" class="mt-1.5 hidden text-sm text-gray-600"></p>
                        <p class="mt-1 text-xs text-admin-muted {{ $hasOgImage ? '' : 'hidden' }}" data-og-replace-hint>クリックまたは DnD で画像を変更できます</p>
                        <p id="og-image-error" class="mt-1.5 hidden text-sm text-red-600" role="alert"></p>
                        @error('og_image')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <input type="file" name="og_image" id="og_image" accept="image/jpeg,image/png,image/webp" class="hidden">
                </div>

                <div class="seo-twitter-card-field">
                    <span class="admin-label" id="twitter_card_label">Twitterカード種別</span>
                    <div class="admin-segmented mt-1" role="radiogroup" aria-labelledby="twitter_card_label">
                        <label class="admin-segmented-option">
                            <input
                                type="radio"
                                name="twitter_card"
                                value="summary_large_image"
                                class="admin-segmented-input"
                                {{ $twitterCard === 'summary_large_image' ? 'checked' : '' }}
                            >
                            <span class="admin-segmented-face">
                                <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                    <rect x="2.5" y="4" width="11" height="8" rx="1.2" stroke="currentColor" stroke-width="1.35"/>
                                    <path d="M4.5 9.5 6.2 7.8 7.5 9.1 9.8 6.5 11.5 9.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span class="admin-segmented-text">大きい画像</span>
                            </span>
                        </label>
                        <label class="admin-segmented-option">
                            <input
                                type="radio"
                                name="twitter_card"
                                value="summary"
                                class="admin-segmented-input"
                                {{ $twitterCard === 'summary' ? 'checked' : '' }}
                            >
                            <span class="admin-segmented-face">
                                <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                    <rect x="2.5" y="2.5" width="5.5" height="5.5" rx="1" stroke="currentColor" stroke-width="1.35"/>
                                    <path d="M9.5 4.25h4M9.5 6.75h3" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
                                    <path d="M2.5 10.25h11M2.5 12.75h8" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
                                </svg>
                                <span class="admin-segmented-text">小さい画像</span>
                            </span>
                        </label>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">X（Twitter）で共有された際のカード表示形式です。通常は「大きい画像」をおすすめします。</p>
                    @error('twitter_card')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

            {{-- プレビュー（PC: 横並び / 狭幅: Google → SNS） --}}
        <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-2 lg:gap-5" data-seo-previews>
            <div class="admin-card seo-preview-card space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="text-base font-medium text-admin-text">Google検索プレビュー</h2>
                        <p class="mt-1 text-sm text-admin-muted">検索結果に近い表示イメージです。入力内容に応じてリアルタイムで更新されます。</p>
                    </div>
                    <p class="shrink-0 text-xs text-admin-muted">Google検索結果イメージ</p>
                </div>
                <div class="seo-google-preview">
                    <p class="seo-google-title" data-google-title></p>
                    <p class="seo-google-url" data-google-url></p>
                    <p class="seo-google-desc" data-google-desc></p>
                </div>
            </div>

            <div class="admin-card seo-preview-card space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="text-base font-medium text-admin-text">SNSシェアプレビュー</h2>
                        <p class="mt-1 text-sm text-admin-muted">LINEやFacebookなどで共有された際の表示イメージです。</p>
                    </div>
                    <p class="shrink-0 text-xs text-admin-muted">Facebook・LINE・X共有イメージ</p>
                </div>
                <div class="seo-og-preview">
                    <div class="seo-og-preview-media {{ $hasOgImage ? '' : 'is-empty' }}" data-sns-image-wrap>
                        <img
                            data-sns-image
                            alt=""
                            class="seo-og-preview-image {{ $hasOgImage ? '' : 'hidden' }}"
                            @if($hasOgImage) src="{{ $ogImageUrl }}" @endif
                        >
                        <div
                            data-sns-image-placeholder
                            class="seo-og-preview-placeholder {{ $hasOgImage ? 'hidden' : '' }}"
                        >
                            <div class="seo-og-placeholder-main">
                                <svg class="seo-og-placeholder-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <rect x="3.5" y="5.5" width="17" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                    <circle cx="9" cy="10.5" r="1.5" fill="currentColor" opacity="0.7"/>
                                    <path d="M5.5 16.5l4-3.5 2.5 2 3.5-3.5 3 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <p class="seo-og-placeholder-label">OGP画像未設定</p>
                            </div>
                            <p class="seo-og-placeholder-hint">推奨サイズ 1200×630px</p>
                            <p class="seo-og-placeholder-hint">保存後にここへプレビュー表示</p>
                        </div>
                    </div>
                    <div class="seo-og-preview-body">
                        <p class="seo-og-preview-domain" data-sns-domain>{{ $shareDomain }}</p>
                        <p class="seo-og-preview-title" data-sns-title></p>
                        <p class="seo-og-preview-desc" data-sns-desc></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- 検索エンジン --}}
        <div class="admin-card space-y-5">
            <div>
                <h2 class="text-base font-medium text-admin-text">検索エンジン</h2>
                <p class="mt-1 text-sm text-admin-muted">検索エンジンへの公開可否と、サイトマップ／robots.txt のURLを確認できます。</p>
            </div>

            <div>
                <span class="admin-label" id="noindex_label">検索結果への表示</span>
                <div class="admin-segmented mt-1" role="radiogroup" aria-labelledby="noindex_label">
                    <label class="admin-segmented-option">
                        <input type="radio" name="noindex" value="0" class="admin-segmented-input" {{ $noindex === '0' ? 'checked' : '' }} data-noindex-option>
                        <span class="admin-segmented-face">
                            <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                <circle cx="8" cy="8" r="5.25" stroke="currentColor" stroke-width="1.35"/>
                                <path d="M5.5 8.1 7.1 9.7 10.6 6.2" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span class="admin-segmented-text">インデックスする</span>
                        </span>
                    </label>
                    <label class="admin-segmented-option">
                        <input type="radio" name="noindex" value="1" class="admin-segmented-input" {{ $noindex === '1' ? 'checked' : '' }} data-noindex-option>
                        <span class="admin-segmented-face">
                            <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                <circle cx="8" cy="8" r="5.25" stroke="currentColor" stroke-width="1.35"/>
                                <path d="M5.75 5.75 10.25 10.25M10.25 5.75 5.75 10.25" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
                            </svg>
                            <span class="admin-segmented-text">インデックスしない</span>
                        </span>
                    </label>
                </div>
                <p class="mt-2 text-xs text-gray-500" data-noindex-help></p>
                <div
                    data-noindex-warning
                    class="mt-3 {{ $noindex === '1' ? '' : 'hidden' }} rounded-lg border border-amber-200/80 bg-[#FBF3E8] px-3.5 py-3 text-sm leading-relaxed text-[#8A5A2B]"
                    role="status"
                >
                    この設定では、公開サイトがGoogleなどの検索結果に表示されない可能性があります。
                </div>
                @error('noindex')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-4 border-t border-admin-border/60 pt-5">
                <div>
                    <label for="sitemap-url" class="admin-label">sitemap.xml</label>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <input
                            type="text"
                            id="sitemap-url"
                            value="{{ $sitemapUrl }}"
                            readonly
                            class="admin-input min-w-0 flex-1 bg-admin-bg/60"
                        >
                        <a
                            href="{{ $sitemapUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="admin-btn-secondary inline-flex shrink-0 items-center gap-1.5 px-3"
                        >
                            <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                <path d="M6.5 3.5H4.2A1.7 1.7 0 0 0 2.5 5.2v6.6A1.7 1.7 0 0 0 4.2 13.5h6.6a1.7 1.7 0 0 0 1.7-1.7V9.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
                                <path d="M9.5 2.5h4v4M13.5 2.5 8 8" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            開く
                        </a>
                        <button
                            type="button"
                            class="admin-btn-secondary inline-flex shrink-0 items-center gap-1.5 px-3"
                            data-copy-target="sitemap-url"
                        >
                            <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                <rect x="5.25" y="5.25" width="7.5" height="7.5" rx="1.2" stroke="currentColor" stroke-width="1.35"/>
                                <path d="M10.75 5.25V4.2A1.7 1.7 0 0 0 9.05 2.5H4.2A1.7 1.7 0 0 0 2.5 4.2v4.85A1.7 1.7 0 0 0 4.2 10.75h1.05" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
                            </svg>
                            コピー
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">公開後、このsitemap.xml URLをGoogle Search Consoleへ登録してください。</p>
                </div>

                <div>
                    <label for="robots-txt-url" class="admin-label">robots.txt</label>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <input
                            type="text"
                            id="robots-txt-url"
                            value="{{ $robotsUrl }}"
                            readonly
                            class="admin-input min-w-0 flex-1 bg-admin-bg/60"
                        >
                        <a
                            href="{{ $robotsUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="admin-btn-secondary inline-flex shrink-0 items-center gap-1.5 px-3"
                        >
                            <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                <path d="M6.5 3.5H4.2A1.7 1.7 0 0 0 2.5 5.2v6.6A1.7 1.7 0 0 0 4.2 13.5h6.6a1.7 1.7 0 0 0 1.7-1.7V9.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
                                <path d="M9.5 2.5h4v4M13.5 2.5 8 8" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            開く
                        </a>
                        <button
                            type="button"
                            class="admin-btn-secondary inline-flex shrink-0 items-center gap-1.5 px-3"
                            data-copy-target="robots-txt-url"
                        >
                            <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                <rect x="5.25" y="5.25" width="7.5" height="7.5" rx="1.2" stroke="currentColor" stroke-width="1.35"/>
                                <path d="M10.75 5.25V4.2A1.7 1.7 0 0 0 9.05 2.5H4.2A1.7 1.7 0 0 0 2.5 4.2v4.85A1.7 1.7 0 0 0 4.2 10.75h1.05" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
                            </svg>
                            コピー
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        (function () {
            const form = document.querySelector('[data-seo-form]');
            if (!form) {
                return;
            }

            const shopName = form.getAttribute('data-shop-name') || '';
            const appUrl = form.getAttribute('data-app-url') || '';
            const shareDomain = form.getAttribute('data-share-domain') || '';
            let currentOgImageUrl = form.getAttribute('data-og-image-url') || '';

            const siteTitleInput = document.getElementById('site_title');
            const metaDescInput = document.getElementById('meta_description');
            const ogTitleInput = document.getElementById('og_title');
            const ogDescInput = document.getElementById('og_description');

            const googleTitleEl = document.querySelector('[data-google-title]');
            const googleUrlEl = document.querySelector('[data-google-url]');
            const googleDescEl = document.querySelector('[data-google-desc]');
            const snsTitleEl = document.querySelector('[data-sns-title]');
            const snsDescEl = document.querySelector('[data-sns-desc]');
            const snsDomainEl = document.querySelector('[data-sns-domain]');
            const snsImageEl = document.querySelector('[data-sns-image]');
            const snsPlaceholderEl = document.querySelector('[data-sns-image-placeholder]');
            const snsImageWrapEl = document.querySelector('[data-sns-image-wrap]');
            const noindexHelpEl = document.querySelector('[data-noindex-help]');
            const noindexWarningEl = document.querySelector('[data-noindex-warning]');

            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            const maxSize = 5 * 1024 * 1024;
            const ogErrorEl = document.getElementById('og-image-error');
            const ogInput = document.getElementById('og_image');
            const ogDropzone = document.getElementById('og-image-dropzone');
            const ogPreview = ogDropzone?.querySelector('[data-og-preview]');
            const ogPlaceholder = ogDropzone?.querySelector('[data-og-placeholder]');
            const ogFilenameEl = document.getElementById('og-image-filename');
            const ogReplaceHint = document.querySelector('[data-og-replace-hint]');

            const faviconAllowedExt = ['ico', 'png', 'svg', 'webp', 'jpg', 'jpeg'];
            const faviconMaxSize = 1 * 1024 * 1024;
            const faviconErrorEl = document.getElementById('favicon-error');
            const faviconInput = document.getElementById('favicon');
            const faviconDropzone = document.getElementById('favicon-dropzone');
            const faviconPreview = faviconDropzone?.querySelector('[data-favicon-preview]');
            const faviconPlaceholder = faviconDropzone?.querySelector('[data-favicon-placeholder]');
            const faviconFilenameEl = document.getElementById('favicon-filename');
            const faviconReplaceHint = document.querySelector('[data-favicon-replace-hint]');

            const helpIndex = 'Googleなどの検索結果にサイトを表示します。通常はこちらを選択してください。';
            const helpNoindex = '検索エンジンにサイトを載せません。公開ページに noindex の robots メタが出力されます。';

            function stripHtml(value) {
                const tmp = document.createElement('div');
                tmp.innerHTML = value || '';
                return (tmp.textContent || tmp.innerText || '').replace(/\s+/g, ' ').trim();
            }

            function truncateText(value, max) {
                const text = value || '';
                if (text.length <= max) {
                    return text;
                }
                return text.slice(0, Math.max(0, max - 1)).trimEnd() + '…';
            }

            function resolveSiteTitle() {
                const raw = stripHtml(siteTitleInput?.value || '');
                return raw || shopName || 'Sun＆ Me';
            }

            function resolveMetaDescription() {
                return stripHtml(metaDescInput?.value || '');
            }

            function resolveOgTitle() {
                const raw = stripHtml(ogTitleInput?.value || '');
                return raw || resolveSiteTitle();
            }

            function resolveOgDescription() {
                const raw = stripHtml(ogDescInput?.value || '');
                return raw || resolveMetaDescription();
            }

            function updateCharCount(inputId) {
                const input = document.getElementById(inputId);
                const counter = document.querySelector('[data-char-count="' + inputId + '"]');
                if (!input || !counter) {
                    return;
                }
                const guide = parseInt(counter.getAttribute('data-guide') || '0', 10);
                const length = (input.value || '').length;
                counter.textContent = length + ' / ' + guide;
                if (guide > 0 && length > guide) {
                    counter.classList.remove('text-admin-muted');
                    counter.classList.add('text-[#C47A3A]');
                } else {
                    counter.classList.add('text-admin-muted');
                    counter.classList.remove('text-[#C47A3A]');
                }
            }

            function updateGooglePreview() {
                if (googleTitleEl) {
                    googleTitleEl.textContent = truncateText(resolveSiteTitle(), 60);
                }
                if (googleUrlEl) {
                    googleUrlEl.textContent = appUrl || '';
                }
                if (googleDescEl) {
                    const desc = resolveMetaDescription();
                    googleDescEl.textContent = desc ? truncateText(desc, 160) : '';
                    googleDescEl.classList.toggle('hidden', !desc);
                }
            }

            function updateSnsImage(src) {
                if (!snsImageEl || !snsPlaceholderEl) {
                    return;
                }
                snsImageWrapEl?.classList.remove('is-logo-fallback');
                snsImageEl.classList.remove('is-logo-fallback');
                if (src) {
                    snsImageEl.src = src;
                    snsImageEl.classList.remove('hidden');
                    snsPlaceholderEl.classList.add('hidden');
                    snsImageWrapEl?.classList.remove('is-empty');
                } else {
                    snsImageEl.removeAttribute('src');
                    snsImageEl.classList.add('hidden');
                    snsPlaceholderEl.classList.remove('hidden');
                    snsImageWrapEl?.classList.add('is-empty');
                }
            }

            function updateSnsPreview() {
                if (snsDomainEl) {
                    snsDomainEl.textContent = shareDomain;
                }
                if (snsTitleEl) {
                    snsTitleEl.textContent = truncateText(resolveOgTitle(), 70);
                }
                if (snsDescEl) {
                    const desc = resolveOgDescription();
                    snsDescEl.textContent = desc ? truncateText(desc, 120) : '';
                    snsDescEl.classList.toggle('hidden', !desc);
                }
                updateSnsImage(currentOgImageUrl || '');
            }

            function updateNoindexHelp() {
                const selected = form.querySelector('input[name="noindex"]:checked');
                const isNoindex = selected?.value === '1';
                if (noindexHelpEl) {
                    noindexHelpEl.textContent = isNoindex ? helpNoindex : helpIndex;
                }
                if (noindexWarningEl) {
                    noindexWarningEl.classList.toggle('hidden', !isNoindex);
                }
            }

            function refreshSeoUi() {
                ['site_title', 'meta_description', 'og_title', 'og_description'].forEach(updateCharCount);
                updateGooglePreview();
                updateSnsPreview();
                updateNoindexHelp();
            }

            ['site_title', 'meta_description', 'og_title', 'og_description'].forEach(function (id) {
                const el = document.getElementById(id);
                el?.addEventListener('input', refreshSeoUi);
            });
            form.querySelectorAll('[data-noindex-option]').forEach(function (radio) {
                radio.addEventListener('change', updateNoindexHelp);
            });

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

            function fileExtension(name) {
                const parts = (name || '').split('.');
                return parts.length > 1 ? parts.pop().toLowerCase() : '';
            }

            function isValidFavicon(file, errorEl) {
                const ext = fileExtension(file.name);
                if (!faviconAllowedExt.includes(ext)) {
                    showImageError(errorEl, 'ICO / PNG / SVG / WebP / JPEG形式のファイルを選択してください。');
                    return false;
                }
                if (file.size > faviconMaxSize) {
                    showImageError(errorEl, 'ファビコンは1MB以下にしてください。');
                    return false;
                }
                return true;
            }

            function clearDropzoneDragState() {
                if (!ogDropzone) {
                    return;
                }
                ogDropzone._ogDragCounter = 0;
                ogDropzone.classList.remove('is-drag-active');
            }

            function clearFaviconDragState() {
                if (!faviconDropzone) {
                    return;
                }
                faviconDropzone._faviconDragCounter = 0;
                faviconDropzone.classList.remove('is-drag-active');
            }

            function showOgPreview(file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    let img = document.getElementById('og-image-preview');
                    if (!img && ogPreview) {
                        img = document.createElement('img');
                        img.id = 'og-image-preview';
                        img.className = 'seo-og-dropzone-image w-full object-cover';
                        img.setAttribute('data-og-image', '');
                        img.alt = 'OGP画像プレビュー';
                        ogPreview.appendChild(img);
                    }
                    if (img) {
                        img.src = e.target.result;
                    }
                    currentOgImageUrl = e.target.result || '';
                    ogPlaceholder?.classList.add('hidden');
                    ogPreview?.classList.remove('hidden');
                    ogDropzone?.classList.remove('is-empty');
                    ogDropzone?.classList.add('overflow-hidden', 'rounded-lg');
                    ogReplaceHint?.classList.remove('hidden');
                    updateSnsPreview();
                };
                reader.readAsDataURL(file);
            }

            function handleOgFile(file) {
                clearImageError(ogErrorEl);

                if (!file || !isValid(file, ogErrorEl)) {
                    if (ogInput) {
                        ogInput.value = '';
                    }
                    if (ogFilenameEl) {
                        ogFilenameEl.textContent = '';
                        ogFilenameEl.classList.add('hidden');
                    }
                    return;
                }

                const dt = new DataTransfer();
                dt.items.add(file);
                ogInput.files = dt.files;
                if (ogFilenameEl) {
                    ogFilenameEl.textContent = '選択中: ' + file.name;
                    ogFilenameEl.classList.remove('hidden');
                }
                showOgPreview(file);
            }

            ogDropzone?.addEventListener('click', function () {
                ogInput?.click();
            });
            ogInput?.addEventListener('change', function () {
                handleOgFile(ogInput.files[0]);
            });
            ogDropzone?.addEventListener('dragenter', function (e) {
                e.preventDefault();
                e.stopPropagation();
                ogDropzone._ogDragCounter = (ogDropzone._ogDragCounter || 0) + 1;
                ogDropzone.classList.add('is-drag-active');
            });
            ogDropzone?.addEventListener('dragover', function (e) {
                e.preventDefault();
                e.stopPropagation();
                ogDropzone.classList.add('is-drag-active');
            });
            ogDropzone?.addEventListener('dragleave', function (e) {
                e.preventDefault();
                e.stopPropagation();
                ogDropzone._ogDragCounter = Math.max(0, (ogDropzone._ogDragCounter || 0) - 1);
                if (ogDropzone._ogDragCounter === 0) {
                    ogDropzone.classList.remove('is-drag-active');
                }
            });
            ogDropzone?.addEventListener('drop', function (e) {
                e.preventDefault();
                e.stopPropagation();
                clearDropzoneDragState();
                handleOgFile(e.dataTransfer.files[0]);
            });

            function showFaviconPreview(file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    let img = document.getElementById('favicon-preview');
                    if (!img && faviconPreview) {
                        img = document.createElement('img');
                        img.id = 'favicon-preview';
                        img.className = 'h-10 w-10 object-contain';
                        img.setAttribute('data-favicon-image', '');
                        img.alt = 'ファビコンプレビュー';
                        faviconPreview.appendChild(img);
                    }
                    if (img) {
                        img.src = e.target.result;
                    }
                    faviconPlaceholder?.classList.add('hidden');
                    faviconPreview?.classList.remove('hidden');
                    faviconDropzone?.classList.remove('is-empty');
                    faviconDropzone?.classList.add('overflow-hidden', 'rounded-lg');
                    faviconReplaceHint?.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }

            function handleFaviconFile(file) {
                clearImageError(faviconErrorEl);

                if (!file || !isValidFavicon(file, faviconErrorEl)) {
                    if (faviconInput) {
                        faviconInput.value = '';
                    }
                    if (faviconFilenameEl) {
                        faviconFilenameEl.textContent = '';
                        faviconFilenameEl.classList.add('hidden');
                    }
                    return;
                }

                const dt = new DataTransfer();
                dt.items.add(file);
                faviconInput.files = dt.files;
                if (faviconFilenameEl) {
                    faviconFilenameEl.textContent = '選択中: ' + file.name;
                    faviconFilenameEl.classList.remove('hidden');
                }
                showFaviconPreview(file);
            }

            faviconDropzone?.addEventListener('click', function () {
                faviconInput?.click();
            });
            faviconInput?.addEventListener('change', function () {
                handleFaviconFile(faviconInput.files[0]);
            });
            faviconDropzone?.addEventListener('dragenter', function (e) {
                e.preventDefault();
                e.stopPropagation();
                faviconDropzone._faviconDragCounter = (faviconDropzone._faviconDragCounter || 0) + 1;
                faviconDropzone.classList.add('is-drag-active');
            });
            faviconDropzone?.addEventListener('dragover', function (e) {
                e.preventDefault();
                e.stopPropagation();
                faviconDropzone.classList.add('is-drag-active');
            });
            faviconDropzone?.addEventListener('dragleave', function (e) {
                e.preventDefault();
                e.stopPropagation();
                faviconDropzone._faviconDragCounter = Math.max(0, (faviconDropzone._faviconDragCounter || 0) - 1);
                if (faviconDropzone._faviconDragCounter === 0) {
                    faviconDropzone.classList.remove('is-drag-active');
                }
            });
            faviconDropzone?.addEventListener('drop', function (e) {
                e.preventDefault();
                e.stopPropagation();
                clearFaviconDragState();
                handleFaviconFile(e.dataTransfer.files[0]);
            });

            document.querySelectorAll('[data-copy-target]').forEach(function (button) {
                button.addEventListener('click', async function () {
                    const targetId = button.getAttribute('data-copy-target');
                    const input = targetId ? document.getElementById(targetId) : null;
                    const value = input?.value || '';
                    if (!value) {
                        return;
                    }

                    try {
                        if (navigator.clipboard?.writeText) {
                            await navigator.clipboard.writeText(value);
                        } else {
                            input.focus();
                            input.select();
                            document.execCommand('copy');
                            input.blur();
                        }
                        if (typeof window.showToast === 'function') {
                            window.showToast('URLをコピーしました。', 'success');
                        }
                    } catch (e) {
                        if (typeof window.showToast === 'function') {
                            window.showToast('コピーに失敗しました。', 'error');
                        }
                    }
                });
            });

            refreshSeoUi();
        })();
    </script>
@endsection
