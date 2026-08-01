@extends('layouts.admin')

@section('heading', 'お知らせ管理')

@section('content')
    <div class="mb-6 flex justify-between">
        <p class="text-sm text-admin-muted">お知らせの一覧・登録・編集・削除</p>
        <x-admin.create-button type="button" data-open-news-create>新規登録</x-admin.create-button>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>タイトル</th>
                    <th>公開日</th>
                    <th>状態</th>
                    <th class="text-right">操作</th>
                </tr>
            </thead>
            <tbody id="news-table-body">
                @forelse($newsList as $news)
                    <tr
                        data-news-row="{{ $news->id }}"
                        data-published-at="{{ $news->published_at?->format('Y-m-d\TH:i') ?? '' }}"
                    >
                        <td class="news-title">{{ $news->title }}</td>
                        <td class="news-published-at">{{ $news->published_at?->format('Y/m/d') ?? '-' }}</td>
                        <td>
                            <span class="news-status rounded-full px-2 py-1 text-xs {{ $news->is_published ? 'bg-admin-selected text-admin-accent-dark' : 'bg-admin-hover text-admin-muted' }}">
                                {{ $news->is_published ? '公開' : '非公開' }}
                            </span>
                        </td>
                        <td class="text-right">
                            <x-admin.action-group>
                                <x-admin.edit-button
                                    type="button"
                                    data-open-news-edit
                                    data-news-id="{{ $news->id }}"
                                />
                                <x-admin.delete-button
                                    :action="route('admin.news.destroy', $news)"
                                    :name="$news->title"
                                />
                            </x-admin.action-group>
                        </td>
                    </tr>
                @empty
                    <tr id="news-empty-row">
                        <td colspan="4" class="!p-0 hover:!bg-transparent">
                            <x-admin.empty-state
                                variant="leaf"
                                title="お知らせがありません。"
                                description="新しいお知らせを登録してみましょう。"
                            >
                                <x-admin.create-button type="button" data-open-news-create>新規登録</x-admin.create-button>
                            </x-admin.empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $newsList->links() }}</div>

    <template id="news-row-template">
        <tr data-news-row="" data-published-at="">
            <td class="news-title px-4 py-3"></td>
            <td class="news-published-at px-4 py-3"></td>
            <td class="px-4 py-3">
                <span class="news-status rounded-full px-2 py-1 text-xs bg-admin-hover text-admin-muted"></span>
            </td>
            <td class="px-4 py-3 text-right">
                <div class="admin-action-group">
                    <button type="button" class="btn-admin-edit" data-open-news-edit data-news-id="">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M2.695 14.763l-1.262 3.154a.5.5 0 0 0 .65.65l3.155-1.262a4 4 0 0 0 1.343-.885L17.5 5.5a2.121 2.121 0 0 0-3-3L3.58 13.42a4 4 0 0 0-.885 1.343Z"/></svg>
                        <span>編集</span>
                    </button>
                    <form action="" method="POST" class="inline" data-admin-delete-form>
                        <input type="hidden" name="_token" value="">
                        <input type="hidden" name="_method" value="DELETE">
                        <button
                            type="button"
                            data-admin-delete-trigger
                            data-delete-message=""
                            class="btn-admin-delete"
                        >
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.5 3a1.5 1.5 0 0 0-1.415 1H4a1 1 0 0 0 0 2h.293l.72 9.364A2.5 2.5 0 0 0 7.505 18h4.99a2.5 2.5 0 0 0 2.492-2.636L15.707 6H16a1 1 0 1 0 0-2h-3.085A1.5 1.5 0 0 0 11.5 3h-3Zm1 1a.5.5 0 0 0-.5.5V5h2v-.5a.5.5 0 0 0-.5-.5h-1ZM7.3 6l.69 8.97a.5.5 0 0 0 .498.53h3.024a.5.5 0 0 0 .498-.53L12.7 6H7.3Z" clip-rule="evenodd"/></svg>
                            <span>削除</span>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
    </template>

    @php
        $newsEditPayload = $newsList->getCollection()->mapWithKeys(function ($news) {
            return [
                (string) $news->id => [
                    'id' => $news->id,
                    'title' => $news->title,
                    'body' => $news->body,
                    'published_at' => $news->published_at?->format('Y-m-d\TH:i') ?? '',
                    'is_published' => (bool) $news->is_published,
                    'update_url' => route('admin.news.update', $news),
                ],
            ];
        });
    @endphp
    <script type="application/json" id="news-edit-data">{!! json_encode($newsEditPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

    {{-- 作成モーダル（一覧に1つ） --}}
    <div
        id="news-create-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="news-create-modal-title"
        hidden
    >
        <div class="admin-modal-panel flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-xl border border-admin-border/50 bg-admin-card" data-news-create-modal-panel>
                <div class="flex shrink-0 items-center justify-between border-b border-admin-border px-6 py-4">
                <h2 id="news-create-modal-title" class="text-lg font-semibold text-admin-text">お知らせ登録</h2>
                <button
                    type="button"
                    class="rounded-md p-1 text-admin-muted hover:bg-admin-hover hover:text-admin-text focus:outline-none focus:ring-2 focus:ring-admin-accent/40"
                    aria-label="閉じる"
                    data-close-news-create
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                    </svg>
                </button>
            </div>

            <form
                id="news-create-form"
                method="POST"
                action="{{ route('admin.news.store') }}"
                class="flex min-h-0 flex-1 flex-col"
            >
                @csrf

                <div class="space-y-5 overflow-y-auto px-6 py-5">
                    <div>
                        <label for="create-news-title" class="admin-label">タイトル</label>
                        <input type="text" name="title" id="create-news-title" required class="admin-input">
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="title"></p>
                    </div>
                    <div>
                        <label for="create-news-body" class="admin-label">本文</label>
                        <textarea name="body" id="create-news-body" rows="10" required class="admin-input"></textarea>
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="body"></p>
                    </div>
                    <div>
                        <label for="create-news-published_at" class="admin-label">公開日時</label>
                        <input type="datetime-local" name="published_at" id="create-news-published_at" class="admin-input">
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="published_at"></p>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_published" id="create-news-is_published" value="1">
                            公開する
                        </label>
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="is_published"></p>
                    </div>
                </div>

                <div class="flex shrink-0 justify-end gap-3 border-t border-admin-border px-6 py-4">
                    <button type="button" class="admin-btn-secondary" data-close-news-create>キャンセル</button>
                    <button
                        type="submit"
                        id="news-create-submit"
                        class="admin-btn"
                    >登録する</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 編集モーダル（一覧に1つ） --}}
    <div
        id="news-edit-modal"
        class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="news-edit-modal-title"
        hidden
    >
        <div class="admin-modal-panel flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-xl border border-admin-border/50 bg-admin-card" data-news-modal-panel>
            <div class="flex shrink-0 items-center justify-between border-b border-admin-border px-6 py-4">
                <h2 id="news-edit-modal-title" class="text-lg font-semibold text-admin-text">お知らせ編集</h2>
                <button
                    type="button"
                    id="news-edit-modal-close"
                    class="rounded-md p-1 text-admin-muted hover:bg-admin-hover hover:text-admin-text focus:outline-none focus:ring-2 focus:ring-admin-accent/40"
                    aria-label="閉じる"
                    data-close-news-edit
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                    </svg>
                </button>
            </div>

            <form id="news-edit-form" method="POST" class="flex min-h-0 flex-1 flex-col">
                @csrf
                <input type="hidden" name="_method" value="PUT">

                <div class="space-y-5 overflow-y-auto px-6 py-5">
                    <div>
                        <label for="modal-title" class="admin-label">タイトル</label>
                        <input type="text" name="title" id="modal-title" required class="admin-input">
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="title"></p>
                    </div>
                    <div>
                        <label for="modal-body" class="admin-label">本文</label>
                        <textarea name="body" id="modal-body" rows="10" required class="admin-input"></textarea>
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="body"></p>
                    </div>
                    <div>
                        <label for="modal-published_at" class="admin-label">公開日時</label>
                        <input type="datetime-local" name="published_at" id="modal-published_at" class="admin-input">
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="published_at"></p>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_published" id="modal-is_published" value="1">
                            公開する
                        </label>
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="is_published"></p>
                    </div>
                </div>

                <div class="flex shrink-0 justify-end gap-3 border-t border-admin-border px-6 py-4">
                    <button type="button" class="admin-btn-secondary" data-close-news-edit>キャンセル</button>
                    <button
                        type="submit"
                        id="news-edit-submit"
                        class="admin-btn"
                    >更新する</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const dataEl = document.getElementById('news-edit-data');

            let newsData = {};
            try {
                newsData = JSON.parse(dataEl?.textContent || '{}');
            } catch (e) {
                newsData = {};
            }

            // ---- 編集モーダル ----
            const modal = document.getElementById('news-edit-modal');
            const form = document.getElementById('news-edit-form');
            const submitBtn = document.getElementById('news-edit-submit');

            let openTrigger = null;
            let previouslyFocused = null;
            let submitting = false;

            const titleInput = document.getElementById('modal-title');
            const bodyInput = document.getElementById('modal-body');
            const publishedAtInput = document.getElementById('modal-published_at');
            const isPublishedInput = document.getElementById('modal-is_published');

            function getFocusable() {
                return Array.from(
                    modal.querySelectorAll(
                        'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
                    )
                ).filter(function (el) {
                    return !el.hasAttribute('disabled') && el.getClientRects().length > 0;
                });
            }

            function clearErrors() {
                form.querySelectorAll('[data-error-for]').forEach(function (el) {
                    el.textContent = '';
                    el.classList.add('hidden');
                });
            }

            function showErrors(errors) {
                clearErrors();
                Object.keys(errors || {}).forEach(function (field) {
                    const target = form.querySelector('[data-error-for="' + field + '"]');
                    if (target && errors[field] && errors[field][0]) {
                        target.textContent = errors[field][0];
                        target.classList.remove('hidden');
                    }
                });
            }

            function fillForm(item) {
                form.action = item.update_url;
                titleInput.value = item.title || '';
                bodyInput.value = item.body || '';
                publishedAtInput.value = item.published_at || '';
                isPublishedInput.checked = !!item.is_published;
                clearErrors();
            }

            function openModal(newsId, trigger) {
                const item = newsData[String(newsId)];
                if (!item) {
                    return;
                }

                openTrigger = trigger || null;
                previouslyFocused = document.activeElement;
                fillForm(item);
                modal.classList.remove('hidden');
                modal.removeAttribute('hidden');
                document.body.style.overflow = 'hidden';

                requestAnimationFrame(function () {
                    setTimeout(function () {
                        titleInput.focus();
                    }, 0);
                });
            }

            function closeModal() {
                if (submitting) {
                    return;
                }

                modal.classList.add('hidden');
                modal.setAttribute('hidden', '');
                document.body.style.overflow = '';
                clearErrors();

                const restore = openTrigger || previouslyFocused;
                openTrigger = null;
                if (restore && typeof restore.focus === 'function') {
                    restore.focus();
                }
            }

            function updateRow(news) {
                const row = document.querySelector('[data-news-row="' + news.id + '"]');
                if (!row) {
                    return;
                }

                const titleCell = row.querySelector('.news-title');
                const dateCell = row.querySelector('.news-published-at');
                const statusEl = row.querySelector('.news-status');

                if (titleCell) {
                    titleCell.textContent = news.title;
                }
                if (dateCell) {
                    dateCell.textContent = news.published_at_display || '-';
                }
                if (statusEl) {
                    statusEl.textContent = news.is_published ? '公開' : '非公開';
                    statusEl.className = 'news-status rounded-full px-2 py-1 text-xs ' +
                        (news.is_published ? 'bg-admin-selected text-admin-accent-dark' : 'bg-admin-hover text-admin-muted');
                }
            }

            function showSuccess(message) {
                if (typeof window.showToast === 'function') {
                    window.showToast(message || 'お知らせを更新しました。', 'success');
                } else if (window.AdminToast) {
                    window.AdminToast.show(message || 'お知らせを更新しました。', 'success');
                }
            }

            document.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-open-news-edit]');
                if (!btn) {
                    return;
                }
                openModal(btn.getAttribute('data-news-id'), btn);
            });

            document.querySelectorAll('[data-close-news-edit]').forEach(function (btn) {
                btn.addEventListener('click', closeModal);
            });

            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function (e) {
                if (modal.classList.contains('hidden')) {
                    return;
                }

                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeModal();
                    return;
                }

                if (e.key === 'Tab') {
                    const focusable = getFocusable();
                    if (focusable.length === 0) {
                        return;
                    }

                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];

                    if (e.shiftKey && document.activeElement === first) {
                        e.preventDefault();
                        last.focus();
                    } else if (!e.shiftKey && document.activeElement === last) {
                        e.preventDefault();
                        first.focus();
                    }
                }
            });

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (submitting) {
                    return;
                }

                submitting = true;
                submitBtn.disabled = true;
                clearErrors();

                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                    credentials: 'same-origin',
                })
                    .then(async function (response) {
                        const data = await response.json().catch(function () {
                            return {};
                        });

                        if (response.status === 422) {
                            showErrors(data.errors || {});
                            return;
                        }

                        if (!response.ok) {
                            showErrors({ title: [data.message || '更新に失敗しました。'] });
                            return;
                        }

                        const news = data.news;
                        if (news) {
                            newsData[String(news.id)] = {
                                id: news.id,
                                title: news.title,
                                body: news.body,
                                published_at: news.published_at || '',
                                is_published: !!news.is_published,
                                update_url: form.action,
                            };
                            if (dataEl) {
                                dataEl.textContent = JSON.stringify(newsData);
                            }
                            updateRow(news);
                        }

                        submitting = false;
                        submitBtn.disabled = false;
                        closeModal();
                        showSuccess(data.message || 'お知らせを更新しました。');
                    })
                    .catch(function () {
                        showErrors({ title: ['通信エラーが発生しました。'] });
                    })
                    .finally(function () {
                        submitting = false;
                        submitBtn.disabled = false;
                    });
            });

            // ---- 作成モーダル ----
            const createModal = document.getElementById('news-create-modal');
            const createForm = document.getElementById('news-create-form');
            const createSubmitBtn = document.getElementById('news-create-submit');
            const createTitleInput = document.getElementById('create-news-title');
            const createIsPublishedInput = document.getElementById('create-news-is_published');
            const rowTemplate = document.getElementById('news-row-template');
            const newsTableBody = document.getElementById('news-table-body');

            let createOpenTrigger = null;
            let createSubmitting = false;

            function clearCreateErrors() {
                createForm.querySelectorAll('[data-error-for]').forEach(function (el) {
                    el.textContent = '';
                    el.classList.add('hidden');
                });
            }

            function showCreateErrors(errors) {
                clearCreateErrors();
                Object.keys(errors || {}).forEach(function (field) {
                    const target = createForm.querySelector('[data-error-for="' + field + '"]');
                    if (target && errors[field] && errors[field][0]) {
                        target.textContent = errors[field][0];
                        target.classList.remove('hidden');
                    }
                });
            }

            function resetCreateFormState() {
                createForm.reset();
                createIsPublishedInput.checked = false;
                clearCreateErrors();
                createSubmitBtn.disabled = false;
                createSubmitBtn.textContent = '登録する';
                createSubmitting = false;
            }

            function openCreateModal(trigger) {
                createOpenTrigger = trigger || null;
                resetCreateFormState();
                createModal.classList.remove('hidden');
                createModal.classList.add('flex');
                createModal.removeAttribute('hidden');
                if (window.AdminUi) {
                    window.AdminUi.lockBody();
                } else {
                    document.body.style.overflow = 'hidden';
                }

                requestAnimationFrame(function () {
                    setTimeout(function () {
                        createTitleInput.focus();
                    }, 0);
                });
            }

            function closeCreateModal() {
                if (createSubmitting) {
                    return;
                }

                createModal.classList.add('hidden');
                createModal.classList.remove('flex');
                createModal.setAttribute('hidden', '');
                if (window.AdminUi) {
                    window.AdminUi.unlockBody();
                } else {
                    document.body.style.overflow = '';
                }

                resetCreateFormState();

                const restore = createOpenTrigger;
                createOpenTrigger = null;
                if (restore && typeof restore.focus === 'function') {
                    restore.focus();
                }
            }

            // SQLite/MySQL: ORDER BY published_at DESC → NULLs last; then id DESC
            function compareNewsRows(a, b) {
                const pubA = a.getAttribute('data-published-at') || '';
                const pubB = b.getAttribute('data-published-at') || '';
                const aNull = pubA === '';
                const bNull = pubB === '';

                if (aNull !== bNull) {
                    return aNull ? 1 : -1;
                }

                if (!aNull && pubA !== pubB) {
                    return pubA > pubB ? -1 : 1;
                }

                const idA = Number(a.getAttribute('data-news-row') || 0);
                const idB = Number(b.getAttribute('data-news-row') || 0);
                return idB - idA;
            }

            function insertNewsRow(row) {
                const empty = document.getElementById('news-empty-row');
                if (empty) {
                    empty.remove();
                }

                const rows = Array.from(newsTableBody.querySelectorAll('[data-news-row]'));
                let inserted = false;
                for (let i = 0; i < rows.length; i++) {
                    if (compareNewsRows(row, rows[i]) < 0) {
                        newsTableBody.insertBefore(row, rows[i]);
                        inserted = true;
                        break;
                    }
                }
                if (!inserted) {
                    newsTableBody.appendChild(row);
                }
            }

            function buildNewsRow(news) {
                const node = rowTemplate.content.firstElementChild.cloneNode(true);
                const publishedAt = news.published_at || '';

                node.setAttribute('data-news-row', String(news.id));
                node.setAttribute('data-published-at', publishedAt);

                const titleEl = node.querySelector('.news-title');
                if (titleEl) {
                    titleEl.textContent = news.title || '';
                }

                const dateEl = node.querySelector('.news-published-at');
                if (dateEl) {
                    dateEl.textContent = news.published_at_display || '-';
                }

                const statusEl = node.querySelector('.news-status');
                if (statusEl) {
                    statusEl.textContent = news.is_published ? '公開' : '非公開';
                    statusEl.className = 'news-status rounded-full px-2 py-1 text-xs ' +
                        (news.is_published ? 'bg-admin-selected text-admin-accent-dark' : 'bg-admin-hover text-admin-muted');
                }

                const editBtn = node.querySelector('[data-open-news-edit]');
                if (editBtn) {
                    editBtn.setAttribute('data-news-id', String(news.id));
                }

                const deleteForm = node.querySelector('form[data-admin-delete-form]');
                if (deleteForm) {
                    deleteForm.action = news.destroy_url || '';
                    const tokenInput = deleteForm.querySelector('input[name="_token"]');
                    if (tokenInput) {
                        tokenInput.value = csrfToken;
                    }
                    const deleteBtn = deleteForm.querySelector('[data-admin-delete-trigger]');
                    if (deleteBtn) {
                        deleteBtn.setAttribute(
                            'data-delete-message',
                            '「' + (news.title || '') + '」を削除しますか？'
                        );
                    }
                }

                return node;
            }

            document.querySelectorAll('[data-open-news-create]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    openCreateModal(btn);
                });
            });

            document.querySelectorAll('[data-close-news-create]').forEach(function (btn) {
                btn.addEventListener('click', closeCreateModal);
            });

            createModal.addEventListener('click', function (e) {
                if (e.target === createModal) {
                    closeCreateModal();
                }
            });

            document.addEventListener('keydown', function (e) {
                if (createModal.classList.contains('hidden')) {
                    return;
                }

                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeCreateModal();
                    return;
                }

                if (window.AdminUi) {
                    window.AdminUi.trapFocus(e, createModal);
                }
            });

            createForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (createSubmitting) {
                    return;
                }

                createSubmitting = true;
                createSubmitBtn.disabled = true;
                createSubmitBtn.textContent = '登録中...';
                clearCreateErrors();

                const formData = new FormData(createForm);

                fetch(createForm.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                    credentials: 'same-origin',
                })
                    .then(async function (response) {
                        const data = await response.json().catch(function () {
                            return {};
                        });

                        if (response.status === 422) {
                            showCreateErrors(data.errors || {});
                            if (typeof window.showToast === 'function') {
                                window.showToast('入力内容を確認してください。', 'error');
                            }
                            return;
                        }

                        if (!response.ok) {
                            showCreateErrors({ title: [data.message || '登録に失敗しました。'] });
                            if (typeof window.showToast === 'function') {
                                window.showToast(data.message || '登録に失敗しました。', 'error');
                            }
                            return;
                        }

                        const news = data.news;
                        if (news) {
                            newsData[String(news.id)] = {
                                id: news.id,
                                title: news.title,
                                body: news.body,
                                published_at: news.published_at || '',
                                is_published: !!news.is_published,
                                update_url: news.update_url,
                            };
                            if (dataEl) {
                                dataEl.textContent = JSON.stringify(newsData);
                            }

                            const row = buildNewsRow(news);
                            insertNewsRow(row);
                        }

                        createSubmitting = false;
                        createSubmitBtn.disabled = false;
                        createSubmitBtn.textContent = '登録する';
                        closeCreateModal();

                        if (typeof window.showToast === 'function') {
                            window.showToast(data.message || 'お知らせを登録しました。', 'success');
                        }
                    })
                    .catch(function () {
                        showCreateErrors({ title: ['通信エラーが発生しました。'] });
                        if (typeof window.showToast === 'function') {
                            window.showToast('通信エラーが発生しました。', 'error');
                        }
                    })
                    .finally(function () {
                        createSubmitting = false;
                        createSubmitBtn.disabled = false;
                        createSubmitBtn.textContent = '登録する';
                    });
            });
        })();
    </script>
@endsection
