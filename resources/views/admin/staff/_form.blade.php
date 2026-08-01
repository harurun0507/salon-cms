@if(isset($staff) && $staff->photo_path)
    <img src="{{ asset('storage/'.$staff->photo_path) }}" alt="" class="mb-3 h-32 w-32 rounded-full object-cover">
@endif
<div>
    <label for="name" class="admin-label">名前</label>
    <input type="text" name="name" id="name" value="{{ old('name', $staff->name ?? '') }}" required class="admin-input">
</div>
<div>
    <label for="role" class="admin-label">役職・担当</label>
    <input type="text" name="role" id="role" value="{{ old('role', $staff->role ?? '') }}" class="admin-input">
</div>
<div>
    <label for="photo" class="admin-label">写真 {{ isset($staff) ? '（変更する場合のみ）' : '' }}</label>
    <input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/webp" class="admin-input">
    <p class="mt-1 text-xs text-gray-500">JPEG / PNG / WebP、最大5MB</p>
</div>
<div>
    <label for="profile" class="admin-label">プロフィール</label>
    <textarea name="profile" id="profile" rows="6" class="admin-input">{{ old('profile', $staff->profile ?? '') }}</textarea>
</div>
<div>
    <label for="sort_order" class="admin-label">表示順</label>
    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $staff->sort_order ?? 0) }}" min="0" class="admin-input">
</div>
<label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_published" value="1" {{ old('is_published', $staff->is_published ?? true) ? 'checked' : '' }}>
    公開する
</label>
