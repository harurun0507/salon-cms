@php
    /** @var \Illuminate\Support\Collection $heroImages */
    $heroImages = $heroImages ?? collect();
    $conceptImagePath = $setting->conceptImagePath();
    $conceptImageAlt = $setting->conceptImageAlt();

    if (! $conceptImagePath) {
        $fallbackHero = $heroImages->count() > 1 ? $heroImages->get(1) : $heroImages->first();
        $conceptImagePath = $fallbackHero?->image_path;
        $conceptImageAlt = $fallbackHero?->alt_text ?: $conceptImageAlt;
    }
@endphp

<section id="concept" class="home-vi-concept" aria-label="Concept">
    <div class="home-vi-concept__media" aria-hidden="{{ $conceptImagePath ? 'false' : 'true' }}">
        @if($conceptImagePath)
            <img
                src="{{ asset('storage/'.$conceptImagePath) }}"
                alt="{{ $conceptImageAlt }}"
                class="home-vi-concept__image"
            >
        @else
            <div class="home-vi-concept__fallback"></div>
        @endif
        <div class="home-vi-concept__shade"></div>
    </div>

    <div class="home-vi-concept__copy">
        <p class="home-vi-kicker">Concept</p>
        @if(!empty($setting->concept_title))
            <h2 class="home-vi-concept__title">{{ $setting->concept_title }}</h2>
        @endif
        <p class="home-vi-concept__body">{{ $setting->concept }}</p>
    </div>
</section>
