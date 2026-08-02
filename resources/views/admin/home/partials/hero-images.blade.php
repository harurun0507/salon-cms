{{-- Expects: $heroImages, $heroCount, $maxHeroImages --}}
<div class="admin-card flex flex-col">
    <div class="space-y-4">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <label class="admin-label mb-0">メインビジュアル画像</label>
            <p id="hero-image-count" class="text-xs text-gray-500">登録数: {{ $heroCount }} / {{ $maxHeroImages }}枚</p>
        </div>
        <p class="text-xs text-gray-500">表示順・公開状態・altテキストは「保存する」でまとめて反映されます。altテキストの入力を推奨します（未入力も可）。</p>

        <div id="hero-images-list" class="space-y-4">
            @foreach($heroImages as $index => $image)
                @php
                    $publishedOld = old('hero_images.'.$image->id.'.is_published', $image->is_published ? '1' : '0');
                    $isPublished = in_array((string) $publishedOld, ['1', 'true', 'on'], true);
                @endphp
                <div
                    class="hero-image-block rounded-lg border border-gray-200 bg-white p-4"
                    data-hero-existing
                    data-hero-id="{{ $image->id }}"
                >
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <p class="hero-image-label text-sm font-medium text-gray-800">画像{{ $index + 1 }}</p>
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
                    <div class="mb-4">
                        <img src="{{ asset('storage/'.$image->image_path) }}" alt="{{ old('hero_images.'.$image->id.'.alt_text', $image->alt_text) }}" class="h-40 w-full max-w-xl rounded object-cover">
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="hero_sort_{{ $image->id }}" class="admin-label">表示順</label>
                            <input type="number" name="hero_images[{{ $image->id }}][sort_order]" id="hero_sort_{{ $image->id }}" value="{{ old('hero_images.'.$image->id.'.sort_order', $image->sort_order) }}" min="0" max="9999" required class="admin-input">
                        </div>
                        <div>
                            <span class="admin-label">公開</span>
                            <label class="menu-published-control mt-2" data-published-control>
                                <input type="hidden" name="hero_images[{{ $image->id }}][is_published]" value="0">
                                <input
                                    type="checkbox"
                                    name="hero_images[{{ $image->id }}][is_published]"
                                    value="1"
                                    class="menu-published-checkbox"
                                    data-published-checkbox
                                    @checked($isPublished)
                                    aria-label="公開状態"
                                >
                                <span
                                    class="menu-published-label {{ $isPublished ? 'is-published' : 'is-unpublished' }}"
                                    data-published-label
                                >
                                    <span class="menu-published-dot" data-published-dot aria-hidden="true"></span>
                                    <span data-published-text>{{ $isPublished ? '公開' : '非公開' }}</span>
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label for="hero_alt_{{ $image->id }}" class="admin-label">altテキスト</label>
                        <input type="text" name="hero_images[{{ $image->id }}][alt_text]" id="hero_alt_{{ $image->id }}" value="{{ old('hero_images.'.$image->id.'.alt_text', $image->alt_text) }}" maxlength="255" class="admin-input" placeholder="例: サロン内観のメインビジュアル">
                        <p class="mt-1 text-xs text-gray-500">検索・アクセシビリティ向上のため入力を推奨します。未入力も保存できます。</p>
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
                <p class="mt-3 text-xs text-admin-muted">カードを追加し、「保存する」で登録できます。</p>
            </div>
        </div>
    </div>

    <p id="hero-image-error" class="mt-2 hidden text-sm text-red-600" role="alert"></p>
</div>
