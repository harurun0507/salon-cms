<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>管理画面ログイン - Salon CMS</title>
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <style type="text/tailwindcss">
            @layer components {
                .admin-input { @apply w-full rounded-md border border-gray-300 px-3 py-2 text-sm; }
                .admin-label { @apply mb-1 block text-sm font-medium text-gray-700; }
                .admin-btn { @apply inline-flex w-full items-center justify-center rounded-md bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800; }
            }
        </style>
    @endif
</head>
<body class="flex min-h-screen items-center justify-center bg-gray-100 font-sans">
    <div class="w-full max-w-md rounded-lg border border-gray-200 bg-white p-8 shadow-sm">
        <h1 class="mb-2 text-center text-xl font-semibold text-gray-900">Salon CMS</h1>
        <p class="mb-8 text-center text-sm text-gray-500">管理画面にログイン</p>

        @isset($errors)
            @if($errors->any())
                <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
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
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" class="rounded border-gray-300">
                ログイン状態を保持
            </label>
            <button type="submit" class="admin-btn w-full justify-center">ログイン</button>
        </form>
    </div>
</body>
</html>
