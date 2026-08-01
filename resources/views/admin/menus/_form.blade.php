<div>
    <label for="name" class="admin-label">メニュー名</label>
    <input type="text" name="name" id="name" value="{{ old('name', $menu->name ?? '') }}" required class="admin-input">
</div>
<div>
    <label for="price" class="admin-label">料金（円）</label>
    <input type="number" name="price" id="price" value="{{ old('price', $menu->price ?? '') }}" min="0" required class="admin-input">
</div>
<div>
    <label for="description" class="admin-label">説明</label>
    <textarea name="description" id="description" rows="4" class="admin-input">{{ old('description', $menu->description ?? '') }}</textarea>
</div>
<div>
    <label for="sort_order" class="admin-label">表示順</label>
    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $menu->sort_order ?? 0) }}" min="0" class="admin-input">
</div>
<label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_published" value="1" {{ old('is_published', $menu->is_published ?? true) ? 'checked' : '' }}>
    公開する
</label>
