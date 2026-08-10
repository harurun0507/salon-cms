@php
    $isExisting = ($kind ?? '') === 'existing';
    $nameBase = $nameBase ?? '';
    $displayOrder = (int) ($displayOrder ?? 1);
    $altText = (string) ($altText ?? '');
    $pendingPath = (string) ($pendingPath ?? '');
    $previewPath = (string) ($previewPath ?? '');
    $labelNumber = (int) ($labelNumber ?? 1);
    $hasPreview = filled($previewPath);
@endphp
<div
    class="gallery-image-item"
    data-gallery-image-item
    @if($isExisting)
        data-gallery-image-id="{{ $existingId }}"
        data-gallery-image-existing
    @else
        data-gallery-image-new="1"
        data-gallery-image-key="{{ $imageKey }}"
    @endif
>
    <span
        class="gallery-image-drag-handle"
        data-gallery-image-drag-handle
        draggable="true"
        role="button"
        tabindex="0"
        aria-label="画像を並び替え"
        title="ドラッグして並び替え"
    >
        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <circle cx="7" cy="5" r="1.25"/><circle cx="13" cy="5" r="1.25"/>
            <circle cx="7" cy="10" r="1.25"/><circle cx="13" cy="10" r="1.25"/>
            <circle cx="7" cy="15" r="1.25"/><circle cx="13" cy="15" r="1.25"/>
        </svg>
    </span>
    <button
        type="button"
        class="gallery-image-remove"
        data-gallery-image-remove
        aria-label="画像を削除"
        title="画像を削除"
    >&times;</button>
    <div class="gallery-image-thumb" data-gallery-image-dropzone>
        @if($hasPreview)
            <img src="{{ asset('storage/'.$previewPath) }}" alt="" class="h-full w-full object-cover" data-gallery-image-preview>
        @endif
    </div>
    <input type="hidden" name="{{ $nameBase }}[display_order]" value="{{ $displayOrder }}" data-gallery-image-order>
    <input type="hidden" name="{{ $nameBase }}[alt_text]" value="{{ $altText }}" data-gallery-image-alt>
    <input type="hidden" name="{{ $nameBase }}[pending_image_path]" value="{{ $pendingPath }}" data-gallery-image-pending>
    <input
        type="file"
        name="{{ $nameBase }}[image]"
        accept="image/jpeg,image/png,image/webp"
        class="hidden"
        data-gallery-image-file
        @if(! $isExisting && $pendingPath === '') required @endif
    >
    <p class="mt-1 text-center text-[10px] text-admin-muted" data-gallery-image-label>画像{{ $labelNumber }}</p>
</div>
