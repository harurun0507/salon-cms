@extends('layouts.public')

@section('title', 'ヘアギャラリー')

@section('content')
    <section class="site-section">
        <div class="mx-auto max-w-6xl px-4 md:px-6">
            <p class="mb-2 text-sm tracking-widest text-salon-accent">Gallery</p>
            <h1 class="section-title mb-12">ヘアギャラリー</h1>

            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-5">
                @forelse($galleries as $gallery)
                    @php
                        $cover = $gallery->coverImagePath();
                        $coverImage = $gallery->coverImage();
                        $alt = $coverImage?->alt_text ?: $gallery->displayTitle();
                    @endphp
                    @if($cover)
                        <a href="{{ route('gallery.show', $gallery) }}" class="gallery-media-card group">
                            <figure>
                                <span class="gallery-media-frame">
                                    <img
                                        src="{{ asset('storage/'.$cover) }}"
                                        alt="{{ $alt }}"
                                        class="gallery-media-image gallery-media-image--hover"
                                    >
                                </span>
                                @if($gallery->displayTitle() !== 'ギャラリー' || filled($gallery->title))
                                    <figcaption class="mt-2 text-sm text-salon-muted">{{ $gallery->displayTitle() }}</figcaption>
                                @endif
                            </figure>
                        </a>
                    @endif
                @empty
                    <p class="col-span-full text-salon-muted">ギャラリー準備中です。</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
