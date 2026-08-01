@if(isset($gallery) && $gallery->image_path)
    <img src="{{ asset('storage/'.$gallery->image_path) }}" alt="" class="mb-3 h-40 rounded object-cover">
@endif
<div>
    <label for="image" class="admin-label">画像 {{ isset($gallery) ? '（変更する場合のみ）' : '' }}</label>
    <input type="file" name="image" id="image" {{ isset($gallery) ? '' : 'required' }} accept="image/jpeg,image/png,image/webp" class="admin-input">
    <p class="mt-1 text-xs text-gray-500">JPEG / PNG / WebP、最大5MB</p>
</div>
<div>
    <label for="caption" class="admin-label">キャプション</label>
    <input type="text" name="caption" id="caption" value="{{ old('caption', $gallery->caption ?? '') }}" class="admin-input">
</div>
<div>
    <label for="sort_order" class="admin-label">表示順</label>
    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $gallery->sort_order ?? 0) }}" min="0" class="admin-input">
</div>
<label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_published" value="1" {{ old('is_published', $gallery->is_published ?? true) ? 'checked' : '' }}>
    公開する
</label>
