@extends('layouts.admin')

@section('heading', 'メニュー編集')

@section('content')
    <form method="POST" action="{{ route('admin.menus.update', $menu) }}" class="admin-card max-w-xl space-y-5">
        @csrf @method('PUT')
        <div>
            <label for="menu_category_id" class="admin-label">カテゴリ</label>
            <select name="menu_category_id" id="menu_category_id" class="admin-input">
                @foreach(\App\Models\MenuCategory::orderBy('sort_order')->get() as $category)
                    <option value="{{ $category->id }}" @selected(old('menu_category_id', $menu->menu_category_id) == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        @include('admin.menus._form')
        <div class="flex gap-3">
            <button type="submit" class="admin-btn">更新する</button>
            <a href="{{ route('admin.menus.index') }}" class="admin-btn-secondary">戻る</a>
        </div>
    </form>
@endsection
