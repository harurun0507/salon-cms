<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '管理画面') - Salon CMS</title>
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            'salon-bg': '#FAF7F1',
                            'salon-text': '#3A332E',
                            'salon-accent': '#7C8A6A',
                            'salon-button': '#5F6F52',
                            'salon-line': '#E3DDD2',
                            'salon-muted': '#6B635C',
                        }
                    }
                }
            }
        </script>
        <style type="text/tailwindcss">
            @layer components {
                .admin-card { @apply rounded-lg border border-gray-200 bg-white p-6 shadow-sm; }
                .admin-input { @apply w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500; }
                .admin-label { @apply mb-1 block text-sm font-medium text-gray-700; }
                .admin-btn { @apply inline-flex items-center rounded-md bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800; }
                .admin-btn-secondary { @apply inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50; }
                .admin-btn-danger { @apply inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700; }
                .admin-action-group { @apply flex flex-wrap items-center justify-end gap-2; }
                .btn-admin-create { @apply inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-salon-button px-4 text-sm font-medium text-white shadow-sm transition duration-150 hover:-translate-y-0.5 hover:bg-[#4f5d44] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-salon-button/40 focus:ring-offset-1 disabled:pointer-events-none disabled:opacity-50; }
                .btn-admin-edit { @apply inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-salon-button bg-white px-3 text-sm font-medium text-salon-button shadow-sm transition duration-150 hover:-translate-y-0.5 hover:bg-salon-button hover:text-white hover:shadow-md focus:outline-none focus:ring-2 focus:ring-salon-button/40 focus:ring-offset-1; }
                .btn-admin-delete { @apply inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-red-500 bg-white px-3 text-sm font-medium text-red-600 shadow-sm transition duration-150 hover:-translate-y-0.5 hover:border-red-600 hover:bg-red-600 hover:text-white hover:shadow-md focus:outline-none focus:ring-2 focus:ring-red-400/50 focus:ring-offset-1; }
            }
        </style>
    @endif
</head>
<body class="bg-gray-100 font-sans text-gray-900">
    <div class="flex min-h-screen">
        <aside class="hidden w-64 shrink-0 bg-[#5F6B5A] text-[#F7F4EE] md:block">
            <div class="border-b border-white/12 px-6 py-5">
                <p class="text-lg font-semibold text-[#F7F4EE]">Salon CMS</p>
                <p class="text-xs text-[#F7F4EE]/70">管理画面</p>
            </div>
            <nav class="space-y-1 p-4 text-sm text-[#F7F4EE]">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 rounded px-3 py-2 text-[#F7F4EE]/90 transition-colors duration-150 hover:bg-[#6F7C64] hover:text-[#F7F4EE] {{ request()->routeIs('admin.dashboard') ? 'bg-[#7D8F73] text-[#F7F4EE]' : '' }}">
                    <x-admin.nav-icon name="dashboard" />
                    <span>ダッシュボード</span>
                </a>
                <a href="{{ route('admin.news.index') }}" class="flex items-center gap-2.5 rounded px-3 py-2 text-[#F7F4EE]/90 transition-colors duration-150 hover:bg-[#6F7C64] hover:text-[#F7F4EE] {{ request()->routeIs('admin.news.*') ? 'bg-[#7D8F73] text-[#F7F4EE]' : '' }}">
                    <x-admin.nav-icon name="news" />
                    <span>お知らせ</span>
                </a>
                <a href="{{ route('admin.galleries.index') }}" class="flex items-center gap-2.5 rounded px-3 py-2 text-[#F7F4EE]/90 transition-colors duration-150 hover:bg-[#6F7C64] hover:text-[#F7F4EE] {{ request()->routeIs('admin.galleries.*') ? 'bg-[#7D8F73] text-[#F7F4EE]' : '' }}">
                    <x-admin.nav-icon name="gallery" />
                    <span>ギャラリー</span>
                </a>
                <a href="{{ route('admin.menus.index') }}" class="flex items-center gap-2.5 rounded px-3 py-2 text-[#F7F4EE]/90 transition-colors duration-150 hover:bg-[#6F7C64] hover:text-[#F7F4EE] {{ request()->routeIs('admin.menus.*') ? 'bg-[#7D8F73] text-[#F7F4EE]' : '' }}">
                    <x-admin.nav-icon name="menus" />
                    <span>メニュー・料金</span>
                </a>
                <a href="{{ route('admin.staff.index') }}" class="flex items-center gap-2.5 rounded px-3 py-2 text-[#F7F4EE]/90 transition-colors duration-150 hover:bg-[#6F7C64] hover:text-[#F7F4EE] {{ request()->routeIs('admin.staff.*') ? 'bg-[#7D8F73] text-[#F7F4EE]' : '' }}">
                    <x-admin.nav-icon name="staff" />
                    <span>スタッフ</span>
                </a>
                <a href="{{ route('admin.settings.edit') }}" class="flex items-center gap-2.5 rounded px-3 py-2 text-[#F7F4EE]/90 transition-colors duration-150 hover:bg-[#6F7C64] hover:text-[#F7F4EE] {{ request()->routeIs('admin.settings.*') ? 'bg-[#7D8F73] text-[#F7F4EE]' : '' }}">
                    <x-admin.nav-icon name="settings" />
                    <span>店舗情報</span>
                </a>
                <a href="{{ route('home') }}" target="_blank" class="flex items-center gap-2.5 rounded px-3 py-2 text-[#F7F4EE]/90 transition-colors duration-150 hover:bg-[#6F7C64] hover:text-[#F7F4EE]">
                    <x-admin.nav-icon name="external" />
                    <span>公開サイトを見る</span>
                </a>
            </nav>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 flex items-center justify-between border-b border-gray-200 bg-white px-4 py-4 md:px-8">
                <h1 class="text-lg font-semibold">@yield('heading', '管理画面')</h1>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="admin-btn-secondary">ログアウト</button>
                </form>
            </header>

            <main class="flex-1 p-4 md:p-8">
                @if($errors->any())
                    <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <ul class="list-disc pl-5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <style>
        #admin-toast-stack {
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            z-index: 70;
            display: flex;
            width: min(100% - 2rem, 22rem);
            flex-direction: column;
            gap: 0.75rem;
            pointer-events: none;
        }

        .admin-toast-item {
            pointer-events: auto;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            border-radius: 0.75rem;
            background: #FAF7F1;
            box-shadow: 0 10px 30px rgba(58, 51, 46, 0.12);
            border: 1px solid #E3DDD2;
            overflow: hidden;
            opacity: 0;
            transform: translateY(0.75rem);
            transition: opacity 220ms ease, transform 220ms ease;
        }

        .admin-toast-item.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .admin-toast-item.is-hiding {
            opacity: 0;
            transform: translateY(0.5rem);
        }

        .admin-toast-item--success {
            background: #FAF7F1;
        }

        .admin-toast-item--error {
            background: #fff;
        }

        .admin-toast-accent {
            width: 4px;
            align-self: stretch;
            flex-shrink: 0;
        }

        .admin-toast-item--success .admin-toast-accent {
            background: #5F6F52;
        }

        .admin-toast-item--error .admin-toast-accent {
            background: #C45C5C;
        }

        .admin-toast-icon {
            margin-top: 0.95rem;
            margin-left: 0.15rem;
            display: inline-flex;
            height: 1.25rem;
            width: 1.25rem;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
        }

        .admin-toast-item--success .admin-toast-icon {
            color: #5F6F52;
        }

        .admin-toast-item--error .admin-toast-icon {
            color: #C45C5C;
        }

        .admin-toast-body {
            flex: 1;
            min-width: 0;
            padding: 0.9rem 0;
        }

        .admin-toast-text {
            margin: 0;
            font-size: 0.875rem;
            line-height: 1.5;
            color: #3A332E;
        }

        .admin-toast-item--error .admin-toast-text {
            color: #7A3E3E;
        }

        .admin-toast-close {
            margin: 0.45rem 0.45rem 0 0;
            border: 0;
            background: transparent;
            border-radius: 0.375rem;
            color: #6B635C;
            cursor: pointer;
            padding: 0.35rem;
            line-height: 0;
        }

        .admin-toast-close:hover {
            background: rgba(58, 51, 46, 0.06);
            color: #3A332E;
        }

        @media (prefers-reduced-motion: reduce) {
            .admin-toast-item {
                transition: none;
                transform: none;
            }

            .admin-toast-item.is-hiding {
                transform: none;
            }
        }
    </style>

    {{-- 管理画面共通トースト（右下） --}}
    <div id="admin-toast-stack" aria-live="polite" aria-relevant="additions"></div>
    <script type="application/json" id="admin-flash-data">{!! json_encode([
        'success' => session('success'),
        'error' => session('error'),
        'status' => session('status'),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

    <script>
        (function () {
            window.AdminUi = {
                getFocusable: function (root) {
                    return Array.from(
                        root.querySelectorAll(
                            'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
                        )
                    ).filter(function (el) {
                        return !el.hasAttribute('disabled') && el.getClientRects().length > 0;
                    });
                },
                lockBody: function () {
                    document.body.style.overflow = 'hidden';
                },
                unlockBody: function () {
                    document.body.style.overflow = '';
                },
                trapFocus: function (event, root) {
                    if (event.key !== 'Tab') {
                        return;
                    }

                    const focusable = window.AdminUi.getFocusable(root);
                    if (focusable.length === 0) {
                        return;
                    }

                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                },
            };

            const icons = {
                success: '<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>',
                error: '<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>',
            };

            function dismissToast(item, immediate) {
                if (!item || item.dataset.closing === '1') {
                    return;
                }

                item.dataset.closing = '1';
                if (item._hideTimer) {
                    clearTimeout(item._hideTimer);
                    item._hideTimer = null;
                }

                if (immediate) {
                    item.remove();
                    return;
                }

                item.classList.remove('is-visible');
                item.classList.add('is-hiding');
                setTimeout(function () {
                    item.remove();
                }, 220);
            }

            function scheduleHide(item, duration) {
                if (!duration) {
                    return;
                }

                if (item._hideTimer) {
                    clearTimeout(item._hideTimer);
                }

                item._hideTimer = setTimeout(function () {
                    dismissToast(item);
                }, duration);
            }

            window.AdminToast = {
                show: function (message, type) {
                    const stack = document.getElementById('admin-toast-stack');
                    if (!stack || !message) {
                        return null;
                    }

                    const tone = type === 'error' ? 'error' : 'success';
                    const duration = tone === 'error' ? 5000 : 3000;

                    const item = document.createElement('div');
                    item.className = 'admin-toast-item admin-toast-item--' + tone;
                    if (tone === 'error') {
                        item.setAttribute('role', 'alert');
                    } else {
                        item.setAttribute('role', 'status');
                    }

                    item.innerHTML =
                        '<span class="admin-toast-accent" aria-hidden="true"></span>' +
                        '<span class="admin-toast-icon">' + icons[tone] + '</span>' +
                        '<div class="admin-toast-body"><p class="admin-toast-text"></p></div>' +
                        '<button type="button" class="admin-toast-close" aria-label="閉じる">' +
                        '<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>' +
                        '</button>';

                    item.querySelector('.admin-toast-text').textContent = message;
                    item.querySelector('.admin-toast-close').addEventListener('click', function () {
                        dismissToast(item);
                    });

                    item.addEventListener('mouseenter', function () {
                        if (item._hideTimer) {
                            clearTimeout(item._hideTimer);
                            item._hideTimer = null;
                        }
                    });

                    item.addEventListener('mouseleave', function () {
                        if (item.dataset.closing === '1') {
                            return;
                        }
                        scheduleHide(item, duration);
                    });

                    stack.appendChild(item);
                    requestAnimationFrame(function () {
                        item.classList.add('is-visible');
                    });
                    scheduleHide(item, duration);

                    return item;
                },
            };

            window.showToast = function (message, type) {
                return window.AdminToast.show(message, type);
            };

            try {
                const flash = JSON.parse(document.getElementById('admin-flash-data')?.textContent || '{}');
                if (flash.success) {
                    window.showToast(flash.success, 'success');
                }
                if (flash.error) {
                    window.showToast(flash.error, 'error');
                }
                if (flash.status && flash.status !== flash.success) {
                    window.showToast(flash.status, 'success');
                }
            } catch (e) {
                // ignore invalid flash payload
            }
        })();
    </script>

    {{-- 管理画面共通・保存確認モーダル（削除モーダルとは別） --}}
    <div
        id="admin-confirm-modal"
        class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/40 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="admin-confirm-modal-title"
        hidden
    >
        <div class="w-full max-w-md rounded-xl border border-[#E3DDD2] bg-[#FFFcf7] p-6 shadow-lg" data-admin-confirm-panel>
            <div class="mb-4 flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#E8EDE3] text-[#5F6F52]" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                    <div>
                        <h2 id="admin-confirm-modal-title" class="text-lg font-semibold text-gray-900">確認</h2>
                        <p id="admin-confirm-modal-message" class="mt-2 whitespace-pre-line text-sm text-gray-700"></p>
                        <p id="admin-confirm-modal-note" class="mt-2 hidden text-sm text-gray-500"></p>
                    </div>
                </div>
                <button
                    type="button"
                    class="rounded-md p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-salon-button/40"
                    aria-label="閉じる"
                    data-admin-confirm-cancel
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                    </svg>
                </button>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-salon-button/30"
                    data-admin-confirm-cancel
                >キャンセル</button>
                <button
                    type="button"
                    id="admin-confirm-submit"
                    class="inline-flex items-center rounded-lg bg-salon-button px-4 py-2 text-sm font-medium text-white transition hover:bg-[#4f5d44] focus:outline-none focus:ring-2 focus:ring-salon-button/40 disabled:cursor-not-allowed disabled:opacity-60"
                >実行する</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('admin-confirm-modal');
            if (!modal) {
                return;
            }

            const titleEl = document.getElementById('admin-confirm-modal-title');
            const messageEl = document.getElementById('admin-confirm-modal-message');
            const noteEl = document.getElementById('admin-confirm-modal-note');
            const submitBtn = document.getElementById('admin-confirm-submit');
            let activeTrigger = null;
            let activeForm = null;
            let submitting = false;
            let defaultSubmitLabel = '実行する';

            function openConfirmModal(trigger) {
                const formId = trigger.getAttribute('data-confirm-form');
                const form = formId ? document.getElementById(formId) : null;
                if (!form) {
                    return;
                }

                activeTrigger = trigger;
                activeForm = form;
                submitting = false;
                defaultSubmitLabel = trigger.getAttribute('data-confirm-submit-label') || '実行する';

                titleEl.textContent = trigger.getAttribute('data-confirm-title') || '確認';
                messageEl.textContent = trigger.getAttribute('data-confirm-message') || 'よろしいですか？';

                const note = trigger.getAttribute('data-confirm-note') || '';
                if (note) {
                    noteEl.textContent = note;
                    noteEl.classList.remove('hidden');
                } else {
                    noteEl.textContent = '';
                    noteEl.classList.add('hidden');
                }

                submitBtn.textContent = defaultSubmitLabel;
                submitBtn.disabled = false;

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
                        submitBtn.focus();
                    }, 0);
                });
            }

            function closeConfirmModal() {
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

                const restore = activeTrigger;
                activeTrigger = null;
                activeForm = null;
                if (restore && typeof restore.focus === 'function') {
                    restore.focus();
                }
            }

            document.addEventListener('click', function (e) {
                const trigger = e.target.closest('[data-admin-confirm-trigger]');
                if (!trigger) {
                    return;
                }

                e.preventDefault();
                openConfirmModal(trigger);
            });

            modal.querySelectorAll('[data-admin-confirm-cancel]').forEach(function (btn) {
                btn.addEventListener('click', closeConfirmModal);
            });

            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    closeConfirmModal();
                }
            });

            document.addEventListener('keydown', function (e) {
                if (modal.classList.contains('hidden')) {
                    return;
                }

                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeConfirmModal();
                    return;
                }

                if (window.AdminUi) {
                    window.AdminUi.trapFocus(e, modal);
                }
            });

            submitBtn.addEventListener('click', function () {
                if (submitting || !activeForm) {
                    return;
                }

                if (typeof activeForm.reportValidity === 'function' && !activeForm.reportValidity()) {
                    closeConfirmModal();
                    return;
                }

                submitting = true;
                submitBtn.disabled = true;
                submitBtn.textContent = '保存中...';
                activeForm.submit();
            });
        })();
    </script>

    {{-- 管理画面共通・削除確認モーダル --}}
    <div
        id="admin-delete-modal"
        class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/40 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="admin-delete-modal-title"
        hidden
    >
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-lg" data-admin-delete-panel>
            <div class="mb-4 flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-500" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.5 3a1.5 1.5 0 0 0-1.415 1H4a1 1 0 0 0 0 2h.293l.72 9.364A2.5 2.5 0 0 0 7.505 18h4.99a2.5 2.5 0 0 0 2.492-2.636L15.707 6H16a1 1 0 1 0 0-2h-3.085A1.5 1.5 0 0 0 11.5 3h-3Zm1 1a.5.5 0 0 0-.5.5V5h2v-.5a.5.5 0 0 0-.5-.5h-1ZM7.3 6l.69 8.97a.5.5 0 0 0 .498.53h3.024a.5.5 0 0 0 .498-.53L12.7 6H7.3Z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                    <div>
                        <h2 id="admin-delete-modal-title" class="text-lg font-semibold text-gray-900">削除の確認</h2>
                        <p id="admin-delete-modal-message" class="mt-2 text-sm text-gray-700"></p>
                        <p class="mt-2 text-sm text-gray-500">この操作は取り消せません。</p>
                    </div>
                </div>
                <button
                    type="button"
                    id="admin-delete-modal-close"
                    class="rounded-md p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-salon-button/40"
                    aria-label="閉じる"
                    data-admin-delete-cancel
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                    </svg>
                </button>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-salon-button/30"
                    data-admin-delete-cancel
                >キャンセル</button>
                <button
                    type="button"
                    id="admin-delete-confirm"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-red-500 bg-white px-4 py-2 text-sm font-medium text-red-600 transition hover:border-red-600 hover:bg-red-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-red-400/40 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8.5 3a1.5 1.5 0 0 0-1.415 1H4a1 1 0 0 0 0 2h.293l.72 9.364A2.5 2.5 0 0 0 7.505 18h4.99a2.5 2.5 0 0 0 2.492-2.636L15.707 6H16a1 1 0 1 0 0-2h-3.085A1.5 1.5 0 0 0 11.5 3h-3Zm1 1a.5.5 0 0 0-.5.5V5h2v-.5a.5.5 0 0 0-.5-.5h-1ZM7.3 6l.69 8.97a.5.5 0 0 0 .498.53h3.024a.5.5 0 0 0 .498-.53L12.7 6H7.3Z" clip-rule="evenodd"/>
                    </svg>
                    削除する
                </button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('admin-delete-modal');
            if (!modal) {
                return;
            }

            const messageEl = document.getElementById('admin-delete-modal-message');
            const confirmBtn = document.getElementById('admin-delete-confirm');
            let activeTrigger = null;
            let activeForm = null;
            let submitting = false;

            function getFocusable() {
                return Array.from(
                    modal.querySelectorAll(
                        'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
                    )
                ).filter(function (el) {
                    return !el.hasAttribute('disabled') && el.getClientRects().length > 0;
                });
            }

            function resolveForm(trigger) {
                const formId = trigger.getAttribute('data-delete-form');
                if (formId) {
                    return document.getElementById(formId);
                }

                return trigger.closest('form[data-admin-delete-form]') || trigger.closest('form');
            }

            function openModal(trigger) {
                const form = resolveForm(trigger);
                if (!form) {
                    return;
                }

                activeTrigger = trigger;
                activeForm = form;
                submitting = false;
                confirmBtn.disabled = false;
                messageEl.textContent = trigger.getAttribute('data-delete-message') || 'このデータを削除しますか？';

                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.removeAttribute('hidden');
                document.body.style.overflow = 'hidden';

                requestAnimationFrame(function () {
                    setTimeout(function () {
                        confirmBtn.focus();
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
                document.body.style.overflow = '';

                const restore = activeTrigger;
                activeTrigger = null;
                activeForm = null;

                if (restore && typeof restore.focus === 'function') {
                    restore.focus();
                }
            }

            document.addEventListener('click', function (e) {
                const trigger = e.target.closest('[data-admin-delete-trigger]');
                if (!trigger) {
                    return;
                }

                e.preventDefault();
                openModal(trigger);
            });

            modal.querySelectorAll('[data-admin-delete-cancel]').forEach(function (btn) {
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

            confirmBtn.addEventListener('click', function () {
                if (submitting || !activeForm) {
                    return;
                }

                submitting = true;
                confirmBtn.disabled = true;
                activeForm.submit();
            });
        })();
    </script>
</body>
</html>
