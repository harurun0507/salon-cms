@php
    $hasMap = filled($setting->google_map_embed_url);
    $hasMapLink = filled($setting->google_map_url);
    $hasAddress = filled($setting->address);
    $hasAccessDirections = filled($setting->access_directions);
    $hasPhone = filled($setting->phone);
    $hasHours = $setting->hasBusinessHours();
    $businessHoursText = $setting->businessHoursDisplayText();
    $hasClosed = $setting->hasClosedDays();
    $closedDaysText = $setting->closedDaysDisplayText();
@endphp

<section id="access" class="home-vi-access" aria-label="Access">
    <div class="home-vi-access__map" aria-hidden="{{ $hasMap ? 'false' : 'true' }}">
        @if($hasMap)
            <iframe
                src="{{ $setting->google_map_embed_url }}"
                class="home-vi-access__iframe"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="Google Map"
            ></iframe>
        @else
            <div class="home-vi-access__map-fallback">
                <p class="home-vi-kicker">Access</p>
                <p class="home-vi-access__map-fallback-text">地図を準備中です</p>
            </div>
        @endif
        <div class="home-vi-access__map-veil" aria-hidden="true"></div>
    </div>

    <div class="home-vi-access__panel">
        <header class="home-vi-access__head">
            <p class="home-vi-kicker">Access</p>
            <h2 class="home-vi-access__title">店舗情報</h2>
            @if(filled($setting->shop_name))
                <p class="home-vi-access__shop">{{ $setting->shop_name }}</p>
            @endif
        </header>

        <dl class="home-vi-access__list">
            @if($hasAddress)
                <div class="home-vi-access__row">
                    <dt>住所</dt>
                    <dd>{{ $setting->address }}</dd>
                </div>
            @endif
            @if($hasAccessDirections)
                <div class="home-vi-access__row">
                    <dt>アクセス</dt>
                    <dd class="home-vi-access__multiline">{{ $setting->access_directions }}</dd>
                </div>
            @endif
            @if($hasHours)
                <div class="home-vi-access__row">
                    <dt>営業時間</dt>
                    <dd class="home-vi-access__multiline">{{ $businessHoursText }}</dd>
                </div>
            @endif
            @if($hasClosed)
                <div class="home-vi-access__row">
                    <dt>定休日</dt>
                    <dd>{{ $closedDaysText }}</dd>
                </div>
            @endif
            @if($hasPhone)
                <div class="home-vi-access__row">
                    <dt>電話</dt>
                    <dd>
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', (string) $setting->phone) }}">{{ $setting->phone }}</a>
                    </dd>
                </div>
            @endif
        </dl>

        @if($hasMapLink)
            <div class="home-vi-access__actions">
                <a
                    href="{{ $setting->google_map_url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="home-vi-access__maps-link"
                >Google Mapsで見る →</a>
            </div>
        @endif
    </div>
</section>
