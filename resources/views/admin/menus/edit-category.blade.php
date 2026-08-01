@extends('layouts.admin')

@section('heading', 'カテゴリ編集')

@section('content')
    <form method="POST" action="{{ route('admin.menus.categories.update', $category) }}" class="admin-card max-w-xl space-y-5">
        @csrf @method('PUT')
        <div>
            <label for="name" class="admin-label">カテゴリ名</label>
            <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" required class="admin-input">
        </div>
        <div>
            <label for="sort_order" class="admin-label">表示順</label>
            <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $category->sort_order) }}" min="0" class="admin-input">
        </div>
        <div class="flex gap-3">
            <button type="submit" class="admin-btn">更新する</button>
            <a href="{{ route('admin.menus.index') }}" class="admin-btn-secondary">戻る</a>
        </div>
    </form>
@endsection
