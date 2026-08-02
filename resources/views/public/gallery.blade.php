@extends('layouts.public')

@section('title', 'ヘアギャラリー')

@section('content')
    <section class="site-section">
        <div class="mx-auto max-w-6xl px-4 md:px-6">
            <p class="mb-2 text-sm tracking-widest text-salon-accent">Gallery</p>
            <h1 class="section-title mb-12">ヘアギャラリー</h1>

            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-5">
                @forelse($galleries as $gallery)
                    <figure>
                        <div class="aspect-[3/4] overflow-hidden rounded-sm bg-salon-line">
                            <img src="{{ asset('storage/'.$gallery->image_path) }}" alt="{{ $gallery->caption }}" class="h-full w-full object-cover">
                        </div>
                        @if($gallery->caption)
                            <figcaption class="mt-2 text-sm text-salon-muted">{{ $gallery->caption }}</figcaption>
                        @endif
                    </figure>
                @empty
                    <p class="col-span-full text-salon-muted">ギャラリー準備中です。</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
