                @php
                    $prefix = 'blogs.'.$blog->id;
                    $publishedOld = old($prefix.'.is_published', $blog->is_published ? '1' : '0');
                    $isPublished = (string) $publishedOld === '1';
                    $isUnpublished = (string) $publishedOld === '0';
                    $publishedAt = old($prefix.'.published_at', $formatLocal($blog->published_at));
                    $displayOrder = old($prefix.'.display_order', $blog->display_order);
                    $cardTitle = trim((string) old($prefix.'.title', $blog->title));
                    $headingTitle = $cardTitle !== '' ? $cardTitle : '新規ブログ';
                    $removeEyeCatch = (string) old($prefix.'.remove_eye_catch', '0') === '1';
                    $pendingImagePath = (string) old($prefix.'.pending_image_path', '');
                    $pendingImageUsable = $pendingImagePath !== ''
                        && \Illuminate\Support\Facades\Storage::disk('public')->exists($pendingImagePath);
                    $previewImagePath = $pendingImageUsable
                        ? $pendingImagePath
                        : ($removeEyeCatch ? null : $blog->eye_catch_image_path);
                    $hasPreviewImage = filled($previewImagePath)
                        && \Illuminate\Support\Facades\Storage::disk('public')->exists($previewImagePath);
                @endphp
                <div
                    class="admin-card blog-card"
                    data-blog-card
                    data-blog-id="{{ $blog->id }}"
                    data-blog-existing
                >
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2">
                            <span
                                class="blog-drag-handle"
                                data-blog-drag-handle
                                draggable="true"
                                role="button"
                                tabindex="0"
                                aria-label="ブログを並び替え"
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
                            <p class="blog-card-label truncate text-sm font-medium text-gray-800" data-blog-card-title title="{{ $headingTitle }}">{{ $headingTitle }}</p>
                        </div>
                        <button
                            type="button"
                            class="admin-icon-btn admin-icon-btn-delete"
                            data-blog-remove
                            aria-label="削除"
                            title="削除"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <input type="hidden" name="blogs[{{ $blog->id }}][display_order]" value="{{ $displayOrder }}" data-blog-order>
                    <input type="hidden" name="blogs[{{ $blog->id }}][remove_eye_catch]" value="{{ $removeEyeCatch ? '1' : '0' }}" data-blog-remove-eye-catch>

                    <div class="mb-3">
                        <span class="admin-label">アイキャッチ画像</span>
                        <div data-blog-dropzone class="banner-dropzone cursor-pointer overflow-hidden rounded-lg {{ $hasPreviewImage ? '' : 'is-empty' }}">
                            <div data-blog-preview @class(['hidden' => ! $hasPreviewImage])>
                                @if($hasPreviewImage)
                                    <img
                                        src="{{ asset('storage/'.$previewImagePath) }}"
                                        alt=""
                                        class="aspect-[16/10] w-full object-cover"
                                        data-blog-image
                                    >
                                @endif
                            </div>
                            <div data-blog-placeholder class="banner-dropzone-placeholder {{ $hasPreviewImage ? 'hidden' : '' }} min-h-[7.5rem] flex-col items-center justify-center px-4 text-center">
                                <div class="banner-dropzone-main">
                                    <svg class="banner-dropzone-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <rect x="3.5" y="5.5" width="17" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                        <circle cx="9" cy="10.5" r="1.5" fill="currentColor" opacity="0.7"/>
                                        <path d="M5.5 16.5l4-3.5 2.5 2 3.5-3.5 3 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <p class="banner-dropzone-text text-sm text-gray-700">画像をドラッグ＆ドロップ、またはクリックして選択</p>
                                </div>
                                <p class="banner-dropzone-hint mt-2 text-xs text-gray-500">任意・JPEG / PNG / WebP、5MBまで</p>
                            </div>
                            <p class="banner-dropzone-drag-message" aria-hidden="true">ここに画像をドロップしてください</p>
                        </div>
                        <input
                            type="hidden"
                            name="blogs[{{ $blog->id }}][pending_image_path]"
                            value="{{ $pendingImageUsable ? $pendingImagePath : '' }}"
                            data-blog-pending-image
                        >
                        <input
                            type="file"
                            name="blogs[{{ $blog->id }}][eye_catch]"
                            accept="image/jpeg,image/png,image/webp"
                            class="hidden"
                            data-blog-file
                        >
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <p class="text-xs text-admin-muted">未登録でも保存できます</p>
                            <button
                                type="button"
                                class="text-xs text-admin-muted underline decoration-admin-border underline-offset-2 hover:text-admin-text {{ $hasPreviewImage ? '' : 'hidden' }}"
                                data-blog-clear-eye-catch
                            >画像を削除</button>
                        </div>
                        @if($pendingImageUsable && $hasPreviewImage)
                            <p class="mt-1 text-xs text-admin-muted">選択中の画像を保持しています。変更する場合は再選択してください。</p>
                        @endif
                        <p class="mt-1 hidden text-sm text-admin-muted" data-blog-image-error role="alert"></p>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label for="blog_title_{{ $blog->id }}" class="admin-label">タイトル <span class="admin-required-badge">必須</span></label>
                            <input
                                type="text"
                                name="blogs[{{ $blog->id }}][title]"
                                id="blog_title_{{ $blog->id }}"
                                value="{{ old($prefix.'.title', $blog->title) }}"
                                maxlength="255"
                                class="admin-input"
                                data-blog-title-input
                            >
                        </div>
                        <div>
                            <label for="blog_body_{{ $blog->id }}" class="admin-label">本文 <span class="admin-required-badge">必須</span></label>
                            <textarea
                                name="blogs[{{ $blog->id }}][body]"
                                id="blog_body_{{ $blog->id }}"
                                rows="6"
                                class="admin-input"
                            >{{ old($prefix.'.body', $blog->body) }}</textarea>
                        </div>
                        <div>
                            <label for="blog_published_at_{{ $blog->id }}" class="admin-label">投稿日時 <span class="admin-required-badge">必須</span></label>
                            <input
                                type="datetime-local"
                                name="blogs[{{ $blog->id }}][published_at]"
                                id="blog_published_at_{{ $blog->id }}"
                                value="{{ $publishedAt }}"
                                class="admin-input"
                                data-blog-published-at
                            >
                            <p class="mt-1 text-xs text-admin-muted">公開判定に使います（投稿日時が未来の場合は表示されません）</p>
                        </div>
                        <div>
                            <span class="admin-label">公開 <span class="admin-required-badge">必須</span></span>
                            <div class="admin-segmented mt-1" role="radiogroup" aria-label="公開状態">
                                <label class="admin-segmented-option">
                                    <input type="radio" name="blogs[{{ $blog->id }}][is_published]" value="1" class="admin-segmented-input" data-blog-is-published @checked($isPublished)>
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>
                                            <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.35"/>
                                        </svg>
                                        <span class="admin-segmented-text">公開</span>
                                    </span>
                                </label>
                                <label class="admin-segmented-option">
                                    <input type="radio" name="blogs[{{ $blog->id }}][is_published]" value="0" class="admin-segmented-input" data-blog-is-published @checked($isUnpublished)>
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
