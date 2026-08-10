@extends('layouts.admin')

@section('heading', 'メニュー編集')

@section('content')
    <form method="POST" action="{{ route('admin.menus.update', $menu) }}" class="admin-card max-w-xl space-y-5">
        @csrf @method('PUT')
        <div>
            <p class="admin-label">カテゴリ（複数選択可）</p>
            <x-admin.choice-toggles
                name="category_ids[]"
                :options="$categories->map(fn ($c) => [
                    'value' => $c->id,
                    'label' => $c->name,
                    'allow_multiple' => (bool) $c->allow_multiple_selection,
                ])->all()"
                :selected="old('category_ids', $menu->categories->pluck('id')->all())"
                aria-label="カテゴリ"
                variant="auto"
                input-data-attribute="data-menu-category-checkbox"
            />
        </div>
        @include('admin.menus._form')
        <div class="flex gap-3">
            <button type="submit" class="admin-btn">更新する</button>
            <a href="{{ route('admin.menus.index') }}" class="admin-btn-secondary">戻る</a>
        </div>
    </form>
    @include('admin.menus.partials.category-selection-script')
@endsection
