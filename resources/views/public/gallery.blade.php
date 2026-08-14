@extends('layouts.public')

@section('title', 'ヘアギャラリー')

@section('content')
    @php
        $design = $design ?? \App\Models\DesignSetting::current();
        $useGalleryModal = $design->usesGalleryDetailModal();
    @endphp
    <section class="site-section">
        <div class="mx-auto max-w-6xl px-4 md:px-6">
            <x-public.list-page-header
                eyebrow="Gallery"
                title="ヘアギャラリー"
                :back-href="\App\Models\TopPageSection::listPageBackHref('gallery')"
            />

            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-5">
                @forelse($galleries as $gallery)
                    @php
                        $cover = $gallery->coverImagePath();
                        $coverImage = $gallery->coverImage();
                        $alt = $coverImage?->alt_text ?: $gallery->displayTitle();
                    @endphp
                    @if($cover)
                        <a
                            href="{{ route('gallery.show', $gallery) }}"
                            class="gallery-media-card group"
                            @if($useGalleryModal)
                                data-gallery-modal-trigger
                                data-gallery-id="{{ $gallery->id }}"
                            @endif
                        >
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

    @if($useGalleryModal)
        <x-public.gallery-modal
            :galleries="$galleries"
            :reserve-url="$setting->hot_pepper_url ?? null"
        />
    @endif
@endsection
