<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>管理画面ログイン - Sun＆Me CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;600&family=Noto+Serif+JP:wght@500;600&display=swap" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['"Noto Sans JP"', 'Hiragino Sans', 'Yu Gothic', 'sans-serif'],
                            serif: ['"Noto Serif JP"', 'Hiragino Mincho ProN', 'serif'],
                        },
                        colors: {
                            'admin-bg': '#F7F5F0',
                            'admin-card': '#FFFFFF',
                            'admin-text': '#3D3833',
                            'admin-muted': '#736D65',
                            'admin-accent': '#697A55',
                            'admin-accent-dark': '#556344',
                            'admin-border': '#E5E0D7',
                            'admin-hover': '#EEF1E8',
                        }
                    }
                }
            }
        </script>
        <style type="text/tailwindcss">
            @layer components {
                .admin-input { @apply w-full rounded-lg border border-admin-border bg-admin-card px-3 py-2.5 text-sm text-admin-text focus:border-admin-accent focus:outline-none focus:ring-1 focus:ring-admin-accent/40; }
                .admin-label { @apply mb-1.5 block text-sm font-medium text-admin-text; }
                .admin-btn { @apply inline-flex w-full items-center justify-center rounded-lg bg-admin-accent px-4 py-2.5 text-sm font-medium text-white transition hover:bg-admin-accent-dark; }
                .admin-brand-title { @apply font-serif text-xl font-medium tracking-wide text-admin-text; }
            }
        </style>
    @endif
</head>
<body class="relative flex min-h-screen items-center justify-center overflow-hidden bg-admin-bg font-sans text-admin-text antialiased">
    {{-- Bottom-left botanical watermark --}}
    <svg class="pointer-events-none absolute -bottom-10 -left-12 h-72 w-72 text-[#B8B09F]" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" opacity="0.22">
        <path d="M42 178c18-42 38-78 78-118" stroke-width="1.4"/>
        <path d="M78 98c-18-8-34-8-48 2" stroke-width="1.25"/>
        <path d="M92 82c14-16 34-24 52-22" stroke-width="1.25"/>
        <path d="M108 64c8-18 24-30 44-34" stroke-width="1.2"/>
        <path d="M70 118c-10 14-14 30-10 44" stroke-width="1.2"/>
        <path d="M54 112c-12-14-14-32-6-46 16 4 28 16 30 32-6 5-14 10-24 14Z" stroke-width="1.3"/>
        <path d="M88 86c8-18 4-36-8-48 18 0 34 10 40 26-8 8-18 14-32 22Z" stroke-width="1.3"/>
        <path d="M112 60c16-12 36-12 50-2-6 16-20 28-38 32-4-10-8-20-12-30Z" stroke-width="1.25"/>
        <path d="M68 128c-4 16 2 32 14 42 10-12 14-28 10-42-8 0-16 0-24 0Z" stroke-width="1.25"/>
    </svg>
    {{-- Top-right faint leaf --}}
    <svg class="pointer-events-none absolute -right-2 top-8 h-36 w-36 text-[#C7C0B2]" viewBox="0 0 80 80" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" opacity="0.28">
        <path d="M40 68V28" stroke-width="1.3"/>
        <path d="M40 34c12-5 22-14 24-26-14 1-26 10-30 22 2 2 4 3 6 4Z" stroke-width="1.3"/>
        <path d="M40 44c-12-4-20-13-22-24 13-.5 24 7 28 18-2 2-4 4-6 6Z" stroke-width="1.3"/>
        <path d="M40 54c9-3 16-10 18-18-9 0-16 5-19 11 0 2 0 5 1 7Z" stroke-width="1.2"/>
    </svg>

    <div class="relative z-10 w-full max-w-md rounded-xl border border-admin-border/40 bg-admin-card p-8 shadow-[0_2px_10px_rgba(0,0,0,0.04)]">
        <div class="mb-8 flex flex-col items-center text-center">
            <span class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-full bg-[#E8EDE3] text-admin-accent" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 21c-4.5-2.5-7.5-6.2-7.5-10.2C4.5 6.2 7.8 3 12 3c4.2 0 7.5 3.2 7.5 7.8 0 4-3 7.7-7.5 10.2Z" />
                    <path d="M12 21V9" />
                    <path d="M12 12c1.8-.8 3.2-2.2 4-4" />
                    <path d="M12 15c-1.5-.6-2.7-1.7-3.5-3" />
                </svg>
            </span>
            <h1 class="admin-brand-title">Sun ＆ Me</h1>
            <p class="mt-2 text-sm text-admin-muted">管理画面にログイン</p>
        </div>

        @isset($errors)
            @if($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif
        @endisset

        <form method="POST" action="{{ route('admin.login') }}" class="space-y-5">
            @csrf
            <div>
                <label for="email" class="admin-label">メールアドレス</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus class="admin-input">
            </div>
            <div>
                <label for="password" class="admin-label">パスワード</label>
                <input type="password" name="password" id="password" required class="admin-input">
            </div>
            <label class="flex items-center gap-2 text-sm text-admin-muted">
                <input type="checkbox" name="remember" class="rounded border-admin-border text-admin-accent focus:ring-admin-accent/40">
                ログイン状態を保持
            </label>
            <button type="submit" class="admin-btn w-full justify-center">ログイン</button>
        </form>
    </div>
</body>
</html>
