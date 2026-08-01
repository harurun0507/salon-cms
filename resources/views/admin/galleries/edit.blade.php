@extends('layouts.admin')

@section('heading', 'ギャラリー編集')

@section('content')
    <form method="POST" action="{{ route('admin.galleries.update', $gallery) }}" enctype="multipart/form-data" class="admin-card max-w-xl space-y-5">
        @csrf @method('PUT')
        @include('admin.galleries._form')
        <div class="flex gap-3">
            <button type="submit" class="admin-btn">更新する</button>
            <a href="{{ route('admin.galleries.index') }}" class="admin-btn-secondary">戻る</a>
        </div>
    </form>
@endsection
