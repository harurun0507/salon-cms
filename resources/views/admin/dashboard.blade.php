@extends('layouts.admin')

@section('heading', 'ダッシュボード')

@section('content')
    <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
        <div class="admin-card">
            <p class="text-sm text-admin-muted">お知らせ</p>
            <p class="mt-3 font-serif text-3xl font-medium tracking-wide text-admin-text">{{ $newsCount }}</p>
            <a href="{{ route('admin.news.index') }}" class="mt-5 inline-block text-sm text-admin-accent hover:underline">管理する →</a>
        </div>
        <div class="admin-card">
            <p class="text-sm text-admin-muted">ギャラリー</p>
            <p class="mt-3 font-serif text-3xl font-medium tracking-wide text-admin-text">{{ $galleryCount }}</p>
            <a href="{{ route('admin.galleries.index') }}" class="mt-5 inline-block text-sm text-admin-accent hover:underline">管理する →</a>
        </div>
        <div class="admin-card">
            <p class="text-sm text-admin-muted">メニュー</p>
            <p class="mt-3 font-serif text-3xl font-medium tracking-wide text-admin-text">{{ $menuCount }}</p>
            <a href="{{ route('admin.menus.index') }}" class="mt-5 inline-block text-sm text-admin-accent hover:underline">管理する →</a>
        </div>
        <div class="admin-card">
            <p class="text-sm text-admin-muted">スタッフ</p>
            <p class="mt-3 font-serif text-3xl font-medium tracking-wide text-admin-text">{{ $staffCount }}</p>
            <a href="{{ route('admin.staff.index') }}" class="mt-5 inline-block text-sm text-admin-accent hover:underline">管理する →</a>
        </div>
    </div>
@endsection
