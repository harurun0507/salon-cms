@extends('layouts.admin')

@section('heading', 'メニュー登録 - '.$category->name)

@section('content')
    <form method="POST" action="{{ route('admin.menus.store', $category) }}" class="admin-card max-w-xl space-y-5">
        @csrf
        <div>
            <p class="admin-label">カテゴリ（複数選択可）</p>
            <x-admin.choice-toggles
                name="category_ids[]"
                :options="$categories->map(fn ($c) => ['value' => $c->id, 'label' => $c->name])->all()"
                :selected="old('category_ids', [$category->id])"
                aria-label="カテゴリ"
                variant="auto"
            />
        </div>
        @include('admin.menus._form')
        <div class="flex gap-3">
            <button type="submit" class="admin-btn">登録する</button>
            <a href="{{ route('admin.menus.index') }}" class="admin-btn-secondary">戻る</a>
        </div>
    </form>
@endsection
