@if(isset($gallery) && ($cover = $gallery->coverImagePath()))
    <img src="{{ asset('storage/'.$cover) }}" alt="" class="mb-3 h-40 rounded object-cover">
@endif
<div>
    <label for="image" class="admin-label">画像 {{ isset($gallery) ? '（1枚目を変更する場合のみ）' : '' }}</label>
    <input type="file" name="image" id="image" {{ isset($gallery) ? '' : 'required' }} accept="image/jpeg,image/png,image/webp" class="admin-input">
    <p class="mt-1 text-xs text-gray-500">JPEG / PNG / WebP、最大5MB。複数画像の管理は一覧の一括編集をご利用ください。</p>
</div>
<div>
    <label for="title" class="admin-label">タイトル</label>
    <input
        type="text"
        name="title"
        id="title"
        value="{{ old('title', $gallery->title ?? '') }}"
        maxlength="255"
        class="admin-input"
        placeholder="例：ショートボブ"
    >
</div>
<div>
    <label for="caption" class="admin-label">詳細</label>
    <textarea
        name="caption"
        id="caption"
        rows="4"
        maxlength="2000"
        class="admin-input min-h-[7rem] resize-y"
        placeholder="スタイルの特徴やポイントを入力してください"
    >{{ old('caption', $gallery->caption ?? '') }}</textarea>
</div>
<div>
    @include('admin.galleries.partials.staff-picker', [
        'name' => 'staff_id',
        'inputId' => 'staff_id',
        'selectedId' => old('staff_id', $gallery->staff_id ?? ''),
        'staffMembers' => $staffMembers ?? collect(),
    ])
</div>
<div>
    <label for="sort_order" class="admin-label">表示順</label>
    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $gallery->sort_order ?? 0) }}" min="0" class="admin-input">
</div>
<label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_published" value="1" {{ old('is_published', $gallery->is_published ?? true) ? 'checked' : '' }}>
    公開する
</label>
