@php
    $prefix = 'new_galleries.'.$key;
    $publishedOld = old($prefix.'.is_published', $newItem['is_published'] ?? '1');
    $isPublished = in_array((string) $publishedOld, ['1', 'true', 'on'], true);
    $sortOrder = old($prefix.'.sort_order', $newItem['sort_order'] ?? 0);
    $cardTitle = trim((string) old($prefix.'.title', $newItem['title'] ?? ''));
    $headingTitle = $cardTitle !== '' ? $cardTitle : '新規ギャラリー';
    $staffId = old($prefix.'.staff_id', $newItem['staff_id'] ?? '');
    $caption = old($prefix.'.caption', $newItem['caption'] ?? '');

    $imageSlots = [];
    $slotIndex = 0;
    $oldNewImages = old($prefix.'.new_images', $newItem['new_images'] ?? []);
    if (! is_array($oldNewImages)) {
        $oldNewImages = [];
    }
    foreach ($oldNewImages as $imageKey => $newImage) {
        if (! is_array($newImage)) {
            continue;
        }

        $imgPrefix = $prefix.'.new_images.'.$imageKey;
        $pendingPath = (string) old($imgPrefix.'.pending_image_path', $newImage['pending_image_path'] ?? '');
        if (! $isUsablePending($pendingPath)) {
            continue;
        }

        $imageSlots[] = [
            'kind' => 'new',
            'existingId' => null,
            'imageKey' => (string) $imageKey,
            'nameBase' => 'new_galleries['.$key.'][new_images]['.$imageKey.']',
            'displayOrder' => (int) old($imgPrefix.'.display_order', $newImage['display_order'] ?? PHP_INT_MAX),
            'altText' => (string) old($imgPrefix.'.alt_text', $newImage['alt_text'] ?? ''),
            'pendingPath' => $pendingPath,
            'previewPath' => $pendingPath,
            'index' => $slotIndex++,
        ];
    }

    usort($imageSlots, function (array $a, array $b): int {
        if ($a['displayOrder'] !== $b['displayOrder']) {
            return $a['displayOrder'] <=> $b['displayOrder'];
        }

        return $a['index'] <=> $b['index'];
    });
@endphp
<div
    class="admin-card gallery-card"
    data-gallery-card
    data-gallery-new="1"
    data-gallery-name-prefix="new_galleries[{{ $key }}]"
>
    <div class="mb-3 flex items-center justify-between gap-3">
        <div class="flex min-w-0 items-center gap-2">
            <span
                class="gallery-drag-handle"
                data-gallery-drag-handle
                draggable="true"
                role="button"
                tabindex="0"
                aria-label="ギャラリーを並び替え"
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
            <p class="banner-card-label truncate text-sm font-medium text-gray-800" data-gallery-card-title title="{{ $headingTitle }}">{{ $headingTitle }}</p>
        </div>
        <button
            type="button"
            class="admin-icon-btn admin-icon-btn-delete"
            data-gallery-remove
            aria-label="削除"
            title="削除"
        >
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <input type="hidden" name="new_galleries[{{ $key }}][sort_order]" value="{{ $sortOrder }}" data-gallery-order>
    <div data-gallery-deleted-images></div>

    <div class="mb-3">
        <p class="admin-label">ギャラリー画像</p>
        <div class="gallery-image-grid" data-gallery-image-grid>
            @foreach($imageSlots as $slot)
                @include('admin.galleries.partials.image-item', [
                    'kind' => $slot['kind'],
                    'existingId' => $slot['existingId'],
                    'imageKey' => $slot['imageKey'],
                    'nameBase' => $slot['nameBase'],
                    'displayOrder' => $slot['displayOrder'],
                    'altText' => $slot['altText'],
                    'pendingPath' => $slot['pendingPath'],
                    'previewPath' => $slot['previewPath'],
                    'labelNumber' => $loop->iteration,
                ])
            @endforeach

            <button
                type="button"
                class="gallery-image-add"
                data-gallery-image-add
                @if(count($imageSlots) >= $maxImages) hidden @endif
            >
                <svg class="mx-auto h-8 w-8 text-[#B8B09F]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="3.5" y="5.5" width="17" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>
                    <circle cx="9" cy="10.5" r="1.5" fill="currentColor" opacity="0.7"/>
                    <path d="M5.5 16.5l4-3.5 2.5 2 3.5-3.5 3 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="mt-1 block text-xs font-medium text-admin-accent">＋画像を追加</span>
            </button>
        </div>
        <p class="mt-1 hidden text-sm text-red-600" data-gallery-image-error role="alert"></p>
    </div>

    <div class="space-y-3">
        <div>
            <label for="gallery_new_title_{{ $key }}" class="admin-label">タイトル</label>
            <input
                type="text"
                name="new_galleries[{{ $key }}][title]"
                id="gallery_new_title_{{ $key }}"
                value="{{ $cardTitle }}"
                maxlength="255"
                class="admin-input"
                placeholder="例：ショートボブ"
                data-gallery-title-input
            >
        </div>
        <div>
            <label for="gallery_new_caption_{{ $key }}" class="admin-label">詳細</label>
            <textarea
                name="new_galleries[{{ $key }}][caption]"
                id="gallery_new_caption_{{ $key }}"
                rows="4"
                maxlength="2000"
                class="admin-input min-h-[7rem] resize-y"
                placeholder="スタイルの特徴やポイントを入力してください"
                data-gallery-caption-input
            >{{ $caption }}</textarea>
        </div>
        <div>
            @include('admin.galleries.partials.staff-picker', [
                'name' => 'new_galleries['.$key.'][staff_id]',
                'inputId' => 'gallery_new_staff_'.$key,
                'selectedId' => $staffId,
                'staffMembers' => $staffMembers,
            ])
        </div>
        <div>
            <span class="admin-label">公開</span>
            <div class="admin-segmented mt-1" role="radiogroup" aria-label="公開状態">
                <label class="admin-segmented-option">
                    <input type="radio" name="new_galleries[{{ $key }}][is_published]" value="1" class="admin-segmented-input" @checked($isPublished)>
                    <span class="admin-segmented-face">
                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                            <path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>
                            <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.35"/>
                        </svg>
                        <span class="admin-segmented-text">公開</span>
                    </span>
                </label>
                <label class="admin-segmented-option">
                    <input type="radio" name="new_galleries[{{ $key }}][is_published]" value="0" class="admin-segmented-input" @checked(!$isPublished)>
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
</div>
