@extends('layouts.public')

@section('title', 'メニュー・料金')

@section('content')
    <section class="py-16 md:py-24">
        <div class="mx-auto max-w-4xl px-4 md:px-6">
            <p class="mb-2 text-sm tracking-widest text-salon-accent">Menu</p>
            <h1 class="section-title mb-12">メニュー・料金</h1>

            @forelse($categories as $category)
                <div class="mb-12">
                    <h2 class="mb-6 border-b border-salon-line pb-3 text-xl font-medium">{{ $category->name }}</h2>
                    <ul class="space-y-6">
                        @foreach($category->publishedMenus as $menu)
                            <li class="flex flex-col gap-2 border-b border-salon-line/60 pb-6 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <p class="text-lg">{{ $menu->name }}</p>
                                    @if($menu->description)
                                        <p class="mt-2 text-sm leading-7 text-salon-muted whitespace-pre-line">{{ $menu->description }}</p>
                                    @endif
                                </div>
                                <p class="shrink-0 text-lg font-medium text-salon-button">¥{{ number_format($menu->price) }}</p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <p class="text-salon-muted">メニュー情報を準備中です。</p>
            @endforelse
        </div>
    </section>
@endsection
