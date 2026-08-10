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
        @php
            $allowMultiple = (string) old(
                'allow_multiple_selection',
                $category->allow_multiple_selection ? '1' : '0'
            ) === '1';
        @endphp
        <div>
            <span class="admin-label mb-1.5 block">複数設定可</span>
            <label class="admin-switch admin-switch--compact">
                <input type="hidden" name="allow_multiple_selection" value="0">
                <input
                    type="checkbox"
                    name="allow_multiple_selection"
                    value="1"
                    class="admin-switch-input"
                    aria-label="複数設定可"
                    @checked($allowMultiple)
                >
                <span class="admin-switch-track" aria-hidden="true">
                    <span class="admin-switch-thumb"></span>
                </span>
                <span class="admin-switch-text">{{ $allowMultiple ? '可' : '不可' }}</span>
            </label>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="admin-btn">更新する</button>
            <a href="{{ route('admin.menus.index') }}" class="admin-btn-secondary">戻る</a>
        </div>
    </form>
@endsection
