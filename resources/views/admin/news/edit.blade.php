@extends('layouts.admin')

@section('heading', 'お知らせ編集')

@section('content')
    <form method="POST" action="{{ route('admin.news.update', $news) }}" class="admin-card max-w-3xl space-y-5">
        @csrf @method('PUT')
        @include('admin.news._form')
        <div class="flex gap-3">
            <button type="submit" class="admin-btn">更新する</button>
            <a href="{{ route('admin.news.index') }}" class="admin-btn-secondary">戻る</a>
        </div>
    </form>
@endsection
