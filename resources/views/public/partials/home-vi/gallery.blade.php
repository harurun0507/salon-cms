@php
    $viGalleries = collect($galleries ?? [])->filter(fn ($gallery) => filled($gallery->coverImagePath()))->values();
    $featured = $viGalleries->first();
    $sideGalleries = $viGalleries->slice(1)->values();
    $useGalleryModal = $useGalleryModal ?? false;
@endphp

@if($featured)
    @php
        // Top shows at most 3: 1 featured + up to 2 rail tiles.
        $railGalleries = $sideGalleries->take(2)->values();
        $railCount = max(1, $railGalleries->count());
    @endphp
    <section id="gallery" class="home-vi-gallery" aria-label="Gallery">
        <div class="home-vi-gallery__head">
            <p class="home-vi-kicker home-vi-kicker--on-dark">Gallery</p>
            <h2 class="home-vi-gallery__title">ヘアギャラリー</h2>
        </div>

        <div
            class="home-vi-gallery__stage{{ $railGalleries->isEmpty() ? ' home-vi-gallery__stage--solo' : '' }}"
            style="--gallery-rail-count: {{ $railCount }};"
        >
            @php
                $featuredUrl = route('gallery.show', $featured);
                $featuredCover = $featured->coverImagePath();
                $featuredAlt = $featured->coverImage()?->alt_text ?: $featured->displayTitle();
                $featuredTitle = trim((string) ($featured->title ?? ''));
            @endphp
            <article class="home-vi-gallery__feature" data-home-gallery-href="{{ $featuredUrl }}">
                <div class="home-vi-gallery__media">
                    <a
                        href="{{ $featuredUrl }}"
                        class="home-vi-gallery__hit"
                        aria-label="{{ $featured->displayTitle() }}の詳細を見る"
                        @if($useGalleryModal)
                            data-gallery-modal-trigger
                            data-gallery-id="{{ $featured->id }}"
                        @endif
                    ></a>
                    <img
                        src="{{ asset('storage/'.$featuredCover) }}"
                        alt="{{ $featuredAlt }}"
                        class="home-vi-gallery__image home-vi-gallery__image--feature"
                    >
                </div>
                @if($featuredTitle !== '')
                    <div class="home-vi-gallery__caption">
                        <h3 class="home-vi-gallery__caption-title">{{ $featuredTitle }}</h3>
                    </div>
                @endif
            </article>

            @if($railGalleries->isNotEmpty())
                <div class="home-vi-gallery__rail">
                    @foreach($railGalleries as $gallery)
                        @php
                            $galleryUrl = route('gallery.show', $gallery);
                            $cover = $gallery->coverImagePath();
                            $alt = $gallery->coverImage()?->alt_text ?: $gallery->displayTitle();
                            $galleryTitle = trim((string) ($gallery->title ?? ''));
                        @endphp
                        <article class="home-vi-gallery__tile" data-home-gallery-href="{{ $galleryUrl }}">
                            <div class="home-vi-gallery__media">
                                <a
                                    href="{{ $galleryUrl }}"
                                    class="home-vi-gallery__hit"
                                    aria-label="{{ $gallery->displayTitle() }}の詳細を見る"
                                    @if($useGalleryModal)
                                        data-gallery-modal-trigger
                                        data-gallery-id="{{ $gallery->id }}"
                                    @endif
                                ></a>
                                <img
                                    src="{{ asset('storage/'.$cover) }}"
                                    alt="{{ $alt }}"
                                    class="home-vi-gallery__image home-vi-gallery__image--tile"
                                >
                            </div>
                            @if($galleryTitle !== '')
                                <div class="home-vi-gallery__tile-meta">
                                    <p class="home-vi-gallery__tile-label">{{ $galleryTitle }}</p>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="home-vi-gallery__footer">
            <x-section-more-link :href="route('gallery')" class="home-vi-more-link--on-dark">すべて見る →</x-section-more-link>
        </div>
    </section>
@endif
