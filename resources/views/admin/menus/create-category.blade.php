@extends('layouts.admin')

@section('heading', 'カテゴリ登録')

@section('content')
    <form method="POST" action="{{ route('admin.menus.categories.store') }}" class="admin-card max-w-xl space-y-5">
        @csrf
        <div>
            <label for="name" class="admin-label">カテゴリ名</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required class="admin-input">
        </div>
        <div>
            <label for="sort_order" class="admin-label">表示順</label>
            <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="admin-input">
        </div>
        <div class="flex gap-3">
            <button type="submit" class="admin-btn">登録する</button>
            <a href="{{ route('admin.menus.index') }}" class="admin-btn-secondary">戻る</a>
        </div>
    </form>
@endsection
