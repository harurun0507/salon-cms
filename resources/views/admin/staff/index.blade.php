@extends('layouts.admin')

@section('heading', 'スタッフ管理')

@section('content')
    <div class="mb-6 flex justify-between">
        <p class="text-sm text-admin-muted">スタッフ情報の管理</p>
        <x-admin.create-button type="button" data-open-staff-create>スタッフ追加</x-admin.create-button>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>写真</th>
                    <th>名前</th>
                    <th>役職</th>
                    <th>表示順</th>
                    <th>状態</th>
                    <th class="text-right">操作</th>
                </tr>
            </thead>
            <tbody id="staff-table-body">
                @forelse($staffMembers as $member)
                    <tr data-staff-row="{{ $member->id }}" data-sort-order="{{ $member->sort_order }}">
                        <td>
                            <div class="staff-photo-cell h-12 w-12">
                                @if($member->photo_path)
                                    <img src="{{ asset('storage/'.$member->photo_path) }}" alt="" class="staff-photo h-12 w-12 rounded-full object-cover">
                                @endif
                            </div>
                        </td>
                        <td class="staff-name">{{ $member->name }}</td>
                        <td class="staff-role">{{ $member->role }}</td>
                        <td class="staff-sort-order">{{ $member->sort_order }}</td>
                        <td class="staff-status">{{ $member->is_published ? '公開' : '非公開' }}</td>
                        <td class="text-right">
                            <x-admin.action-group>
                                <x-admin.edit-button
                                    type="button"
                                    data-open-staff-edit
                                    data-staff-id="{{ $member->id }}"
                                />
                                <x-admin.delete-button
                                    :action="route('admin.staff.destroy', $member)"
                                    :name="$member->name"
                                />
                            </x-admin.action-group>
                        </td>
                    </tr>
                @empty
                    <tr id="staff-empty-row">
                        <td colspan="6" class="!p-0 hover:!bg-transparent">
                            <x-admin.empty-state
                                variant="users"
                                title="スタッフが登録されていません。"
                                description="スタッフ情報を追加してみましょう。"
                            >
                                <x-admin.create-button type="button" data-open-staff-create>スタッフ追加</x-admin.create-button>
                            </x-admin.empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <template id="staff-row-template">
        <tr data-staff-row="" data-sort-order="0">
            <td>
                <div class="staff-photo-cell h-12 w-12"></div>
            </td>
            <td class="staff-name"></td>
            <td class="staff-role"></td>
            <td class="staff-sort-order"></td>
            <td class="staff-status"></td>
            <td class="text-right">
                <div class="admin-action-group">
                    <button type="button" class="btn-admin-edit" data-open-staff-edit data-staff-id="">
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
        $staffEditPayload = $staffMembers->mapWithKeys(function ($member) {
            return [
                (string) $member->id => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'role' => $member->role,
                    'profile' => $member->profile,
                    'sort_order' => (int) $member->sort_order,
                    'is_published' => (bool) $member->is_published,
                    'photo_url' => $member->photo_path ? asset('storage/'.$member->photo_path) : null,
                    'update_url' => route('admin.staff.update', $member),
                ],
            ];
        });
    @endphp
    <script type="application/json" id="staff-edit-data">{!! json_encode($staffEditPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

    {{-- スタッフ作成モーダル（一覧に1つ） --}}
    <div
        id="staff-create-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="staff-create-modal-title"
        hidden
    >
        <div class="admin-modal-panel flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-xl border border-admin-border/50 bg-admin-card" data-staff-create-modal-panel>
            <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-6 py-4">
                <h2 id="staff-create-modal-title" class="text-lg font-semibold text-gray-900">スタッフ追加</h2>
                <button
                    type="button"
                    class="rounded-md p-1 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-salon-button/40"
                    aria-label="閉じる"
                    data-close-staff-create
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                    </svg>
                </button>
            </div>

            <form
                id="staff-create-form"
                method="POST"
                action="{{ route('admin.staff.store') }}"
                enctype="multipart/form-data"
                class="flex min-h-0 flex-1 flex-col"
            >
                @csrf

                <div class="space-y-5 overflow-y-auto px-6 py-5">
                    <div>
                        <p class="admin-label">写真プレビュー</p>
                        <div id="create-staff-photo-wrap" class="mt-1">
                            <img id="create-staff-photo-preview" src="" alt="" class="hidden h-32 w-32 rounded-full object-cover">
                            <p id="create-staff-photo-empty" class="text-sm text-gray-500">写真は選択されていません</p>
                        </div>
                    </div>

                    <div>
                        <label for="create-staff-name" class="admin-label">名前</label>
                        <input type="text" name="name" id="create-staff-name" required class="admin-input">
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="name"></p>
                    </div>

                    <div>
                        <label for="create-staff-role" class="admin-label">役職・担当</label>
                        <input type="text" name="role" id="create-staff-role" class="admin-input">
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="role"></p>
                    </div>

                    <div>
                        <label for="create-staff-photo" class="admin-label">写真</label>
                        <input type="file" name="photo" id="create-staff-photo" accept="image/jpeg,image/png,image/webp" class="admin-input">
                        <p class="mt-1 text-xs text-gray-500">JPEG / PNG / WebP、最大5MB。選択すると上のプレビューが切り替わります。</p>
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="photo"></p>
                        <p id="create-staff-photo-reselect-hint" class="mt-1 hidden text-xs text-amber-700">
                            画像のバリデーションエラー後は、ブラウザの仕様上ファイルを再選択する必要があります。
                        </p>
                    </div>

                    <div>
                        <label for="create-staff-profile" class="admin-label">プロフィール</label>
                        <textarea name="profile" id="create-staff-profile" rows="6" class="admin-input"></textarea>
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="profile"></p>
                    </div>

                    <div>
                        <label for="create-staff-sort_order" class="admin-label">表示順</label>
                        <input type="number" name="sort_order" id="create-staff-sort_order" min="0" value="0" class="admin-input">
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="sort_order"></p>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_published" id="create-staff-is_published" value="1" checked>
                            公開する
                        </label>
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="is_published"></p>
                    </div>
                </div>

                <div class="flex shrink-0 justify-end gap-3 border-t border-gray-200 px-6 py-4">
                    <button type="button" class="admin-btn-secondary" data-close-staff-create>キャンセル</button>
                    <button
                        type="submit"
                        id="staff-create-submit"
                        class="inline-flex items-center rounded-md bg-salon-button px-4 py-2 text-sm font-medium text-white hover:bg-[#4f5d44] disabled:cursor-not-allowed disabled:opacity-60"
                    >登録する</button>
                </div>
            </form>
        </div>
    </div>

    {{-- スタッフ編集モーダル（一覧に1つ） --}}
    <div
        id="staff-edit-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="staff-edit-modal-title"
        hidden
    >
        <div class="admin-modal-panel flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-xl border border-admin-border/50 bg-admin-card" data-staff-modal-panel>
            <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-6 py-4">
                <h2 id="staff-edit-modal-title" class="text-lg font-semibold text-gray-900">スタッフ編集</h2>
                <button
                    type="button"
                    class="rounded-md p-1 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-salon-button/40"
                    aria-label="閉じる"
                    data-close-staff-edit
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                    </svg>
                </button>
            </div>

            <form id="staff-edit-form" method="POST" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col">
                @csrf
                <input type="hidden" name="_method" value="PUT">

                <div class="space-y-5 overflow-y-auto px-6 py-5">
                    <div>
                        <p class="admin-label">登録済み写真</p>
                        <div id="modal-staff-photo-wrap" class="mt-1">
                            <img id="modal-staff-photo-preview" src="" alt="" class="hidden h-32 w-32 rounded-full object-cover">
                            <p id="modal-staff-photo-empty" class="text-sm text-gray-500">写真は登録されていません</p>
                        </div>
                    </div>

                    <div>
                        <label for="modal-staff-name" class="admin-label">名前</label>
                        <input type="text" name="name" id="modal-staff-name" required class="admin-input">
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="name"></p>
                    </div>

                    <div>
                        <label for="modal-staff-role" class="admin-label">役職・担当</label>
                        <input type="text" name="role" id="modal-staff-role" class="admin-input">
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="role"></p>
                    </div>

                    <div>
                        <label for="modal-staff-photo" class="admin-label">写真（変更する場合のみ）</label>
                        <input type="file" name="photo" id="modal-staff-photo" accept="image/jpeg,image/png,image/webp" class="admin-input">
                        <p class="mt-1 text-xs text-gray-500">JPEG / PNG / WebP、最大5MB。選択すると上のプレビューが切り替わります。</p>
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="photo"></p>
                        <p id="modal-staff-photo-reselect-hint" class="mt-1 hidden text-xs text-amber-700">
                            画像のバリデーションエラー後は、ブラウザの仕様上ファイルを再選択する必要があります。
                        </p>
                    </div>

                    <div>
                        <label for="modal-staff-profile" class="admin-label">プロフィール</label>
                        <textarea name="profile" id="modal-staff-profile" rows="6" class="admin-input"></textarea>
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="profile"></p>
                    </div>

                    <div>
                        <label for="modal-staff-sort_order" class="admin-label">表示順</label>
                        <input type="number" name="sort_order" id="modal-staff-sort_order" min="0" class="admin-input">
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="sort_order"></p>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_published" id="modal-staff-is_published" value="1">
                            公開する
                        </label>
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="is_published"></p>
                    </div>
                </div>

                <div class="flex shrink-0 justify-end gap-3 border-t border-gray-200 px-6 py-4">
                    <button type="button" class="admin-btn-secondary" data-close-staff-edit>キャンセル</button>
                    <button
                        type="submit"
                        id="staff-edit-submit"
                        class="inline-flex items-center rounded-md bg-salon-button px-4 py-2 text-sm font-medium text-white hover:bg-[#4f5d44] disabled:cursor-not-allowed disabled:opacity-60"
                    >更新する</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('staff-edit-modal');
            const form = document.getElementById('staff-edit-form');
            const submitBtn = document.getElementById('staff-edit-submit');
            const dataEl = document.getElementById('staff-edit-data');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            let staffData = {};
            try {
                staffData = JSON.parse(dataEl?.textContent || '{}');
            } catch (e) {
                staffData = {};
            }

            let openTrigger = null;
            let submitting = false;
            let savedPhotoUrl = null;

            const nameInput = document.getElementById('modal-staff-name');
            const roleInput = document.getElementById('modal-staff-role');
            const photoInput = document.getElementById('modal-staff-photo');
            const profileInput = document.getElementById('modal-staff-profile');
            const sortOrderInput = document.getElementById('modal-staff-sort_order');
            const isPublishedInput = document.getElementById('modal-staff-is_published');
            const photoPreview = document.getElementById('modal-staff-photo-preview');
            const photoEmpty = document.getElementById('modal-staff-photo-empty');
            const photoReselectHint = document.getElementById('modal-staff-photo-reselect-hint');

            function setPhotoPreview(url) {
                if (url) {
                    photoPreview.src = url;
                    photoPreview.classList.remove('hidden');
                    photoEmpty.classList.add('hidden');
                } else {
                    photoPreview.removeAttribute('src');
                    photoPreview.classList.add('hidden');
                    photoEmpty.classList.remove('hidden');
                }
            }

            function clearErrors() {
                form.querySelectorAll('[data-error-for]').forEach(function (el) {
                    el.textContent = '';
                    el.classList.add('hidden');
                });
                photoReselectHint.classList.add('hidden');
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

                if (errors && errors.photo) {
                    photoInput.value = '';
                    setPhotoPreview(savedPhotoUrl);
                    photoReselectHint.classList.remove('hidden');
                }
            }

            function resetFormState() {
                form.reset();
                photoInput.value = '';
                clearErrors();
                savedPhotoUrl = null;
                setPhotoPreview(null);
                submitBtn.disabled = false;
                submitBtn.textContent = '更新する';
                submitting = false;
            }

            function fillForm(item) {
                resetFormState();
                form.action = item.update_url;
                nameInput.value = item.name || '';
                roleInput.value = item.role || '';
                profileInput.value = item.profile || '';
                sortOrderInput.value = item.sort_order != null ? item.sort_order : 0;
                isPublishedInput.checked = !!item.is_published;
                savedPhotoUrl = item.photo_url || null;
                setPhotoPreview(savedPhotoUrl);
            }

            function openModal(staffId, trigger) {
                const item = staffData[String(staffId)];
                if (!item) {
                    return;
                }

                openTrigger = trigger || null;
                fillForm(item);
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.removeAttribute('hidden');
                if (window.AdminUi) {
                    window.AdminUi.lockBody();
                } else {
                    document.body.style.overflow = 'hidden';
                }

                requestAnimationFrame(function () {
                    setTimeout(function () {
                        nameInput.focus();
                    }, 0);
                });
            }

            function closeModal() {
                if (submitting) {
                    return;
                }

                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.setAttribute('hidden', '');
                if (window.AdminUi) {
                    window.AdminUi.unlockBody();
                } else {
                    document.body.style.overflow = '';
                }

                resetFormState();

                const restore = openTrigger;
                openTrigger = null;
                if (restore && typeof restore.focus === 'function') {
                    restore.focus();
                }
            }

            function updateRow(staff) {
                const row = document.querySelector('[data-staff-row="' + staff.id + '"]');
                if (!row) {
                    return;
                }

                const photoCell = row.querySelector('.staff-photo-cell');
                if (photoCell) {
                    if (staff.photo_url) {
                        let img = photoCell.querySelector('.staff-photo');
                        if (!img) {
                            img = document.createElement('img');
                            img.className = 'staff-photo h-12 w-12 rounded-full object-cover';
                            img.alt = '';
                            photoCell.innerHTML = '';
                            photoCell.appendChild(img);
                        }
                        img.src = staff.photo_url;
                    } else {
                        photoCell.innerHTML = '';
                    }
                }

                const nameCell = row.querySelector('.staff-name');
                const roleCell = row.querySelector('.staff-role');
                const sortCell = row.querySelector('.staff-sort-order');
                const statusCell = row.querySelector('.staff-status');

                if (nameCell) {
                    nameCell.textContent = staff.name || '';
                }
                if (roleCell) {
                    roleCell.textContent = staff.role || '';
                }
                if (sortCell) {
                    sortCell.textContent = staff.sort_order != null ? String(staff.sort_order) : '0';
                }
                if (statusCell) {
                    statusCell.textContent = staff.is_published ? '公開' : '非公開';
                }
            }

            document.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-open-staff-edit]');
                if (!btn) {
                    return;
                }
                openModal(btn.getAttribute('data-staff-id'), btn);
            });

            document.querySelectorAll('[data-close-staff-edit]').forEach(function (btn) {
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

                if (window.AdminUi) {
                    window.AdminUi.trapFocus(e, modal);
                }
            });

            photoInput.addEventListener('change', function () {
                const file = photoInput.files && photoInput.files[0];
                photoReselectHint.classList.add('hidden');

                if (!file) {
                    setPhotoPreview(savedPhotoUrl);
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (event) {
                    setPhotoPreview(event.target.result);
                };
                reader.readAsDataURL(file);
            });

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (submitting) {
                    return;
                }

                submitting = true;
                submitBtn.disabled = true;
                submitBtn.textContent = '更新中...';
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
                            if (typeof window.showToast === 'function') {
                                window.showToast('入力内容を確認してください。', 'error');
                            }
                            return;
                        }

                        if (!response.ok) {
                            showErrors({ name: [data.message || '更新に失敗しました。'] });
                            if (typeof window.showToast === 'function') {
                                window.showToast(data.message || '更新に失敗しました。', 'error');
                            }
                            return;
                        }

                        const staff = data.staff;
                        if (staff) {
                            staffData[String(staff.id)] = {
                                id: staff.id,
                                name: staff.name,
                                role: staff.role,
                                profile: staff.profile,
                                sort_order: staff.sort_order,
                                is_published: !!staff.is_published,
                                photo_url: staff.photo_url || null,
                                update_url: form.action,
                            };
                            if (dataEl) {
                                dataEl.textContent = JSON.stringify(staffData);
                            }
                            updateRow(staff);
                        }

                        submitting = false;
                        submitBtn.disabled = false;
                        submitBtn.textContent = '更新する';
                        closeModal();

                        if (typeof window.showToast === 'function') {
                            window.showToast(data.message || 'スタッフ情報を更新しました。', 'success');
                        }
                    })
                    .catch(function () {
                        showErrors({ name: ['通信エラーが発生しました。'] });
                        if (typeof window.showToast === 'function') {
                            window.showToast('通信エラーが発生しました。', 'error');
                        }
                    })
                    .finally(function () {
                        submitting = false;
                        submitBtn.disabled = false;
                        submitBtn.textContent = '更新する';
                    });
            });

            // ---- 作成モーダル ----
            const createModal = document.getElementById('staff-create-modal');
            const createForm = document.getElementById('staff-create-form');
            const createSubmitBtn = document.getElementById('staff-create-submit');
            const createNameInput = document.getElementById('create-staff-name');
            const createPhotoInput = document.getElementById('create-staff-photo');
            const createSortOrderInput = document.getElementById('create-staff-sort_order');
            const createIsPublishedInput = document.getElementById('create-staff-is_published');
            const createPhotoPreview = document.getElementById('create-staff-photo-preview');
            const createPhotoEmpty = document.getElementById('create-staff-photo-empty');
            const createPhotoReselectHint = document.getElementById('create-staff-photo-reselect-hint');
            const rowTemplate = document.getElementById('staff-row-template');
            const staffTableBody = document.getElementById('staff-table-body');
            const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
            const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

            let createOpenTrigger = null;
            let createSubmitting = false;

            function setCreatePhotoPreview(url) {
                if (url) {
                    createPhotoPreview.src = url;
                    createPhotoPreview.classList.remove('hidden');
                    createPhotoEmpty.classList.add('hidden');
                } else {
                    createPhotoPreview.removeAttribute('src');
                    createPhotoPreview.classList.add('hidden');
                    createPhotoEmpty.classList.remove('hidden');
                }
            }

            function clearCreateErrors() {
                createForm.querySelectorAll('[data-error-for]').forEach(function (el) {
                    el.textContent = '';
                    el.classList.add('hidden');
                });
                createPhotoReselectHint.classList.add('hidden');
            }

            function showCreateFieldError(field, message) {
                const target = createForm.querySelector('[data-error-for="' + field + '"]');
                if (target) {
                    target.textContent = message;
                    target.classList.remove('hidden');
                }
            }

            function showCreateErrors(errors) {
                clearCreateErrors();
                Object.keys(errors || {}).forEach(function (field) {
                    if (errors[field] && errors[field][0]) {
                        showCreateFieldError(field, errors[field][0]);
                    }
                });

                if (errors && errors.photo) {
                    createPhotoInput.value = '';
                    setCreatePhotoPreview(null);
                    createPhotoReselectHint.classList.remove('hidden');
                }
            }

            function resetCreateFormState() {
                createForm.reset();
                createPhotoInput.value = '';
                createSortOrderInput.value = '0';
                createIsPublishedInput.checked = true;
                clearCreateErrors();
                setCreatePhotoPreview(null);
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
                        createNameInput.focus();
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

            function validateCreatePhotoFile(file) {
                if (!file) {
                    return null;
                }
                if (ALLOWED_TYPES.indexOf(file.type) === -1) {
                    return 'JPEG / PNG / WebP形式の画像を選択してください。';
                }
                if (file.size > MAX_IMAGE_BYTES) {
                    return '画像サイズは5MB以下にしてください。';
                }
                return null;
            }

            function compareStaffRows(a, b) {
                const sortA = Number(a.getAttribute('data-sort-order') || 0);
                const sortB = Number(b.getAttribute('data-sort-order') || 0);
                if (sortA !== sortB) {
                    return sortA - sortB;
                }
                const idA = Number(a.getAttribute('data-staff-row') || 0);
                const idB = Number(b.getAttribute('data-staff-row') || 0);
                return idA - idB;
            }

            function insertStaffRow(row) {
                const empty = document.getElementById('staff-empty-row');
                if (empty) {
                    empty.remove();
                }

                const rows = Array.from(staffTableBody.querySelectorAll('[data-staff-row]'));
                let inserted = false;
                for (let i = 0; i < rows.length; i++) {
                    if (compareStaffRows(row, rows[i]) < 0) {
                        staffTableBody.insertBefore(row, rows[i]);
                        inserted = true;
                        break;
                    }
                }
                if (!inserted) {
                    staffTableBody.appendChild(row);
                }
            }

            function buildStaffRow(staff) {
                const node = rowTemplate.content.firstElementChild.cloneNode(true);
                const sortOrder = staff.sort_order != null ? staff.sort_order : 0;

                node.setAttribute('data-staff-row', String(staff.id));
                node.setAttribute('data-sort-order', String(sortOrder));

                const photoCell = node.querySelector('.staff-photo-cell');
                if (photoCell) {
                    photoCell.innerHTML = '';
                    if (staff.photo_url) {
                        const img = document.createElement('img');
                        img.src = staff.photo_url;
                        img.alt = '';
                        img.className = 'staff-photo h-12 w-12 rounded-full object-cover';
                        photoCell.appendChild(img);
                    }
                }

                const nameEl = node.querySelector('.staff-name');
                if (nameEl) {
                    nameEl.textContent = staff.name || '';
                }

                const roleEl = node.querySelector('.staff-role');
                if (roleEl) {
                    roleEl.textContent = staff.role || '';
                }

                const sortEl = node.querySelector('.staff-sort-order');
                if (sortEl) {
                    sortEl.textContent = String(sortOrder);
                }

                const statusEl = node.querySelector('.staff-status');
                if (statusEl) {
                    statusEl.textContent = staff.is_published ? '公開' : '非公開';
                }

                const editBtn = node.querySelector('[data-open-staff-edit]');
                if (editBtn) {
                    editBtn.setAttribute('data-staff-id', String(staff.id));
                }

                const deleteForm = node.querySelector('form[data-admin-delete-form]');
                if (deleteForm) {
                    deleteForm.action = staff.destroy_url || '';
                    const tokenInput = deleteForm.querySelector('input[name="_token"]');
                    if (tokenInput) {
                        tokenInput.value = csrfToken;
                    }
                    const deleteBtn = deleteForm.querySelector('[data-admin-delete-trigger]');
                    if (deleteBtn) {
                        deleteBtn.setAttribute(
                            'data-delete-message',
                            '「' + (staff.name || '') + '」を削除しますか？'
                        );
                    }
                }

                return node;
            }

            document.querySelectorAll('[data-open-staff-create]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    openCreateModal(btn);
                });
            });

            document.querySelectorAll('[data-close-staff-create]').forEach(function (btn) {
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

            createPhotoInput.addEventListener('change', function () {
                createPhotoReselectHint.classList.add('hidden');
                const file = createPhotoInput.files && createPhotoInput.files[0];

                if (!file) {
                    setCreatePhotoPreview(null);
                    return;
                }

                const error = validateCreatePhotoFile(file);
                if (error) {
                    createPhotoInput.value = '';
                    setCreatePhotoPreview(null);
                    showCreateFieldError('photo', error);
                    if (typeof window.showToast === 'function') {
                        window.showToast(error, 'error');
                    }
                    return;
                }

                clearCreateErrors();
                const reader = new FileReader();
                reader.onload = function (event) {
                    setCreatePhotoPreview(event.target.result);
                };
                reader.readAsDataURL(file);
            });

            createForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (createSubmitting) {
                    return;
                }

                clearCreateErrors();

                const file = createPhotoInput.files && createPhotoInput.files[0];
                const clientError = validateCreatePhotoFile(file);
                if (clientError) {
                    showCreateFieldError('photo', clientError);
                    if (typeof window.showToast === 'function') {
                        window.showToast(clientError, 'error');
                    }
                    return;
                }

                createSubmitting = true;
                createSubmitBtn.disabled = true;
                createSubmitBtn.textContent = '登録中...';

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
                            showCreateErrors({ name: [data.message || '登録に失敗しました。'] });
                            if (typeof window.showToast === 'function') {
                                window.showToast(data.message || '登録に失敗しました。', 'error');
                            }
                            return;
                        }

                        const staff = data.staff;
                        if (staff) {
                            staffData[String(staff.id)] = {
                                id: staff.id,
                                name: staff.name,
                                role: staff.role,
                                profile: staff.profile,
                                sort_order: staff.sort_order,
                                is_published: !!staff.is_published,
                                photo_url: staff.photo_url || null,
                                update_url: staff.update_url,
                            };
                            if (dataEl) {
                                dataEl.textContent = JSON.stringify(staffData);
                            }

                            const row = buildStaffRow(staff);
                            insertStaffRow(row);
                        }

                        createSubmitting = false;
                        createSubmitBtn.disabled = false;
                        createSubmitBtn.textContent = '登録する';
                        closeCreateModal();

                        if (typeof window.showToast === 'function') {
                            window.showToast(data.message || 'スタッフを登録しました。', 'success');
                        }
                    })
                    .catch(function () {
                        showCreateErrors({ name: ['通信エラーが発生しました。'] });
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
