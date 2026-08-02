{{-- Expects: $heroImages, $heroCount, $maxHeroImages --}}
@if ($errors->any())
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <ul class="list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div id="hero-images-list" class="grid grid-cols-1 gap-4 lg:grid-cols-2" data-hero-grid>
    @foreach($heroImages as $index => $image)
        @php
            $prefix = 'hero_images.'.$image->id;
            $publishedOld = old($prefix.'.is_published', $image->is_published ? '1' : '0');
            $isPublished = in_array((string) $publishedOld, ['1', 'true', 'on'], true);
            $sortOrder = old($prefix.'.sort_order', $image->sort_order);
            $altText = trim((string) old($prefix.'.alt_text', $image->alt_text));
            $fallbackTitle = 'メインビジュアル'.($index + 1);
            $headingTitle = $altText !== '' ? $altText : $fallbackTitle;
        @endphp
        <div
            class="admin-card hero-card"
            data-hero-card
            data-hero-id="{{ $image->id }}"
            data-hero-existing
        >
            <div class="mb-3 flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <span
                        class="hero-drag-handle"
                        data-hero-drag-handle
                        draggable="true"
                        role="button"
                        tabindex="0"
                        aria-label="メインビジュアルを並び替え"
                        title="ドラッグして並び替え"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <circle cx="7" cy="5" r="1.25"/>
                            <circle cx="13" cy="5" r="1.25"/>
                            <circle cx="7" cy="10" r="1.25"/>
                            <circle cx="13" cy="10" r="1.25"/>
                            <circle cx="7" cy="15" r="1.25"/>
                            <circle cx="13" cy="15" r="1.25"/>
                        </svg>
                    </span>
                    <p class="banner-card-label truncate text-sm font-medium text-gray-800" data-hero-card-title title="{{ $headingTitle }}">{{ $headingTitle }}</p>
                </div>
                <button
                    type="button"
                    class="admin-icon-btn admin-icon-btn-delete"
                    data-hero-remove
                    aria-label="削除"
                    title="削除"
                >
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <input type="hidden" name="hero_images[{{ $image->id }}][sort_order]" value="{{ $sortOrder }}" data-hero-order>

            <div class="mb-3">
                <div class="overflow-hidden rounded-lg">
                    <img
                        src="{{ asset('storage/'.$image->image_path) }}"
                        alt=""
                        class="aspect-[16/9] w-full object-cover"
                        data-hero-image
                    >
                </div>
            </div>

            <div class="space-y-3">
                <div>
                    <label for="hero_alt_{{ $image->id }}" class="admin-label">altテキスト</label>
                    <input
                        type="text"
                        name="hero_images[{{ $image->id }}][alt_text]"
                        id="hero_alt_{{ $image->id }}"
                        value="{{ old($prefix.'.alt_text', $image->alt_text) }}"
                        maxlength="255"
                        class="admin-input"
                        placeholder="例: サロン内観のメインビジュアル"
                        data-hero-alt-input
                    >
                    <p class="mt-1 text-xs text-gray-500">検索・アクセシビリティ向上のため入力を推奨します。未入力も保存できます。</p>
                </div>
                <div>
                    <span class="admin-label">公開</span>
                    <div class="admin-segmented mt-1" role="radiogroup" aria-label="公開状態">
                        <label class="admin-segmented-option">
                            <input type="radio" name="hero_images[{{ $image->id }}][is_published]" value="1" class="admin-segmented-input" @checked($isPublished)>
                            <span class="admin-segmented-face">
                                <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                    <path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>
                                    <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.35"/>
                                </svg>
                                <span class="admin-segmented-text">公開</span>
                            </span>
                        </label>
                        <label class="admin-segmented-option">
                            <input type="radio" name="hero_images[{{ $image->id }}][is_published]" value="0" class="admin-segmented-input" @checked(!$isPublished)>
                            <span class="admin-segmented-face">
                                <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                    <path d="M2 2.5 13.5 13.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
                                    <path d="M6.7 4.1A6.4 6.4 0 0 1 8 3.5c4 0 6.5 4.5 6.5 4.5a10.3 10.3 0 0 1-2.15 2.55M4.2 5.85A10.2 10.2 0 0 0 1.5 8S4 12.5 8 12.5c.7 0 1.35-.12 1.95-.34" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M6.65 7.1a2 2 0 0 0 2.35 2.35" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
                                </svg>
                                <span class="admin-segmented-text">非公開</span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
            {{-- Future per-image fields (catch_copy / link_url / …) --}}
        </div>
    @endforeach

    <div
        id="hero-image-add-card"
        class="admin-card flex min-h-[22rem] w-full flex-col items-center justify-center px-6 py-10 text-center {{ $heroCount >= $maxHeroImages ? 'hidden' : '' }}"
    >
        <div class="admin-empty-state-icon !mb-4" aria-hidden="true">
            <svg class="h-14 w-14" viewBox="0 0 80 80" fill="none" stroke="#B8B09F" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 52c-4-8-6-16-4-24 3-10 12-16 20-14" stroke-width="1.3" opacity="0.7"/>
                <path d="M18 34c-2 4-2 9 1 13" stroke-width="1.2" opacity="0.55"/>
                <path d="M22 28c4-1 8 1 10 5" stroke-width="1.2" opacity="0.55"/>
                <path d="M66 18c6 8 8 18 4 28-4 10-14 16-22 14" stroke-width="1.3" opacity="0.7"/>
                <path d="M58 28c3 3 4 8 2 12" stroke-width="1.2" opacity="0.55"/>
                <path d="M62 36c-4 2-7 6-8 10" stroke-width="1.2" opacity="0.55"/>
                <rect x="22" y="22" width="36" height="36" rx="3.5" stroke-width="1.4"/>
                <circle cx="32" cy="33" r="3.2" stroke-width="1.3"/>
                <path d="M26 50l9-9a4 4 0 0 1 5.5 0l13.5 13.5" stroke-width="1.3"/>
                <path d="M44 44l3.5-3.5a3.5 3.5 0 0 1 5 0L58 46" stroke-width="1.3"/>
            </svg>
        </div>
        <x-admin.create-button
            type="button"
            id="hero-image-add-card-btn"
            data-hero-add
            :disabled="$heroCount >= $maxHeroImages"
        >
            画像を追加
        </x-admin.create-button>
        <p class="mt-3 text-xs text-admin-muted">カードを追加し、保存で登録できます。</p>
    </div>
</div>

<p id="hero-image-error" class="mt-2 hidden text-sm text-red-600" role="alert"></p>
