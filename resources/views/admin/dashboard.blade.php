@extends('layouts.admin')

@section('heading', 'ダッシュボード')

@section('content')
    <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
        <div class="admin-card">
            <p class="text-sm text-gray-500">お知らせ</p>
            <p class="mt-2 text-3xl font-semibold">{{ $newsCount }}</p>
            <a href="{{ route('admin.news.index') }}" class="mt-4 inline-block text-sm text-slate-600 hover:underline">管理する →</a>
        </div>
        <div class="admin-card">
            <p class="text-sm text-gray-500">ギャラリー</p>
            <p class="mt-2 text-3xl font-semibold">{{ $galleryCount }}</p>
            <a href="{{ route('admin.galleries.index') }}" class="mt-4 inline-block text-sm text-slate-600 hover:underline">管理する →</a>
        </div>
        <div class="admin-card">
            <p class="text-sm text-gray-500">メニュー</p>
            <p class="mt-2 text-3xl font-semibold">{{ $menuCount }}</p>
            <a href="{{ route('admin.menus.index') }}" class="mt-4 inline-block text-sm text-slate-600 hover:underline">管理する →</a>
        </div>
        <div class="admin-card">
            <p class="text-sm text-gray-500">スタッフ</p>
            <p class="mt-2 text-3xl font-semibold">{{ $staffCount }}</p>
            <a href="{{ route('admin.staff.index') }}" class="mt-4 inline-block text-sm text-slate-600 hover:underline">管理する →</a>
        </div>
    </div>
@endsection
