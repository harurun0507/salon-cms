@extends('layouts.admin')

@section('heading', 'お知らせ登録')

@section('content')
    <form method="POST" action="{{ route('admin.news.store') }}" class="admin-card max-w-3xl space-y-5">
        @csrf
        @include('admin.news._form')
        <div class="flex gap-3">
            <button type="submit" class="admin-btn">登録する</button>
            <a href="{{ route('admin.news.index') }}" class="admin-btn-secondary">戻る</a>
        </div>
    </form>
@endsection
