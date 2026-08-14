@php
    $viBanners = collect($banners ?? []);
@endphp

@if($viBanners->isNotEmpty())
    <section id="banners" class="home-vi-campaign" aria-label="Campaign">
        <div class="home-vi-campaign__intro">
            <p class="home-vi-kicker">Campaign</p>
            <h2 class="home-vi-campaign__title">キャンペーン</h2>
        </div>

        <div class="home-vi-campaign__stage">
            @foreach($viBanners as $index => $banner)
                @php
                    $alt = $banner->altTextOrTitle();
                @endphp
                @if($banner->hasLink())
                    <a
                        href="{{ $banner->link_url }}"
                        @if($banner->opensInNewTab()) target="_blank" rel="noopener noreferrer" @endif
                        @class([
                            'home-vi-campaign__item',
                            'home-vi-campaign__item--wide' => $index === 0,
                        ])
                    >
                        <img
                            src="{{ asset('storage/'.$banner->image_path) }}"
                            alt="{{ $alt }}"
                            class="home-vi-campaign__image"
                        >
                        @if($banner->title)
                            <span class="home-vi-campaign__caption">{{ $banner->title }}</span>
                        @endif
                    </a>
                @else
                    <article @class([
                        'home-vi-campaign__item',
                        'home-vi-campaign__item--wide' => $index === 0,
                    ])>
                        <img
                            src="{{ asset('storage/'.$banner->image_path) }}"
                            alt="{{ $alt }}"
                            class="home-vi-campaign__image"
                        >
                        @if($banner->title)
                            <span class="home-vi-campaign__caption">{{ $banner->title }}</span>
                        @endif
                    </article>
                @endif
            @endforeach
        </div>

        <div class="home-vi-campaign__footer">
            <x-section-more-link :href="route('campaign')">すべて見る →</x-section-more-link>
        </div>
    </section>
@endif
