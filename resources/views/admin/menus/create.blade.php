@extends('layouts.admin')

@section('heading', 'メニュー登録 - '.$category->name)

@section('content')
    <form method="POST" action="{{ route('admin.menus.store', $category) }}" class="admin-card max-w-xl space-y-5">
        @csrf
        @include('admin.menus._form')
        <div class="flex gap-3">
            <button type="submit" class="admin-btn">登録する</button>
            <a href="{{ route('admin.menus.index') }}" class="admin-btn-secondary">戻る</a>
        </div>
    </form>
@endsection
