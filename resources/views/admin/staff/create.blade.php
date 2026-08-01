@extends('layouts.admin')

@section('heading', 'スタッフ登録')

@section('content')
    <form method="POST" action="{{ route('admin.staff.store') }}" enctype="multipart/form-data" class="admin-card max-w-xl space-y-5">
        @csrf
        @include('admin.staff._form')
        <div class="flex gap-3">
            <button type="submit" class="admin-btn">登録する</button>
            <a href="{{ route('admin.staff.index') }}" class="admin-btn-secondary">戻る</a>
        </div>
    </form>
@endsection
