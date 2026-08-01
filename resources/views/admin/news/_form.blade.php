<div>
    <label for="title" class="admin-label">タイトル</label>
    <input type="text" name="title" id="title" value="{{ old('title', $news->title ?? '') }}" required class="admin-input">
</div>
<div>
    <label for="body" class="admin-label">本文</label>
    <textarea name="body" id="body" rows="10" required class="admin-input">{{ old('body', $news->body ?? '') }}</textarea>
</div>
<div>
    <label for="published_at" class="admin-label">公開日時</label>
    <input type="datetime-local" name="published_at" id="published_at" value="{{ old('published_at', isset($news) && $news->published_at ? $news->published_at->format('Y-m-d\TH:i') : '') }}" class="admin-input">
</div>
<label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_published" value="1" {{ old('is_published', $news->is_published ?? false) ? 'checked' : '' }}>
    公開する
</label>
