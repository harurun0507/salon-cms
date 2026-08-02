<div>
    <label for="name" class="admin-label">メニュー名</label>
    <textarea name="name" id="name" rows="4" required class="admin-input">{{ old('name', $menu->name ?? '') }}</textarea>
</div>
<div>
    <label for="price" class="admin-label">料金表示</label>
    <input
        type="text"
        name="price"
        id="price"
        value="{{ old('price', $menu->price ?? '') }}"
        maxlength="100"
        placeholder="例: ¥5,500 / ¥8,800〜"
        class="admin-input"
    >
    <p class="mt-1 text-xs text-admin-muted">公開サイトへそのまま表示されます。</p>
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
