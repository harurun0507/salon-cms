@extends('layouts.admin')

@section('heading', '管理ユーザー')

@section('content')
    @php
        $oldNewUsers = old('new_users', []);
        if (! is_array($oldNewUsers)) {
            $oldNewUsers = [];
        }
        $nextNewIndex = 1;
        foreach (array_keys($oldNewUsers) as $key) {
            if (preg_match('/^new_(\d+)$/', (string) $key, $m)) {
                $nextNewIndex = max($nextNewIndex, ((int) $m[1]) + 1);
            }
        }

        $formatLastLogin = function ($value): string {
            if (! $value) {
                return '未ログイン';
            }
            try {
                return \Illuminate\Support\Carbon::parse($value)->timezone(config('app.timezone'))->format('Y-m-d H:i');
            } catch (\Throwable $e) {
                return '未ログイン';
            }
        };
    @endphp

    <div class="sticky top-[4.5rem] z-10 -mx-4 -mt-4 mb-6 border-b border-admin-border/50 bg-admin-bg/95 px-4 py-3 shadow-[0_1px_0_rgba(61,56,51,0.03)] backdrop-blur-sm md:-mx-8 md:-mt-8 md:px-8">
        <div class="flex min-w-0 flex-wrap items-center gap-3">
            <button
                type="button"
                class="admin-btn shadow-md shrink-0"
                data-admin-confirm-trigger
                data-confirm-form="users-bulk-form"
                data-confirm-title="管理ユーザー保存の確認"
                data-confirm-message="管理ユーザーの変更内容を保存します。よろしいですか？"
                data-confirm-submit-label="保存する"
            >保存する</button>
            <p class="text-sm text-admin-muted">
                カードで編集し、「保存する」でまとめて反映できます
            </p>
        </div>
    </div>

    <div class="mb-6">
        <p class="text-sm text-admin-muted">管理画面にログインできるユーザーを管理します。ログイン実績のないユーザーは削除、実績のあるユーザーは無効化されます。</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('admin.system.users.update') }}"
        id="users-bulk-form"
        data-users-workspace
        data-next-new-index="{{ $nextNewIndex }}"
        data-current-user-id="{{ $currentUserId }}"
        data-active-admin-count="{{ $activeAdminCount }}"
    >
        @csrf
        @method('PUT')

        <div id="users-deleted-ids"></div>

        <div id="users-grid" class="grid grid-cols-1 gap-4 md:grid-cols-2" data-users-grid>
            @foreach($users as $user)
                @php
                    $prefix = 'users.'.$user->id;
                    $isSelf = (int) $user->id === (int) $currentUserId;
                    $roleOld = old($prefix.'.role', $user->role);
                    $activeOld = old($prefix.'.is_active', $user->is_active ? '1' : '0');
                    $isActive = in_array((string) $activeOld, ['1', 'true', 'on'], true);
                    $isAdminRole = $roleOld === 'admin';
                    $isLastActiveAdmin = $activeAdminCount <= 1 && $user->role === 'admin' && $user->is_active;
                    $lockAdminRole = $isSelf || $isLastActiveAdmin;
                    $lockActive = $isSelf || $isLastActiveAdmin;
                    $canDelete = ! $isSelf && ! $isLastActiveAdmin;
                    $cardName = trim((string) old($prefix.'.name', $user->name));
                    $headingTitle = $cardName !== '' ? $cardName : '新規管理ユーザー';
                @endphp
                <div
                    class="admin-card user-card"
                    data-user-card
                    data-user-id="{{ $user->id }}"
                    data-user-existing
                    @if($isSelf) data-user-self @endif
                    @if($user->role === 'admin' && $user->is_active) data-user-active-admin @endif
                >
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <p class="banner-card-label truncate text-sm font-medium text-gray-800" data-user-card-title title="{{ $headingTitle }}">{{ $headingTitle }}</p>
                        @if($canDelete)
                            <button
                                type="button"
                                class="admin-icon-btn admin-icon-btn-delete"
                                data-user-remove
                                aria-label="削除"
                                title="削除"
                            >
                                <span aria-hidden="true">&times;</span>
                            </button>
                        @else
                            <span
                                class="admin-icon-btn pointer-events-none opacity-40"
                                aria-hidden="true"
                                title="{{ $isSelf ? '自分自身は削除できません' : '最後の管理者は削除できません' }}"
                            >
                                <span>&times;</span>
                            </span>
                        @endif
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label for="user_name_{{ $user->id }}" class="admin-label">名前 <span class="admin-required-badge">必須</span></label>
                            <input
                                type="text"
                                name="users[{{ $user->id }}][name]"
                                id="user_name_{{ $user->id }}"
                                value="{{ old($prefix.'.name', $user->name) }}"
                                maxlength="255"
                                required
                                class="admin-input"
                                data-user-name-input
                            >
                            @error($prefix.'.name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="user_email_{{ $user->id }}" class="admin-label">メールアドレス <span class="admin-required-badge">必須</span></label>
                            <input
                                type="email"
                                name="users[{{ $user->id }}][email]"
                                id="user_email_{{ $user->id }}"
                                value="{{ old($prefix.'.email', $user->email) }}"
                                maxlength="255"
                                required
                                class="admin-input"
                            >
                            @error($prefix.'.email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <span class="admin-label">権限</span>
                            <div class="admin-segmented mt-1" role="radiogroup" aria-label="権限">
                                <label class="admin-segmented-option">
                                    <input
                                        type="radio"
                                        name="users[{{ $user->id }}][role]"
                                        value="admin"
                                        class="admin-segmented-input"
                                        data-user-role-input
                                        @checked($isAdminRole)
                                        @if($lockAdminRole && ! $isAdminRole) disabled @endif
                                    >
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <path d="M8 1.75 13.25 4v3.2c0 3.35-2.15 5.85-5.25 6.8-3.1-.95-5.25-3.45-5.25-6.8V4L8 1.75Z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>
                                            <path d="M6.2 8.1 7.35 9.25 9.9 6.7" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <span class="admin-segmented-text">管理者</span>
                                    </span>
                                </label>
                                <label class="admin-segmented-option {{ $lockAdminRole ? 'opacity-60' : '' }}">
                                    <input
                                        type="radio"
                                        name="users[{{ $user->id }}][role]"
                                        value="editor"
                                        class="admin-segmented-input"
                                        data-user-role-input
                                        @checked(! $isAdminRole)
                                        @if($lockAdminRole) disabled @endif
                                    >
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <circle cx="8" cy="5.25" r="2.35" stroke="currentColor" stroke-width="1.35"/>
                                            <path d="M3.5 13.25c.7-2.35 2.35-3.5 4.5-3.5s3.8 1.15 4.5 3.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
                                            <path d="M10.6 3.9 13 6.3M11.1 7.05l1.55-.55" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/>
                                        </svg>
                                        <span class="admin-segmented-text">編集者</span>
                                    </span>
                                </label>
                            </div>
                            @if($lockAdminRole)
                                <input type="hidden" name="users[{{ $user->id }}][role]" value="admin">
                            @endif
                            @error($prefix.'.role')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <span class="admin-label">状態</span>
                            <div class="admin-segmented mt-1" role="radiogroup" aria-label="状態">
                                <label class="admin-segmented-option">
                                    <input
                                        type="radio"
                                        name="users[{{ $user->id }}][is_active]"
                                        value="1"
                                        class="admin-segmented-input"
                                        data-user-active-input
                                        @checked($isActive)
                                        @if($lockActive && ! $isActive) disabled @endif
                                    >
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <circle cx="8" cy="8" r="5.25" stroke="currentColor" stroke-width="1.35"/>
                                            <path d="M5.5 8.1 7.15 9.7 10.6 6.2" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <span class="admin-segmented-text">有効</span>
                                    </span>
                                </label>
                                <label class="admin-segmented-option {{ $lockActive ? 'opacity-60' : '' }}">
                                    <input
                                        type="radio"
                                        name="users[{{ $user->id }}][is_active]"
                                        value="0"
                                        class="admin-segmented-input"
                                        data-user-active-input
                                        @checked(! $isActive)
                                        @if($lockActive) disabled @endif
                                    >
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <circle cx="8" cy="8" r="5.25" stroke="currentColor" stroke-width="1.35"/>
                                            <path d="M5.4 5.4 10.6 10.6M10.6 5.4 5.4 10.6" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
                                        </svg>
                                        <span class="admin-segmented-text">無効</span>
                                    </span>
                                </label>
                            </div>
                            @if($lockActive)
                                <input type="hidden" name="users[{{ $user->id }}][is_active]" value="1">
                            @endif
                            @error($prefix.'.is_active')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="user_password_{{ $user->id }}" class="admin-label">パスワード</label>
                            <input
                                type="password"
                                name="users[{{ $user->id }}][password]"
                                id="user_password_{{ $user->id }}"
                                value=""
                                autocomplete="new-password"
                                class="admin-input"
                                placeholder="変更する場合のみ入力"
                            >
                            <p class="mt-1 text-xs text-admin-muted">空欄の場合は現在のパスワードを維持します（8文字以上）</p>
                            @error($prefix.'.password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="user_password_confirmation_{{ $user->id }}" class="admin-label">パスワード（確認）</label>
                            <input
                                type="password"
                                name="users[{{ $user->id }}][password_confirmation]"
                                id="user_password_confirmation_{{ $user->id }}"
                                value=""
                                autocomplete="new-password"
                                class="admin-input"
                                placeholder="変更する場合のみ入力"
                            >
                        </div>

                        <div>
                            <span class="admin-label">最終ログイン</span>
                            <p class="mt-1 text-sm text-admin-muted" data-user-last-login>{{ $formatLastLogin($user->last_login_at) }}</p>
                        </div>
                    </div>
                </div>
            @endforeach

            @foreach($oldNewUsers as $key => $newItem)
                @php
                    if (! is_array($newItem)) {
                        continue;
                    }
                    $prefix = 'new_users.'.$key;
                    $roleOld = old($prefix.'.role', $newItem['role'] ?? 'editor');
                    $activeOld = old($prefix.'.is_active', $newItem['is_active'] ?? '1');
                    $isActive = in_array((string) $activeOld, ['1', 'true', 'on'], true);
                    $isAdminRole = $roleOld === 'admin';
                    $cardName = trim((string) old($prefix.'.name', $newItem['name'] ?? ''));
                    $headingTitle = $cardName !== '' ? $cardName : '新規管理ユーザー';
                    $email = old($prefix.'.email', $newItem['email'] ?? '');
                @endphp
                <div
                    class="admin-card user-card"
                    data-user-card
                    data-user-new="1"
                >
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <p class="banner-card-label truncate text-sm font-medium text-gray-800" data-user-card-title title="{{ $headingTitle }}">{{ $headingTitle }}</p>
                        <button
                            type="button"
                            class="admin-icon-btn admin-icon-btn-delete"
                            data-user-remove
                            aria-label="削除"
                            title="削除"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="admin-label">名前 <span class="admin-required-badge">必須</span></label>
                            <input
                                type="text"
                                name="new_users[{{ $key }}][name]"
                                value="{{ $cardName }}"
                                maxlength="255"
                                required
                                class="admin-input"
                                data-user-name-input
                            >
                            @error($prefix.'.name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="admin-label">メールアドレス <span class="admin-required-badge">必須</span></label>
                            <input
                                type="email"
                                name="new_users[{{ $key }}][email]"
                                value="{{ $email }}"
                                maxlength="255"
                                required
                                class="admin-input"
                            >
                            @error($prefix.'.email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <span class="admin-label">権限</span>
                            <div class="admin-segmented mt-1" role="radiogroup" aria-label="権限">
                                <label class="admin-segmented-option">
                                    <input type="radio" name="new_users[{{ $key }}][role]" value="admin" class="admin-segmented-input" @checked($isAdminRole)>
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <path d="M8 1.75 13.25 4v3.2c0 3.35-2.15 5.85-5.25 6.8-3.1-.95-5.25-3.45-5.25-6.8V4L8 1.75Z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>
                                            <path d="M6.2 8.1 7.35 9.25 9.9 6.7" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <span class="admin-segmented-text">管理者</span>
                                    </span>
                                </label>
                                <label class="admin-segmented-option">
                                    <input type="radio" name="new_users[{{ $key }}][role]" value="editor" class="admin-segmented-input" @checked(! $isAdminRole)>
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <circle cx="8" cy="5.25" r="2.35" stroke="currentColor" stroke-width="1.35"/>
                                            <path d="M3.5 13.25c.7-2.35 2.35-3.5 4.5-3.5s3.8 1.15 4.5 3.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
                                            <path d="M10.6 3.9 13 6.3M11.1 7.05l1.55-.55" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/>
                                        </svg>
                                        <span class="admin-segmented-text">編集者</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <span class="admin-label">状態</span>
                            <div class="admin-segmented mt-1" role="radiogroup" aria-label="状態">
                                <label class="admin-segmented-option">
                                    <input type="radio" name="new_users[{{ $key }}][is_active]" value="1" class="admin-segmented-input" @checked($isActive)>
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <circle cx="8" cy="8" r="5.25" stroke="currentColor" stroke-width="1.35"/>
                                            <path d="M5.5 8.1 7.15 9.7 10.6 6.2" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <span class="admin-segmented-text">有効</span>
                                    </span>
                                </label>
                                <label class="admin-segmented-option">
                                    <input type="radio" name="new_users[{{ $key }}][is_active]" value="0" class="admin-segmented-input" @checked(! $isActive)>
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <circle cx="8" cy="8" r="5.25" stroke="currentColor" stroke-width="1.35"/>
                                            <path d="M5.4 5.4 10.6 10.6M10.6 5.4 5.4 10.6" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
                                        </svg>
                                        <span class="admin-segmented-text">無効</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="admin-label">パスワード <span class="admin-required-badge">必須</span></label>
                            <input
                                type="password"
                                name="new_users[{{ $key }}][password]"
                                value=""
                                autocomplete="new-password"
                                required
                                class="admin-input"
                                placeholder="8文字以上"
                            >
                            @error($prefix.'.password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="admin-label">パスワード（確認） <span class="admin-required-badge">必須</span></label>
                            <input
                                type="password"
                                name="new_users[{{ $key }}][password_confirmation]"
                                value=""
                                autocomplete="new-password"
                                required
                                class="admin-input"
                            >
                        </div>
                        <div>
                            <span class="admin-label">最終ログイン</span>
                            <p class="mt-1 text-sm text-admin-muted">未ログイン</p>
                        </div>
                    </div>
                </div>
            @endforeach

            <div
                id="users-add-card"
                class="admin-card flex min-h-[18rem] w-full flex-col items-center justify-center px-6 py-10 text-center"
            >
                <div class="admin-empty-state-icon !mb-4" aria-hidden="true">
                    <svg class="h-14 w-14" viewBox="0 0 80 80" fill="none" stroke="#B8B09F" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="34" cy="30" r="10" stroke-width="1.35"/>
                        <path d="M16 62c2.5-10 9-16 18-16s15.5 6 18 16" stroke-width="1.35"/>
                        <path d="M54 28h14M61 21v14" stroke-width="1.4"/>
                        <path d="M52 48c6-1.5 11 1 14 6" stroke-width="1.25" opacity="0.75"/>
                    </svg>
                </div>
                <x-admin.create-button data-user-add>
                    管理ユーザーを追加
                </x-admin.create-button>
                <p class="mt-3 text-xs text-admin-muted">カードを追加し、保存で登録できます。</p>
            </div>
        </div>
    </form>

    <script>
        (function () {
            const form = document.getElementById('users-bulk-form');
            const grid = document.getElementById('users-grid');
            const deletedIdsWrap = document.getElementById('users-deleted-ids');
            const addCard = document.getElementById('users-add-card');
            const addButton = addCard ? addCard.querySelector('[data-user-add]') : null;
            const emptyHeading = '新規管理ユーザー';

            if (!form || !grid || !addCard || !addButton) {
                return;
            }

            let nextNewIndex = parseInt(form.getAttribute('data-next-new-index') || '1', 10);

            const roleAdminFace =
                '<svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">' +
                    '<path d="M8 1.75 13.25 4v3.2c0 3.35-2.15 5.85-5.25 6.8-3.1-.95-5.25-3.45-5.25-6.8V4L8 1.75Z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>' +
                    '<path d="M6.2 8.1 7.35 9.25 9.9 6.7" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>' +
                '</svg>' +
                '<span class="admin-segmented-text">管理者</span>';

            const roleEditorFace =
                '<svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">' +
                    '<circle cx="8" cy="5.25" r="2.35" stroke="currentColor" stroke-width="1.35"/>' +
                    '<path d="M3.5 13.25c.7-2.35 2.35-3.5 4.5-3.5s3.8 1.15 4.5 3.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>' +
                    '<path d="M10.6 3.9 13 6.3M11.1 7.05l1.55-.55" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/>' +
                '</svg>' +
                '<span class="admin-segmented-text">編集者</span>';

            const activeOnFace =
                '<svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">' +
                    '<circle cx="8" cy="8" r="5.25" stroke="currentColor" stroke-width="1.35"/>' +
                    '<path d="M5.5 8.1 7.15 9.7 10.6 6.2" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>' +
                '</svg>' +
                '<span class="admin-segmented-text">有効</span>';

            const activeOffFace =
                '<svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">' +
                    '<circle cx="8" cy="8" r="5.25" stroke="currentColor" stroke-width="1.35"/>' +
                    '<path d="M5.4 5.4 10.6 10.6M10.6 5.4 5.4 10.6" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>' +
                '</svg>' +
                '<span class="admin-segmented-text">無効</span>';

            function syncCardHeading(card) {
                const label = card.querySelector('[data-user-card-title]');
                const input = card.querySelector('[data-user-name-input]');
                if (!label || !input) {
                    return;
                }
                const value = (input.value || '').trim();
                const text = value !== '' ? value : emptyHeading;
                label.textContent = text;
                label.setAttribute('title', text);
            }

            function bindCard(card) {
                const removeBtn = card.querySelector('[data-user-remove]');
                const nameInput = card.querySelector('[data-user-name-input]');

                nameInput?.addEventListener('input', function () {
                    syncCardHeading(card);
                });
                syncCardHeading(card);

                removeBtn?.addEventListener('click', function () {
                    if (card.hasAttribute('data-user-self')) {
                        if (typeof window.showToast === 'function') {
                            window.showToast('自分自身のアカウントは削除できません。', 'error');
                        }
                        return;
                    }
                    const existingId = card.getAttribute('data-user-id');
                    if (existingId && deletedIdsWrap) {
                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'deleted_ids[]';
                        hidden.value = existingId;
                        deletedIdsWrap.appendChild(hidden);
                    }
                    card.remove();
                });
            }

            function createEmptyCard() {
                const key = 'new_' + nextNewIndex;
                nextNewIndex += 1;
                form.setAttribute('data-next-new-index', String(nextNewIndex));

                const card = document.createElement('div');
                card.className = 'admin-card user-card';
                card.setAttribute('data-user-card', '');
                card.setAttribute('data-user-new', '1');
                card.innerHTML =
                    '<div class="mb-3 flex items-center justify-between gap-3">' +
                        '<p class="banner-card-label truncate text-sm font-medium text-gray-800" data-user-card-title title="' + emptyHeading + '">' + emptyHeading + '</p>' +
                        '<button type="button" class="admin-icon-btn admin-icon-btn-delete" data-user-remove aria-label="削除" title="削除">' +
                            '<span aria-hidden="true">&times;</span>' +
                        '</button>' +
                    '</div>' +
                    '<div class="space-y-3">' +
                        '<div>' +
                            '<label for="user_new_name_' + key + '" class="admin-label">名前 <span class="admin-required-badge">必須</span></label>' +
                            '<input type="text" name="new_users[' + key + '][name]" id="user_new_name_' + key + '" value="" maxlength="255" required class="admin-input" data-user-name-input>' +
                        '</div>' +
                        '<div>' +
                            '<label for="user_new_email_' + key + '" class="admin-label">メールアドレス <span class="admin-required-badge">必須</span></label>' +
                            '<input type="email" name="new_users[' + key + '][email]" id="user_new_email_' + key + '" value="" maxlength="255" required class="admin-input">' +
                        '</div>' +
                        '<div>' +
                            '<span class="admin-label">権限</span>' +
                            '<div class="admin-segmented mt-1" role="radiogroup" aria-label="権限">' +
                                '<label class="admin-segmented-option">' +
                                    '<input type="radio" name="new_users[' + key + '][role]" value="admin" class="admin-segmented-input">' +
                                    '<span class="admin-segmented-face">' + roleAdminFace + '</span>' +
                                '</label>' +
                                '<label class="admin-segmented-option">' +
                                    '<input type="radio" name="new_users[' + key + '][role]" value="editor" class="admin-segmented-input" checked>' +
                                    '<span class="admin-segmented-face">' + roleEditorFace + '</span>' +
                                '</label>' +
                            '</div>' +
                        '</div>' +
                        '<div>' +
                            '<span class="admin-label">状態</span>' +
                            '<div class="admin-segmented mt-1" role="radiogroup" aria-label="状態">' +
                                '<label class="admin-segmented-option">' +
                                    '<input type="radio" name="new_users[' + key + '][is_active]" value="1" class="admin-segmented-input" checked>' +
                                    '<span class="admin-segmented-face">' + activeOnFace + '</span>' +
                                '</label>' +
                                '<label class="admin-segmented-option">' +
                                    '<input type="radio" name="new_users[' + key + '][is_active]" value="0" class="admin-segmented-input">' +
                                    '<span class="admin-segmented-face">' + activeOffFace + '</span>' +
                                '</label>' +
                            '</div>' +
                        '</div>' +
                        '<div>' +
                            '<label for="user_new_password_' + key + '" class="admin-label">パスワード <span class="admin-required-badge">必須</span></label>' +
                            '<input type="password" name="new_users[' + key + '][password]" id="user_new_password_' + key + '" value="" autocomplete="new-password" required class="admin-input" placeholder="8文字以上">' +
                        '</div>' +
                        '<div>' +
                            '<label for="user_new_password_confirmation_' + key + '" class="admin-label">パスワード（確認） <span class="admin-required-badge">必須</span></label>' +
                            '<input type="password" name="new_users[' + key + '][password_confirmation]" id="user_new_password_confirmation_' + key + '" value="" autocomplete="new-password" required class="admin-input">' +
                        '</div>' +
                        '<div>' +
                            '<span class="admin-label">最終ログイン</span>' +
                            '<p class="mt-1 text-sm text-admin-muted">未ログイン</p>' +
                        '</div>' +
                    '</div>';

                addCard.before(card);
                bindCard(card);
                const focusInput = card.querySelector('[data-user-name-input]');
                if (focusInput) {
                    focusInput.focus();
                }
            }

            grid.querySelectorAll('[data-user-card]').forEach(bindCard);

            addButton.addEventListener('click', function (e) {
                e.preventDefault();
                createEmptyCard();
            });
        })();
    </script>
@endsection
