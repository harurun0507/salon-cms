@extends('layouts.public')

@section('title', $gallery->displayTitle().' | ヘアギャラリー')

@section('content')
    @php
        $images = $gallery->images;
        $hasCarousel = $images->count() > 1;
        $staff = $gallery->staffMember;
        $displayTitle = $gallery->displayTitle();
        $description = $gallery->displayDescription();
    @endphp
    <section class="site-section">
        <div class="mx-auto max-w-6xl px-6 md:px-10 lg:px-12">
            <div class="gallery-detail-layout">
                <div class="gallery-detail-copy min-w-0">
                    <x-back-link :href="route('gallery')">ギャラリー一覧へ戻る</x-back-link>

                    <h1 class="mt-6 text-3xl font-semibold tracking-wide text-salon-text md:text-[2rem]">
                        {{ $displayTitle }}
                    </h1>

                    @if(filled($description))
                        <p class="mt-8 whitespace-pre-line text-base leading-8 text-salon-muted">
                            {{ $description }}
                        </p>
                    @endif
                </div>

                <div
                    class="gallery-detail-media min-w-0{{ $hasCarousel ? ' gallery-detail-media--carousel' : '' }}"
                    @if($hasCarousel)
                        data-gallery-carousel
                        data-gallery-interval="5000"
                        data-slide-carousel
                        data-slide-carousel-interval="5000"
                        data-slide-carousel-draggable
                        data-slide-carousel-pause-hover
                        data-slide-carousel-pause-focus
                        data-slide-carousel-keyboard
                        tabindex="0"
                        role="region"
                        aria-roledescription="carousel"
                        aria-label="ギャラリー画像"
                    @endif
                >
                    <div class="gallery-detail-frame mx-auto w-full max-w-2xl lg:max-w-none">
                        <div
                            class="gallery-detail-image-frame"
                            data-gallery-stage
                            @if($hasCarousel) data-slide-carousel-viewport @endif
                        >
                            @if($hasCarousel)
                                <div class="gallery-detail-track" data-slide-carousel-track>
                                    @foreach($images as $index => $image)
                                        <figure
                                            class="gallery-detail-slide {{ $index === 0 ? 'is-active' : '' }}"
                                            data-gallery-slide
                                            data-gallery-index="{{ $index }}"
                                            data-slide-carousel-slide
                                            @if($index !== 0) aria-hidden="true" @endif
                                        >
                                            <img
                                                src="{{ asset('storage/'.$image->image_path) }}"
                                                alt="{{ $image->alt_text ?: $displayTitle }}"
                                                class="gallery-media-image gallery-media-image--contain"
                                                draggable="false"
                                                @if($index > 0) loading="lazy" @endif
                                            >
                                        </figure>
                                    @endforeach
                                </div>
                            @else
                                @foreach($images as $index => $image)
                                    <figure
                                        class="gallery-detail-slide is-active"
                                        data-gallery-slide
                                        data-gallery-index="{{ $index }}"
                                    >
                                        <img
                                            src="{{ asset('storage/'.$image->image_path) }}"
                                            alt="{{ $image->alt_text ?: $displayTitle }}"
                                            class="gallery-media-image gallery-media-image--contain"
                                            draggable="false"
                                        >
                                    </figure>
                                @endforeach
                            @endif
                        </div>

                        @if($hasCarousel)
                            <div class="mt-5 flex items-center justify-center gap-4" data-slide-carousel-controls>
                                <button
                                    type="button"
                                    class="gallery-detail-nav"
                                    data-gallery-prev
                                    data-slide-carousel-prev
                                    aria-label="前の画像"
                                >
                                    <svg class="gallery-detail-nav-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                        <path d="M12.5 4.5 7 10l5.5 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>

                                <div class="flex items-center gap-2" data-gallery-dots role="tablist" aria-label="画像選択">
                                    @foreach($images as $index => $image)
                                        <button
                                            type="button"
                                            class="site-carousel-dot {{ $index === 0 ? 'is-active' : '' }}"
                                            data-gallery-dot
                                            data-gallery-index="{{ $index }}"
                                            data-slide-carousel-dot
                                            data-slide-carousel-index="{{ $index }}"
                                            aria-label="画像{{ $index + 1 }}を表示"
                                            @if($index === 0) aria-current="true" @endif
                                        ></button>
                                    @endforeach
                                </div>

                                <button
                                    type="button"
                                    class="gallery-detail-nav"
                                    data-gallery-next
                                    data-slide-carousel-next
                                    aria-label="次の画像"
                                >
                                    <svg class="gallery-detail-nav-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                        <path d="M7.5 4.5 13 10l-5.5 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                @if($staff)
                    <div class="gallery-detail-staff min-w-0">
                        <div class="gallery-detail-staff-inner">
                            <div class="flex items-start gap-5">
                                @if($staff->photo_path)
                                    <img
                                        src="{{ asset('storage/'.$staff->photo_path) }}"
                                        alt="{{ $staff->name }}"
                                        class="h-24 w-24 shrink-0 rounded-full object-cover"
                                    >
                                @else
                                    <div
                                        class="flex h-24 w-24 shrink-0 items-center justify-center rounded-full bg-salon-line text-sm text-salon-muted"
                                        aria-hidden="true"
                                    >Staff</div>
                                @endif

                                <div class="min-w-0">
                                    <p class="text-xs tracking-wide text-salon-muted">担当スタイリスト</p>
                                    @if(filled($staff->role))
                                        <p class="mt-1 text-sm text-salon-muted">{{ $staff->role }}</p>
                                    @endif
                                    <p class="mt-2 text-xl font-medium text-salon-text">{{ $staff->name }}</p>

                                    @if(filled($setting->hot_pepper_url))
                                        <a
                                            href="{{ $setting->hot_pepper_url }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="btn-primary mt-4"
                                        >予約する</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
