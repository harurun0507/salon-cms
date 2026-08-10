<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $adminPageTitle = trim($__env->yieldContent('title'));
        if ($adminPageTitle === '') {
            $adminPageTitle = trim($__env->yieldContent('heading'));
        }
        $adminDocumentTitle = ($adminPageTitle !== '' && $adminPageTitle !== '管理画面')
            ? $adminPageTitle.' | 管理画面 - Sun ＆ Me'
            : '管理画面 - Sun ＆ Me';
        $adminFaviconVersion = '2';
    @endphp
    <title>{{ $adminDocumentTitle }}</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/admin-favicon-32.png') }}?v={{ $adminFaviconVersion }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/admin-favicon-192.png') }}?v={{ $adminFaviconVersion }}">
    <link rel="icon" type="image/png" href="{{ asset('images/admin-favicon.png') }}?v={{ $adminFaviconVersion }}">
    <link rel="apple-touch-icon" href="{{ asset('images/admin-favicon-180.png') }}?v={{ $adminFaviconVersion }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;600&family=Noto+Serif+JP:wght@500;600&display=swap" rel="stylesheet">
    <script>
        (function () {
            try {
                if (localStorage.getItem('adminDesktopSidebarCollapsed') === '1') {
                    document.documentElement.classList.add('admin-desktop-sidebar-pref-collapsed');
                }
            } catch (e) {
                // Ignore storage access failures.
            }
        })();
    </script>
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['"Noto Sans JP"', 'Hiragino Sans', 'Yu Gothic', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                            serif: ['"Noto Serif JP"', 'Hiragino Mincho ProN', 'ui-serif', 'Georgia', 'serif'],
                        },
                        colors: {
                            'salon-bg': '#FAF7F1',
                            'salon-text': '#3A332E',
                            'salon-accent': '#7C8A6A',
                            'salon-button': '#5F6F52',
                            'salon-line': '#E3DDD2',
                            'salon-muted': '#6B635C',
                            'admin-bg': '#F7F5F0',
                            'admin-sidebar': '#F6F2EA',
                            'admin-card': '#FFFFFF',
                            'admin-text': '#3D3833',
                            'admin-muted': '#736D65',
                            'admin-accent': '#697A55',
                            'admin-accent-dark': '#556344',
                            'admin-selected': '#E5EADD',
                            'admin-hover': '#EEF1E8',
                            'admin-border': '#E5E0D7',
                            'admin-icon': '#8A847A',
                            'admin-danger': '#C45C5C',
                            'admin-danger-dark': '#A84848',
                        }
                    }
                }
            }
        </script>
        <style type="text/tailwindcss">
            @layer components {
                .admin-card { @apply rounded-xl border border-admin-border/40 bg-admin-card p-6 shadow-[0_2px_10px_rgba(0,0,0,0.04)]; }
                .admin-modal-panel { @apply shadow-[0_2px_10px_rgba(0,0,0,0.04)]; }
                .admin-input { @apply w-full rounded-lg border border-admin-border bg-admin-card px-3 py-2.5 text-sm text-admin-text placeholder:text-admin-muted/70 focus:border-admin-accent focus:outline-none focus:ring-1 focus:ring-admin-accent/40; }
                .admin-label { @apply mb-1.5 block text-sm font-medium text-admin-text; }
                .admin-btn { @apply inline-flex items-center justify-center rounded-lg bg-admin-accent px-4 py-2 text-sm font-medium text-white transition hover:bg-admin-accent-dark focus:outline-none focus:ring-2 focus:ring-admin-accent/40 focus:ring-offset-1 focus:ring-offset-admin-bg disabled:cursor-not-allowed disabled:opacity-60; }
                .admin-btn-secondary { @apply inline-flex items-center justify-center rounded-lg border border-admin-border bg-admin-card px-4 py-2 text-sm font-medium text-admin-text transition hover:bg-admin-hover focus:outline-none focus:ring-2 focus:ring-admin-accent/30 focus:ring-offset-1 focus:ring-offset-admin-bg; }
                .admin-btn-danger { @apply inline-flex items-center justify-center rounded-lg bg-admin-danger px-4 py-2 text-sm font-medium text-white transition hover:bg-admin-danger-dark focus:outline-none focus:ring-2 focus:ring-admin-danger/40 focus:ring-offset-1; }
                .admin-action-group { @apply flex flex-wrap items-center justify-end gap-1.5; }
                .btn-admin-create { @apply inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-admin-accent px-4 text-sm font-medium text-white shadow-[0_2px_10px_rgba(0,0,0,0.04)] transition duration-150 hover:-translate-y-0.5 hover:bg-admin-accent-dark hover:shadow-[0_2px_10px_rgba(0,0,0,0.06)] focus:outline-none focus:ring-2 focus:ring-admin-accent/40 focus:ring-offset-1 focus:ring-offset-admin-bg disabled:pointer-events-none disabled:opacity-50; }
                .admin-icon-btn { display: inline-flex; height: 2rem; width: 2rem; flex-shrink: 0; align-items: center; justify-content: center; border-radius: 9999px; border: 1px solid #E5E0D7; background-color: #fff; font-size: 1rem; line-height: 1; color: #A89D8C; box-shadow: none; transition: color 0.15s ease, border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease; }
                .admin-icon-btn:hover { background-color: #F7F5F0; border-color: #D8D2C7; color: #736D65; box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); }
                .admin-icon-btn:active { background-color: #EEF1E8; color: #697A55; }
                .admin-icon-btn:focus, .admin-icon-btn:focus-visible { outline: none; background-color: #F7F5F0; border-color: #D8D2C7; color: #736D65; box-shadow: 0 0 0 3px rgba(105, 122, 85, 0.15); }
                .admin-icon-btn-edit { border-color: #D1D5C9; color: #6B7355; }
                .admin-icon-btn-edit:hover { background-color: #F5F6F1; border-color: #C4C9B8; color: #5A6248; box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); }
                .admin-icon-btn-edit:active { background-color: #EEF1E8; border-color: #B8BFA8; color: #4F5640; }
                .admin-icon-btn-edit:focus, .admin-icon-btn-edit:focus-visible { outline: none; background-color: #F5F6F1; border-color: #C4C9B8; color: #5A6248; box-shadow: 0 0 0 3px rgba(107, 115, 85, 0.15); }
                .admin-icon-btn-edit svg { height: 0.875rem; width: 0.875rem; flex-shrink: 0; }
                .admin-choice-choices, .news-weekday-choices { display: flex; flex-wrap: wrap; gap: 0.5rem; }
                .admin-choice-option, .news-weekday-option { position: relative; display: block; margin: 0; cursor: pointer; flex: 1 1 calc(14.28% - 0.45rem); min-width: 2.5rem; }
                .admin-choice-choices--auto .admin-choice-option, .admin-choice-choices--auto .news-weekday-option, .news-weekday-choices--auto .admin-choice-option, .news-weekday-choices--auto .news-weekday-option { flex: 0 0 auto; min-width: 0; flex-shrink: 0; }
                .admin-choice-choices--auto, .news-weekday-choices--auto { display: flex; flex-wrap: nowrap; width: 100%; max-width: 100%; gap: 0.3rem; overflow: visible; }
                .admin-choice-choices--auto .admin-choice-face, .admin-choice-choices--auto .news-weekday-face, .news-weekday-choices--auto .admin-choice-face, .news-weekday-choices--auto .news-weekday-face { min-height: 2.25rem; padding-left: 0.45rem; padding-right: 0.45rem; font-size: 0.8125rem; white-space: nowrap; }
                @media (max-width: 767px) {
                    .admin-choice-choices--auto, .news-weekday-choices--auto { flex-wrap: wrap; gap: 0.375rem; }
                    .admin-choice-choices--auto .admin-choice-option, .admin-choice-choices--auto .news-weekday-option, .news-weekday-choices--auto .admin-choice-option, .news-weekday-choices--auto .news-weekday-option { flex: 1 1 calc(33.333% - 0.375rem); min-width: 5.25rem; max-width: calc(50% - 0.2rem); }
                    .admin-choice-choices--auto .admin-choice-face, .admin-choice-choices--auto .news-weekday-face, .news-weekday-choices--auto .admin-choice-face, .news-weekday-choices--auto .news-weekday-face { min-height: 2.5rem; padding-left: 0.5rem; padding-right: 0.5rem; font-size: 0.875rem; }
                }
                [data-menu-category-checkboxes] { max-width: 100%; min-width: 0; }
                .admin-choice-input, .news-weekday-input { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; appearance: none; }
                .admin-choice-face, .news-weekday-face { display: flex; align-items: center; justify-content: center; min-height: 2.5rem; border: 1px solid #E5E0D7; border-radius: 0.5rem; background-color: #ffffff; color: #3D3833; font-size: 0.875rem; transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease; }
                .admin-choice-option:hover .admin-choice-face, .admin-choice-option:hover .news-weekday-face, .news-weekday-option:hover .admin-choice-face, .news-weekday-option:hover .news-weekday-face { background-color: #F7F5F0; }
                .admin-choice-input:checked + .admin-choice-face, .admin-choice-input:checked + .news-weekday-face, .news-weekday-input:checked + .admin-choice-face, .news-weekday-input:checked + .news-weekday-face { background-color: #E5EADD; border-color: #697A55; color: #556344; font-weight: 600; }
                .admin-choice-input:focus-visible + .admin-choice-face, .admin-choice-input:focus-visible + .news-weekday-face, .news-weekday-input:focus-visible + .admin-choice-face, .news-weekday-input:focus-visible + .news-weekday-face { box-shadow: 0 0 0 2px rgba(105, 122, 85, 0.35); }
                .btn-admin-delete { @apply inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-admin-danger/70 bg-admin-card px-3 text-sm font-medium text-admin-danger shadow-[0_2px_10px_rgba(0,0,0,0.04)] transition duration-150 hover:-translate-y-0.5 hover:border-admin-danger-dark hover:bg-admin-danger hover:text-white hover:shadow-[0_2px_10px_rgba(0,0,0,0.06)] focus:outline-none focus:ring-2 focus:ring-admin-danger/40 focus:ring-offset-1 focus:ring-offset-admin-bg; }
                .admin-nav-link { @apply flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm leading-snug text-admin-text/90 transition-colors duration-150 hover:bg-admin-hover hover:text-admin-text; }
                .admin-nav-link svg { @apply text-admin-icon; }
                .admin-nav-link:hover svg { @apply text-admin-accent; }
                .admin-nav-link-active { @apply rounded-xl bg-admin-selected px-4 font-medium text-admin-accent-dark hover:bg-admin-selected hover:text-admin-accent-dark; }
                .admin-nav-link-active svg { @apply text-admin-accent-dark; }
                .admin-nav-parent { @apply flex cursor-pointer list-none items-center gap-2.5 rounded-lg px-3 py-2 text-sm leading-snug text-admin-text/90 transition-colors duration-150 hover:bg-admin-hover hover:text-admin-text; list-style: none; }
                .admin-nav-parent::-webkit-details-marker { display: none; }
                .admin-nav-parent::marker { display: none; content: ''; }
                .admin-nav-parent svg { @apply text-admin-icon; }
                .admin-nav-parent:hover svg { @apply text-admin-accent; }
                .admin-nav-chevron { @apply ml-auto shrink-0 transition-transform duration-150; }
                .admin-nav-group[open] > .admin-nav-parent .admin-nav-chevron { transform: rotate(180deg); }
                .admin-nav-link-child {
                    margin-left: 0;
                    padding-left: 2.5rem;
                    gap: 0.5rem;
                    padding-top: 0.5rem;
                    padding-bottom: 0.5rem;
                    line-height: 1.375;
                }
                .admin-mobile-sidebar-panel {
                    width: min(18rem, 85vw);
                    max-width: 18rem;
                    border-right: 1px solid #E5E0D7;
                    box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
                }
                @media (max-width: 639px) {
                    .admin-mobile-sidebar-panel {
                        width: 100vw !important;
                        max-width: none !important;
                        min-width: 100vw;
                        border-right: 0;
                        box-shadow: none;
                    }
                    .admin-mobile-sidebar-overlay {
                        display: none;
                    }
                }
                .admin-desktop-sidebar-open {
                    display: none;
                }
                @media (min-width: 768px) {
                    .admin-desktop-sidebar {
                        transition:
                            width 0.2s ease,
                            min-width 0.2s ease,
                            opacity 0.15s ease,
                            border-color 0.15s ease;
                    }
                    .admin-desktop-sidebar.is-collapsed,
                    html.admin-desktop-sidebar-pref-collapsed .admin-desktop-sidebar {
                        width: 0 !important;
                        min-width: 0 !important;
                        opacity: 0;
                        overflow: hidden;
                        border-right-color: transparent;
                        pointer-events: none;
                    }
                    .admin-layout.sidebar-collapsed .admin-desktop-sidebar-open,
                    html.admin-desktop-sidebar-pref-collapsed .admin-desktop-sidebar-open {
                        display: inline-flex;
                    }
                }
                .admin-sidebar-scroll {
                    scrollbar-width: thin;
                    scrollbar-color: #C8C0B2 #F1ECE3;
                    padding-right: 0.25rem;
                }
                .admin-sidebar-scroll::-webkit-scrollbar {
                    width: 8px;
                }
                .admin-sidebar-scroll::-webkit-scrollbar-track {
                    background: #F1ECE3;
                    border-radius: 9999px;
                }
                .admin-sidebar-scroll::-webkit-scrollbar-thumb {
                    background: #C8C0B2;
                    border: 2px solid #F1ECE3;
                    border-radius: 9999px;
                }
                .admin-sidebar-scroll::-webkit-scrollbar-thumb:hover {
                    background: #AFA692;
                }
                .admin-page,
                .admin-page-scroll,
                html:has(body.admin-page) {
                    scrollbar-width: thin;
                    scrollbar-color: #C8C0B2 #F1ECE3;
                }
                .admin-page::-webkit-scrollbar,
                .admin-page-scroll::-webkit-scrollbar,
                html:has(body.admin-page)::-webkit-scrollbar {
                    width: 10px;
                    height: 10px;
                }
                .admin-page::-webkit-scrollbar-track,
                .admin-page-scroll::-webkit-scrollbar-track,
                html:has(body.admin-page)::-webkit-scrollbar-track {
                    background: #F1ECE3;
                }
                .admin-page::-webkit-scrollbar-thumb,
                .admin-page-scroll::-webkit-scrollbar-thumb,
                html:has(body.admin-page)::-webkit-scrollbar-thumb {
                    background: #C8C0B2;
                    border: 2px solid #F1ECE3;
                    border-radius: 9999px;
                }
                .admin-page::-webkit-scrollbar-thumb:hover,
                .admin-page-scroll::-webkit-scrollbar-thumb:hover,
                html:has(body.admin-page)::-webkit-scrollbar-thumb:hover {
                    background: #AFA692;
                }
                .admin-table-wrap { @apply overflow-hidden rounded-xl border border-admin-border/40 bg-admin-card shadow-[0_2px_10px_rgba(0,0,0,0.04)]; }
                .admin-table { @apply min-w-full divide-y divide-admin-border/40 text-sm text-admin-text; }
                .admin-table thead { @apply bg-admin-sidebar/80; }
                .admin-table th { @apply px-5 py-3.5 text-left text-xs font-medium tracking-wide text-admin-muted; }
                .admin-table tbody { @apply divide-y divide-admin-border/30; }
                .admin-table tbody tr { @apply transition-colors duration-100 hover:bg-admin-hover/60; }
                .admin-table td { @apply px-5 py-3.5 align-middle; }
                .admin-empty-state { @apply flex flex-col items-center justify-center px-4 py-12 text-center; }
                .admin-empty-state-icon { @apply mb-5 inline-flex h-24 w-24 items-center justify-center rounded-full bg-[#EEF1E8] text-[#B8B09F]; }
                .admin-empty-state-title { @apply text-base font-medium text-admin-text; }
                .admin-empty-state-desc { @apply mt-2 text-sm text-admin-muted; }
                .admin-empty-state-actions { @apply mt-6; }
                .admin-page-title { @apply font-serif text-xl font-medium tracking-wide text-admin-text md:text-2xl; }
                .admin-page-title-icon { @apply inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#E8EDE3] text-admin-accent; }
                .admin-brand-title { @apply font-serif text-lg font-medium tracking-wide text-admin-text; }
            }
        </style>
        {{-- Fallback when Vite build is missing: keep published checkbox styled --}}
        <style>
            .menu-published-control { display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; min-height: 2.5rem; }
            .menu-published-checkbox {
                appearance: none; -webkit-appearance: none; -moz-appearance: none;
                position: relative; width: 18px; height: 18px; flex-shrink: 0; margin: 0;
                border-radius: 4px; border: 1px solid #E5E0D7; background-color: #fff;
                cursor: pointer; accent-color: transparent;
            }
            .menu-published-checkbox::after {
                content: ''; position: absolute; inset: 0; opacity: 0;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' fill='none'%3E%3Cpath d='M2.5 6.2L4.8 8.5L9.5 3.5' stroke='%23ffffff' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
                background-position: center; background-repeat: no-repeat; background-size: 12px 12px;
            }
            .menu-published-checkbox:hover { background-color: #EEF1E8; border-color: #D8D2C7; }
            .menu-published-checkbox:focus,
            .menu-published-checkbox:focus-visible { outline: none; border-color: #697A55; box-shadow: 0 0 0 3px rgba(105, 122, 85, 0.25); }
            .menu-published-checkbox:checked { background-color: #697A55; border-color: #697A55; }
            .menu-published-checkbox:checked::after { opacity: 1; }
            .menu-published-checkbox:checked:hover { background-color: #556344; border-color: #556344; }
            .menu-published-label { display: inline-flex; align-items: center; gap: 0.375rem; font-size: 0.75rem; font-weight: 500; line-height: 1; white-space: nowrap; }
            .menu-published-dot { display: inline-block; width: 6px; height: 6px; flex-shrink: 0; border-radius: 9999px; }
            .menu-published-label.is-published { color: #5F7351; }
            .menu-published-label.is-published .menu-published-dot { background-color: #8FA57B; }
            .menu-published-label.is-unpublished { color: #77736D; }
            .menu-published-label.is-unpublished .menu-published-dot { background-color: #B8B5AF; }
            .admin-radio-group { display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; }
            .admin-radio-control { display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; min-height: 2.5rem; }
            .admin-radio {
                appearance: none; -webkit-appearance: none; -moz-appearance: none;
                position: relative; width: 18px; height: 18px; flex-shrink: 0; margin: 0;
                border-radius: 9999px; border: 1px solid #E5E0D7; background-color: #fff;
                cursor: pointer; accent-color: transparent;
            }
            .admin-radio::after {
                content: ''; position: absolute; top: 50%; left: 50%; width: 8px; height: 8px;
                border-radius: 9999px; background-color: #697A55; opacity: 0; transform: translate(-50%, -50%);
            }
            .admin-radio:hover { background-color: #EEF1E8; border-color: #C5D0B8; }
            .admin-radio:focus,
            .admin-radio:focus-visible { outline: none; border-color: #697A55; box-shadow: 0 0 0 3px rgba(105, 122, 85, 0.25); }
            .admin-radio:checked { border-color: #697A55; background-color: #fff; }
            .admin-radio:checked::after { opacity: 1; }
            .admin-radio:checked:hover { background-color: #EEF1E8; border-color: #556344; }
            .admin-radio:checked:hover::after { background-color: #556344; }
            .admin-segmented { display: inline-flex; width: 100%; max-width: 100%; overflow: hidden; border: 1px solid #E5E0D7; border-radius: 0.5rem; background-color: #fff; }
            .admin-segmented-option { position: relative; display: block; flex: 1 1 0; min-width: 0; cursor: pointer; margin: 0; }
            .admin-segmented-option + .admin-segmented-option { border-left: 1px solid #E5E0D7; }
            .admin-segmented-input {
                position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden;
                clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
                appearance: none; -webkit-appearance: none; -moz-appearance: none;
            }
            .admin-segmented-face {
                display: flex; align-items: center; justify-content: center; gap: 0.375rem;
                min-height: 2.5rem; padding: 0.5rem 0.625rem; background-color: #fff; color: #3D3833;
                font-size: 0.875rem; line-height: 1.25;
            }
            .admin-segmented-icon { width: 0.875rem; height: 0.875rem; flex-shrink: 0; opacity: 0.72; }
            .admin-segmented-text { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .admin-segmented-option:hover .admin-segmented-face { background-color: #F7F5F0; }
            .admin-segmented-input:checked + .admin-segmented-face { background-color: #E5EADD; color: #556344; font-weight: 500; }
            .admin-segmented-input:checked + .admin-segmented-face .admin-segmented-icon { opacity: 1; }
            .admin-segmented-input:checked + .admin-segmented-face::after {
                content: ''; width: 0.7rem; height: 0.7rem; flex-shrink: 0;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' fill='none'%3E%3Cpath d='M2.5 6.2L4.8 8.5L9.5 3.5' stroke='%23556344' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
                background-position: center; background-repeat: no-repeat; background-size: contain;
            }
            .admin-segmented-input:checked + .admin-segmented-face:hover,
            .admin-segmented-option:hover .admin-segmented-input:checked + .admin-segmented-face { background-color: #DDE5D2; }
            .admin-segmented-input:focus-visible + .admin-segmented-face { box-shadow: inset 0 0 0 2px rgba(105, 122, 85, 0.35); }
            .admin-segmented--compact .admin-segmented-face {
                min-height: 2.25rem; padding: 0.25rem 0.375rem; gap: 0.25rem; font-size: 0.8125rem;
            }
            .admin-segmented--compact .admin-segmented-icon { width: 0.875rem; height: 0.875rem; }
            .admin-segmented--compact .admin-segmented-input:checked + .admin-segmented-face::after {
                width: 0.625rem; height: 0.625rem;
            }
            .admin-switch {
                display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; user-select: none; min-height: 2.5rem;
            }
            .admin-switch-input {
                position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden;
                clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
            }
            .admin-switch-track {
                position: relative; width: 2.75rem; height: 1.5rem; flex-shrink: 0; border-radius: 9999px;
                background-color: #D8D2C7; transition: background-color 0.15s ease, box-shadow 0.15s ease;
            }
            .admin-switch-thumb {
                position: absolute; top: 2px; left: 2px; width: 1.25rem; height: 1.25rem; border-radius: 9999px;
                background-color: #fff; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.12); transition: transform 0.15s ease;
            }
            .admin-switch-input:checked + .admin-switch-track { background-color: #697A55; }
            .admin-switch-input:checked + .admin-switch-track .admin-switch-thumb { transform: translateX(1.25rem); }
            .admin-switch-input:focus-visible + .admin-switch-track { box-shadow: 0 0 0 3px rgba(105, 122, 85, 0.25); }
            .admin-switch-text { min-width: 2rem; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.02em; color: #77736D; }
            .admin-switch-input:checked ~ .admin-switch-text { color: #5F7351; }
            .admin-switch--compact { min-height: 0; align-items: center; gap: 0.5rem; }
            .admin-switch--compact .admin-switch-track { width: 2.75rem; height: 1.375rem; }
            .admin-switch--compact .admin-switch-thumb { top: 2px; left: 2px; width: 1.125rem; height: 1.125rem; }
            .admin-switch--compact .admin-switch-input:checked + .admin-switch-track .admin-switch-thumb { transform: translateX(1.375rem); }
            .admin-switch--compact .admin-switch-text {
                min-width: 3em; font-size: 0.8125rem; font-weight: 500; letter-spacing: 0; line-height: 1.25; white-space: nowrap;
            }
            .admin-required-badge {
                display: inline-flex; align-items: center; justify-content: center; margin-left: 0.375rem;
                padding: 0.1rem 0.45rem; border-radius: 9999px; border: 1px solid #E8D9C8;
                background-color: #F5EDE3; color: #9A7B5A; font-size: 11px; font-weight: 500;
                line-height: 1.3; letter-spacing: 0.02em; vertical-align: middle;
            }
            .banner-card-label { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; }
            .banner-location-chips { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.25rem; }
            .banner-location-chip { position: relative; display: inline-flex; margin: 0; cursor: pointer; }
            .banner-location-chip-input {
                position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden;
                clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
                appearance: none; -webkit-appearance: none; -moz-appearance: none;
            }
            .banner-location-chip-face {
                display: inline-flex; align-items: center; justify-content: center;
                padding: 0.35rem 0.75rem; border-radius: 9999px; border: 1px solid #E5E0D7;
                background-color: #fff; color: #3D3833; font-size: 0.8125rem; line-height: 1.25; white-space: nowrap;
            }
            .banner-location-chip:hover .banner-location-chip-face { background-color: #F7F5F0; border-color: #D8D2C7; }
            .banner-location-chip-input:checked + .banner-location-chip-face {
                background-color: #E5EADD; border-color: #C5D0B8; color: #556344; font-weight: 500;
            }
            .banner-location-chip:hover .banner-location-chip-input:checked + .banner-location-chip-face { background-color: #DDE5D2; }
            .banner-location-chip-input:focus-visible + .banner-location-chip-face { box-shadow: 0 0 0 3px rgba(105, 122, 85, 0.25); }
            .banner-dropzone {
                position: relative; cursor: pointer; overflow: hidden; border-radius: 0.5rem;
                transition: background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
            }
            .banner-dropzone.is-empty {
                display: flex; min-height: 7.5rem; flex-direction: column; align-items: center; justify-content: center;
                border: 2px dashed #D1D5DB; background-color: #fff; padding: 1rem; text-align: center;
            }
            .banner-dropzone.is-empty:hover { border-color: #9CA3AF; background-color: #F9FAFB; }
            .banner-dropzone-main {
                display: inline-flex; max-width: 100%; align-items: center; justify-content: center; gap: 0.5625rem;
            }
            .banner-dropzone-icon {
                width: 1.125rem; height: 1.125rem; flex-shrink: 0; color: #A89D8C;
                transition: color 0.15s ease, transform 0.15s ease, opacity 0.15s ease;
            }
            .banner-dropzone-text { margin: 0; line-height: 1.35; text-wrap: balance; }
            .banner-dropzone-drag-message {
                display: none; position: absolute; inset: 0; z-index: 2; align-items: center; justify-content: center;
                padding: 0.75rem; background-color: rgba(238, 241, 232, 0.92); color: #556344;
                font-size: 0.875rem; font-weight: 500; text-align: center; pointer-events: none;
            }
            .banner-dropzone.is-drag-active {
                border: 2px dashed #697A55; background-color: #EEF1E8;
                box-shadow: inset 0 0 0 1px rgba(105, 122, 85, 0.12);
            }
            .banner-dropzone.is-drag-active .banner-dropzone-icon { color: #556344; transform: scale(1.12); opacity: 1; }
            .banner-dropzone.is-drag-active .banner-dropzone-drag-message { display: flex; }
            .banner-dropzone.is-drag-active [data-banner-preview],
            .banner-dropzone.is-drag-active [data-gallery-preview],
            .banner-dropzone.is-drag-active [data-logo-preview],
            .banner-dropzone.is-drag-active [data-og-preview],
            .banner-dropzone.is-drag-active [data-staff-preview] { opacity: 0.35; }
            .banner-dropzone.is-drag-active.is-empty .banner-dropzone-text,
            .banner-dropzone.is-drag-active.is-empty .banner-dropzone-hint { opacity: 0.35; }
            .admin-service-heading { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
            .admin-service-icon { width: 18px; height: 18px; flex-shrink: 0; color: #697A55; }
            .admin-service-name { font-size: 15px; font-weight: 500; color: #3D3833; }
            .admin-service-help { margin-top: 0.375rem; font-size: 0.75rem; line-height: 1.4; color: #8A847A; }
        </style>
    @endif
</head>
<body class="admin-page h-screen overflow-hidden bg-admin-bg font-sans text-admin-text antialiased">
    <div class="admin-layout flex h-screen overflow-hidden" data-admin-layout>
        <aside
            id="admin-desktop-sidebar"
            class="admin-desktop-sidebar relative hidden h-screen w-64 shrink-0 overflow-hidden border-r border-[#E5E0D7] bg-[#F6F2EA] text-admin-text md:flex md:flex-col"
            data-admin-desktop-sidebar
        >
            <div class="relative z-10 flex shrink-0 items-center px-7 py-7">
                <a
                    href="{{ route('admin.dashboard') }}"
                    class="flex min-w-0 flex-1 items-center gap-3.5 hover:opacity-80"
                >
                    <img
                        src="{{ asset('images/admin-brand-logo.png') }}"
                        alt=""
                        width="40"
                        height="40"
                        class="h-12 w-12 shrink-0 object-contain"
                        decoding="async"
                        aria-hidden="true"
                    >
                    <div class="min-w-0 pt-0.5">
                        <div class="whitespace-nowrap font-serif text-xl leading-tight tracking-wide text-[#3D3833]">
                            Sun ＆ Me
                        </div>
                        <div class="mt-1 whitespace-nowrap text-xs tracking-wide text-[#736D65]">
                            管理画面
                        </div>
                    </div>
                </a>

                <button
                    type="button"
                    class="absolute right-3 top-1/2 hidden h-9 w-9 -translate-y-1/2 items-center justify-center rounded-lg text-admin-muted transition hover:bg-admin-hover hover:text-admin-text focus:outline-none focus:ring-2 focus:ring-admin-accent/40 md:inline-flex"
                    aria-label="サイドメニューを閉じる"
                    aria-controls="admin-desktop-sidebar"
                    aria-expanded="true"
                    data-admin-desktop-sidebar-close
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                        <path d="M9 4v16"></path>
                        <path d="m14 9-3 3 3 3"></path>
                    </svg>
                </button>
            </div>
            <x-admin.sidebar-nav />
            <div
                class="pointer-events-none absolute inset-x-0 bottom-0 z-0 h-[330px] overflow-hidden text-[#B8AE98]"
                aria-hidden="true"
            >
                <!-- 背景の有機的なベージュ形状 -->
                <svg
                    viewBox="0 0 280 330"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                    class="absolute inset-0 h-full w-full"
                    preserveAspectRatio="none"
                >
                    <path
                        d="M0 330V245
                           C38 215 74 212 108 225
                           C147 240 174 227 205 242
                           C239 259 261 292 280 330H0Z"
                        fill="#C4BCA9"
                        opacity=".07"
                    />
                    <path
                        d="M0 330V276
                           C45 249 92 253 127 270
                           C167 290 219 279 280 330H0Z"
                        fill="#D9D2C5"
                        opacity=".1"
                    />
                </svg>

                <!-- 枝葉本体 -->
                <svg
                    viewBox="0 0 280 330"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                    class="absolute bottom-0 left-0 h-[315px] w-[245px] opacity-28"
                >
                    <!-- 主枝 -->
                    <path
                        d="M18 314
                           C53 283 74 249 91 218
                           C110 183 124 150 143 112
                           C158 82 171 53 190 22"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                    />

                    <!-- 左下枝 -->
                    <path
                        d="M57 273
                           C41 261 30 249 23 234"
                        stroke="currentColor"
                        stroke-width="1.35"
                        stroke-linecap="round"
                    />

                    <!-- 右下枝 -->
                    <path
                        d="M73 251
                           C92 247 108 239 121 226"
                        stroke="currentColor"
                        stroke-width="1.35"
                        stroke-linecap="round"
                    />

                    <!-- 左中枝 -->
                    <path
                        d="M101 199
                           C81 191 67 179 57 163"
                        stroke="currentColor"
                        stroke-width="1.35"
                        stroke-linecap="round"
                    />

                    <!-- 右中枝 -->
                    <path
                        d="M119 166
                           C139 159 154 149 166 134"
                        stroke="currentColor"
                        stroke-width="1.35"
                        stroke-linecap="round"
                    />

                    <!-- 上部左枝 -->
                    <path
                        d="M146 106
                           C130 97 118 85 111 71"
                        stroke="currentColor"
                        stroke-width="1.35"
                        stroke-linecap="round"
                    />

                    <!-- 上部右枝 -->
                    <path
                        d="M164 68
                           C181 61 194 50 203 37"
                        stroke="currentColor"
                        stroke-width="1.35"
                        stroke-linecap="round"
                    />

                    <!-- 葉1 -->
                    <path
                        d="M23 234
                           C11 232 5 223 4 213
                           C15 216 22 223 23 234Z"
                        stroke="currentColor"
                        stroke-width="1.45"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M22 233L6 215"
                        stroke="currentColor"
                        stroke-width=".9"
                        stroke-linecap="round"
                        opacity=".7"
                    />

                    <!-- 葉2 -->
                    <path
                        d="M48 266
                           C34 266 25 258 21 246
                           C34 248 44 255 48 266Z"
                        stroke="currentColor"
                        stroke-width="1.45"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M47 265L23 248"
                        stroke="currentColor"
                        stroke-width=".9"
                        stroke-linecap="round"
                        opacity=".7"
                    />

                    <!-- 葉3 -->
                    <path
                        d="M77 248
                           C83 235 94 229 106 230
                           C100 241 90 247 77 248Z"
                        stroke="currentColor"
                        stroke-width="1.45"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M79 247L104 231"
                        stroke="currentColor"
                        stroke-width=".9"
                        stroke-linecap="round"
                        opacity=".7"
                    />

                    <!-- 葉4 -->
                    <path
                        d="M121 226
                           C129 214 140 210 151 212
                           C144 222 134 227 121 226Z"
                        stroke="currentColor"
                        stroke-width="1.45"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M123 225L149 213"
                        stroke="currentColor"
                        stroke-width=".9"
                        stroke-linecap="round"
                        opacity=".7"
                    />

                    <!-- 葉5 -->
                    <path
                        d="M90 215
                           C76 211 68 201 67 190
                           C79 194 87 203 90 215Z"
                        stroke="currentColor"
                        stroke-width="1.45"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M89 213L69 192"
                        stroke="currentColor"
                        stroke-width=".9"
                        stroke-linecap="round"
                        opacity=".7"
                    />

                    <!-- 葉6 -->
                    <path
                        d="M57 163
                           C44 160 36 151 34 140
                           C46 143 54 152 57 163Z"
                        stroke="currentColor"
                        stroke-width="1.45"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M56 161L36 142"
                        stroke="currentColor"
                        stroke-width=".9"
                        stroke-linecap="round"
                        opacity=".7"
                    />

                    <!-- 葉7 -->
                    <path
                        d="M106 186
                           C112 173 122 167 134 168
                           C128 179 118 185 106 186Z"
                        stroke="currentColor"
                        stroke-width="1.45"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M108 184L132 169"
                        stroke="currentColor"
                        stroke-width=".9"
                        stroke-linecap="round"
                        opacity=".7"
                    />

                    <!-- 葉8 -->
                    <path
                        d="M166 134
                           C174 122 185 118 197 120
                           C190 130 179 135 166 134Z"
                        stroke="currentColor"
                        stroke-width="1.45"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M168 133L195 121"
                        stroke="currentColor"
                        stroke-width=".9"
                        stroke-linecap="round"
                        opacity=".7"
                    />

                    <!-- 葉9 -->
                    <path
                        d="M128 142
                           C116 137 109 127 109 116
                           C121 120 128 130 128 142Z"
                        stroke="currentColor"
                        stroke-width="1.45"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M127 140L111 118"
                        stroke="currentColor"
                        stroke-width=".9"
                        stroke-linecap="round"
                        opacity=".7"
                    />

                    <!-- 葉10 -->
                    <path
                        d="M111 71
                           C100 66 94 56 94 46
                           C105 50 111 60 111 71Z"
                        stroke="currentColor"
                        stroke-width="1.45"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M110 69L96 48"
                        stroke="currentColor"
                        stroke-width=".9"
                        stroke-linecap="round"
                        opacity=".7"
                    />

                    <!-- 葉11 -->
                    <path
                        d="M155 86
                           C162 74 172 69 183 70
                           C177 81 167 86 155 86Z"
                        stroke="currentColor"
                        stroke-width="1.45"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M157 84L181 71"
                        stroke="currentColor"
                        stroke-width=".9"
                        stroke-linecap="round"
                        opacity=".7"
                    />

                    <!-- 葉12 -->
                    <path
                        d="M190 22
                           C193 10 202 3 213 2
                           C209 13 201 20 190 22Z"
                        stroke="currentColor"
                        stroke-width="1.45"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M192 20L211 4"
                        stroke="currentColor"
                        stroke-width=".9"
                        stroke-linecap="round"
                        opacity=".7"
                    />

                    <!-- 葉13 -->
                    <path
                        d="M203 37
                           C214 31 224 31 233 36
                           C224 43 214 43 203 37Z"
                        stroke="currentColor"
                        stroke-width="1.45"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M205 37L231 36"
                        stroke="currentColor"
                        stroke-width=".9"
                        stroke-linecap="round"
                        opacity=".7"
                    />
                </svg>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
            <header class="z-20 flex shrink-0 items-center justify-between gap-3 border-b border-admin-border/60 bg-admin-card/95 px-4 py-4 backdrop-blur-sm md:px-8">
                @php
                    $pageIcon = \App\Support\AdminNav::currentPageIcon(
                        trim($__env->yieldContent('icon'))
                    );
                @endphp
                <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-admin-text transition hover:bg-admin-hover focus:outline-none focus:ring-2 focus:ring-admin-accent/40 md:hidden"
                        aria-label="メニューを開く"
                        aria-controls="admin-mobile-sidebar"
                        aria-expanded="false"
                        data-admin-mobile-menu-open
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            aria-hidden="true"
                        >
                            <path d="M4 7h16"></path>
                            <path d="M4 12h16"></path>
                            <path d="M4 17h16"></path>
                        </svg>
                    </button>

                    <button
                        type="button"
                        class="admin-desktop-sidebar-open h-10 w-10 shrink-0 items-center justify-center rounded-lg text-admin-text transition hover:bg-admin-hover focus:outline-none focus:ring-2 focus:ring-admin-accent/40"
                        aria-label="サイドメニューを開く"
                        aria-controls="admin-desktop-sidebar"
                        aria-expanded="true"
                        data-admin-desktop-sidebar-open
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            aria-hidden="true"
                        >
                            <path d="M4 7h16"></path>
                            <path d="M4 12h16"></path>
                            <path d="M4 17h16"></path>
                        </svg>
                    </button>

                    <h1 class="admin-page-title flex min-w-0 items-center gap-3">
                        <span class="admin-page-title-icon inline-flex" aria-hidden="true">
                            @if ($pageIcon)
                                <x-admin.nav-icon :name="$pageIcon" class="h-[18px] w-[18px]" />
                            @else
                                <x-admin.leaf-icon />
                            @endif
                        </span>
                        <span class="truncate">@yield('heading', '管理画面')</span>
                    </h1>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}" class="shrink-0">
                    @csrf
                    <button type="submit" class="admin-btn-secondary">ログアウト</button>
                </form>
            </header>

            <main class="admin-page-scroll min-h-0 flex-1 overflow-y-auto bg-admin-bg">
                @hasSection('save-bar')
                    <div
                        class="admin-save-bar sticky top-0 z-20 border-b border-admin-border/50 bg-admin-bg px-4 py-3 md:px-8"
                        data-admin-save-bar
                    >
                        @yield('save-bar')
                    </div>
                @endif

                <div class="p-4 md:p-8">
                    @php
                        $toastValidationRoutes = ['admin.news.*', 'admin.home.banners', 'admin.home.banners.update', 'admin.menus.*', 'admin.blog.*', 'admin.galleries.*'];
                        $toastValidationErrors = [];
                        if (request()->routeIs($toastValidationRoutes)) {
                            $errorBag = (isset($errors) && $errors->any())
                                ? $errors
                                : (session()->get('errors') ?: null);
                            if ($errorBag && $errorBag->any()) {
                                $toastValidationErrors = array_values(array_unique($errorBag->all()));
                            }
                        }
                    @endphp
                    @if($errors->any() && ! request()->routeIs($toastValidationRoutes))
                        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                            <ul class="list-disc pl-5">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <div
        id="admin-mobile-sidebar"
        class="fixed inset-0 z-50 hidden md:hidden"
        hidden
        data-admin-mobile-menu
    >
        <button
            type="button"
            class="admin-mobile-sidebar-overlay absolute inset-0 bg-black/35"
            aria-label="メニューを閉じる"
            data-admin-mobile-menu-close
        ></button>

        <aside
            class="admin-mobile-sidebar-panel relative z-10 flex h-full -translate-x-full flex-col overflow-hidden bg-admin-sidebar text-admin-text transition-transform duration-200"
            data-admin-mobile-menu-panel
        >
            <div class="flex min-h-0 flex-1 flex-col">
                <div class="relative z-10 flex shrink-0 items-center gap-3 px-5 py-5">
                    <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 flex-1 items-center gap-3 hover:opacity-80">
                        <img
                            src="{{ asset('images/admin-brand-logo.png') }}"
                            alt=""
                            width="40"
                            height="40"
                            class="h-12 w-12 shrink-0 object-contain"
                            decoding="async"
                            aria-hidden="true"
                        >
                        <div class="min-w-0 pt-0.5">
                            <div class="font-serif text-xl leading-tight tracking-wide text-[#3D3833]">Sun ＆ Me</div>
                            <div class="mt-1 text-xs tracking-wide text-[#736D65]">管理画面</div>
                        </div>
                    </a>
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-admin-border/70 bg-admin-card text-admin-muted transition hover:bg-admin-hover hover:text-admin-text focus:outline-none focus:ring-2 focus:ring-admin-accent/40"
                        aria-label="メニューを閉じる"
                        data-admin-mobile-menu-close
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            aria-hidden="true"
                        >
                            <path d="M6 6l12 12"></path>
                            <path d="M18 6L6 18"></path>
                        </svg>
                    </button>
                </div>

                <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                    <x-admin.sidebar-nav />
                </div>
            </div>
        </aside>
    </div>

    <script>
        (function () {
            const layout = document.querySelector('[data-admin-layout]');
            const sidebar = document.querySelector('[data-admin-desktop-sidebar]');
            const openButton = document.querySelector('[data-admin-desktop-sidebar-open]');
            const closeButton = document.querySelector('[data-admin-desktop-sidebar-close]');
            const storageKey = 'adminDesktopSidebarCollapsed';
            const desktopQuery = window.matchMedia('(min-width: 768px)');

            if (!layout || !sidebar || (!openButton && !closeButton)) {
                return;
            }

            function setCollapsed(collapsed) {
                sidebar.classList.toggle('is-collapsed', collapsed);
                layout.classList.toggle('sidebar-collapsed', collapsed);
                document.documentElement.classList.toggle(
                    'admin-desktop-sidebar-pref-collapsed',
                    collapsed
                );

                if (openButton) {
                    openButton.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                }

                if (closeButton) {
                    closeButton.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                }

                try {
                    localStorage.setItem(storageKey, collapsed ? '1' : '0');
                } catch (e) {
                    // Ignore quota / private-mode failures.
                }
            }

            function applySavedStateIfDesktop() {
                if (!desktopQuery.matches) {
                    layout.classList.remove('sidebar-collapsed');
                    return;
                }

                let savedCollapsed = false;
                try {
                    savedCollapsed = localStorage.getItem(storageKey) === '1';
                } catch (e) {
                    savedCollapsed = false;
                }

                setCollapsed(savedCollapsed);
            }

            if (openButton) {
                openButton.addEventListener('click', function () {
                    if (!desktopQuery.matches) {
                        return;
                    }

                    setCollapsed(false);
                });
            }

            if (closeButton) {
                closeButton.addEventListener('click', function () {
                    if (!desktopQuery.matches) {
                        return;
                    }

                    setCollapsed(true);
                });
            }

            if (typeof desktopQuery.addEventListener === 'function') {
                desktopQuery.addEventListener('change', function (event) {
                    if (event.matches) {
                        applySavedStateIfDesktop();
                    } else {
                        layout.classList.remove('sidebar-collapsed');
                    }
                });
            } else if (typeof desktopQuery.addListener === 'function') {
                desktopQuery.addListener(function (event) {
                    if (event.matches) {
                        applySavedStateIfDesktop();
                    } else {
                        layout.classList.remove('sidebar-collapsed');
                    }
                });
            }

            applySavedStateIfDesktop();
        })();
    </script>

    <script>
        (function () {
            const menu = document.querySelector('[data-admin-mobile-menu]');
            const panel = document.querySelector('[data-admin-mobile-menu-panel]');
            const openButton = document.querySelector('[data-admin-mobile-menu-open]');
            const closeButtons = document.querySelectorAll('[data-admin-mobile-menu-close]');

            if (!menu || !panel || !openButton) {
                return;
            }

            let closeTimer = null;
            let isOpen = false;

            function openMenu() {
                if (closeTimer) {
                    clearTimeout(closeTimer);
                    closeTimer = null;
                }

                isOpen = true;
                menu.hidden = false;
                menu.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                openButton.setAttribute('aria-expanded', 'true');

                requestAnimationFrame(function () {
                    panel.classList.remove('-translate-x-full');
                    panel.classList.add('translate-x-0');
                });
            }

            function closeMenu() {
                if (!isOpen) {
                    return;
                }

                isOpen = false;
                panel.classList.add('-translate-x-full');
                panel.classList.remove('translate-x-0');
                openButton.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';

                closeTimer = setTimeout(function () {
                    menu.classList.add('hidden');
                    menu.hidden = true;
                    openButton.focus();
                    closeTimer = null;
                }, 200);
            }

            openButton.addEventListener('click', openMenu);
            closeButtons.forEach(function (button) {
                button.addEventListener('click', closeMenu);
            });

            menu.addEventListener('click', function (event) {
                const link = event.target.closest('a[href]');
                if (link && menu.contains(link)) {
                    closeMenu();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && isOpen) {
                    closeMenu();
                }
            });

            window.addEventListener('resize', function () {
                if (isOpen && window.matchMedia('(min-width: 768px)').matches) {
                    closeMenu();
                }
            });
        })();
    </script>

    <style>
        #admin-toast-stack {
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            z-index: 70;
            display: flex;
            width: min(100% - 2rem, 26rem);
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
            background: #FFFFFF;
            box-shadow: 0 8px 24px rgba(61, 56, 51, 0.1);
            border: 1px solid #E5E0D7;
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
            background: #FFFFFF;
        }

        .admin-toast-item--error {
            background: #FFFFFF;
        }

        .admin-toast-accent {
            width: 4px;
            align-self: stretch;
            flex-shrink: 0;
        }

        .admin-toast-item--success .admin-toast-accent {
            background: #697A55;
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
            color: #697A55;
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
            color: #3D3833;
            white-space: pre-line;
        }

        .admin-toast-item--error .admin-toast-text {
            color: #7A3E3E;
        }

        .admin-toast-close {
            margin: 0.45rem 0.45rem 0 0;
            border: 0;
            background: transparent;
            border-radius: 0.375rem;
            color: #736D65;
            cursor: pointer;
            padding: 0.35rem;
            line-height: 0;
        }

        .admin-toast-close:hover {
            background: rgba(61, 56, 51, 0.06);
            color: #3D3833;
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
        'validationErrors' => $toastValidationErrors ?? [],
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
                show: function (message, type, options) {
                    const stack = document.getElementById('admin-toast-stack');
                    if (!stack || !message) {
                        return null;
                    }

                    const opts = options || {};
                    const tone = type === 'error' ? 'error' : 'success';
                    const duration = Object.prototype.hasOwnProperty.call(opts, 'duration')
                        ? opts.duration
                        : (tone === 'error' ? 12000 : 3000);

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
                    item.querySelector('.admin-toast-close').addEventListener('click', function (event) {
                        event.stopPropagation();
                        dismissToast(item);
                    });

                    item.addEventListener('click', function (event) {
                        // 将来のリンク／操作ボタン等はトースト全体の閉じる処理を発火させない
                        if (event.target.closest('a, button, input, select, textarea, label, [data-toast-keep-open]')) {
                            return;
                        }
                        dismissToast(item, true);
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

            window.showToast = function (message, type, options) {
                return window.AdminToast.show(message, type, options);
            };

            try {
                const flash = JSON.parse(document.getElementById('admin-flash-data')?.textContent || '{}');
                if (flash.success) {
                    window.showToast(flash.success, 'success');
                }
                if (flash.error) {
                    window.showToast(flash.error, 'error');
                }
                if (Array.isArray(flash.validationErrors) && flash.validationErrors.length) {
                    const lines = ['入力内容を確認してください。'].concat(
                        flash.validationErrors.map(function (error) {
                            return '・' + error;
                        })
                    );
                    window.showToast(lines.join('\n'), 'error', { duration: 12000 });
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
        <div class="admin-modal-panel w-full max-w-md rounded-xl border border-admin-border/50 bg-admin-card p-6" data-admin-confirm-panel>
            <div class="mb-4 flex items-start gap-3">
                <x-admin.leaf-icon-circle />
                <div class="min-w-0 flex-1">
                    <h2 id="admin-confirm-modal-title" class="text-lg font-semibold text-admin-text">確認</h2>
                    <p id="admin-confirm-modal-message" class="mt-2 whitespace-pre-line text-sm text-admin-text/90"></p>
                    <p id="admin-confirm-modal-note" class="mt-2 hidden text-sm text-admin-muted"></p>
                </div>
                <button
                    type="button"
                    class="shrink-0 rounded-md p-1 text-admin-muted transition hover:bg-admin-hover hover:text-admin-text focus:outline-none focus:ring-2 focus:ring-admin-accent/40"
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
                    class="admin-btn-secondary"
                    data-admin-confirm-cancel
                >キャンセル</button>
                <button
                    type="button"
                    id="admin-confirm-submit"
                    class="admin-btn"
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
                const callbackName = trigger.getAttribute('data-confirm-callback');
                if (!form && !callbackName) {
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
                if (submitting) {
                    return;
                }

                const callbackName = activeTrigger && activeTrigger.getAttribute('data-confirm-callback');
                if (callbackName) {
                    document.dispatchEvent(new CustomEvent(callbackName, {
                        detail: { trigger: activeTrigger },
                    }));
                    closeConfirmModal();
                    return;
                }

                if (!activeForm) {
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
        <div class="admin-modal-panel w-full max-w-md rounded-xl border border-admin-border/50 bg-admin-card p-6" data-admin-delete-panel>
            <div class="mb-4 flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-50 text-admin-danger" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.5 3a1.5 1.5 0 0 0-1.415 1H4a1 1 0 0 0 0 2h.293l.72 9.364A2.5 2.5 0 0 0 7.505 18h4.99a2.5 2.5 0 0 0 2.492-2.636L15.707 6H16a1 1 0 1 0 0-2h-3.085A1.5 1.5 0 0 0 11.5 3h-3Zm1 1a.5.5 0 0 0-.5.5V5h2v-.5a.5.5 0 0 0-.5-.5h-1ZM7.3 6l.69 8.97a.5.5 0 0 0 .498.53h3.024a.5.5 0 0 0 .498-.53L12.7 6H7.3Z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                    <div>
                        <h2 id="admin-delete-modal-title" class="text-lg font-semibold text-admin-text">削除の確認</h2>
                        <p id="admin-delete-modal-message" class="mt-2 text-sm text-admin-text/90"></p>
                        <p class="mt-2 text-sm text-admin-muted">この操作は取り消せません。</p>
                    </div>
                </div>
                <button
                    type="button"
                    id="admin-delete-modal-close"
                    class="rounded-md p-1 text-admin-muted transition hover:bg-admin-hover hover:text-admin-text focus:outline-none focus:ring-2 focus:ring-admin-accent/40"
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
                    class="admin-btn-secondary"
                    data-admin-delete-cancel
                >キャンセル</button>
                <button
                    type="button"
                    id="admin-delete-confirm"
                    class="btn-admin-delete"
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
