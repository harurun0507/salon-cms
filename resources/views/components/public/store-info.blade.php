@props([
    'setting',
    'showReserve' => true,
])

@php
    $leftItems = array_values(array_filter([
        ['label' => '店名', 'value' => $setting->shop_name, 'multiline' => false],
        ['label' => '営業時間', 'value' => $setting->business_hours, 'multiline' => true],
        ['label' => '定休日', 'value' => $setting->closed_days, 'multiline' => false],
        ['label' => '電話番号', 'value' => $setting->phone, 'multiline' => false],
    ], static fn (array $item): bool => filled($item['value'])));

    $hasAddress = filled($setting->address);
    $hasAccessDirections = filled($setting->access_directions);
    $hasLocationDetails = $hasAddress || $hasAccessDirections;

    $salonGridItems = array_values(array_filter([
        ['label' => '支払い方法', 'value' => $setting->payment_methods, 'multiline' => true],
        ['label' => 'カット価格', 'value' => $setting->cut_price, 'multiline' => false],
        ['label' => '席数', 'value' => $setting->seat_count, 'multiline' => false],
        ['label' => 'スタッフ数', 'value' => $setting->staff_count, 'multiline' => false],
    ], static fn (array $item): bool => filled($item['value'])));

    $parking = $setting->parking;
    $hasSalonBlock = $salonGridItems !== [] || filled($parking);

    $extraItems = array_values(array_filter([
        ['label' => 'こだわり条件', 'value' => $setting->commitment_conditions],
        ['label' => '備考', 'value' => $setting->notes],
        ['label' => 'その他', 'value' => $setting->other_info],
    ], static fn (array $item): bool => filled($item['value'])));

    $hasMap = filled($setting->google_map_embed_url);
    $hasMapLink = filled($setting->google_map_url);
    $hasReserve = $showReserve && filled($setting->hot_pepper_url);

    $cardClass = 'access-info-card';
@endphp

<div {{ $attributes->class('space-y-6') }}>
    {{-- 上段: 基本情報 | マップ・住所・アクセス --}}
    <div class="grid gap-6 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
        <section class="{{ $cardClass }}">
            @if ($leftItems !== [])
                <div>
                    @foreach ($leftItems as $item)
                        <div class="border-b border-salon-line/60 py-3.5 first:pt-0 last:border-b-0 last:pb-0">
                            <p class="text-xs tracking-wide text-salon-muted">{{ $item['label'] }}</p>
                            <p @class([
                                'mt-1.5 text-sm leading-7 text-salon-text',
                                'whitespace-pre-line' => $item['multiline'],
                            ])>{{ $item['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($hasReserve)
                <div @class(['mt-5' => $leftItems !== []])>
                    <a
                        href="{{ $setting->hot_pepper_url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn-primary inline-flex"
                    >予約する</a>
                </div>
            @endif
        </section>

        <section class="access-map-card">
            <div class="overflow-hidden rounded-lg bg-salon-line/40">
                @if ($hasMap)
                    <div class="aspect-video min-h-[220px] w-full sm:min-h-[260px] lg:min-h-[280px]">
                        <iframe
                            src="{{ $setting->google_map_embed_url }}"
                            class="h-full w-full rounded-lg border-0"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="Google Map"
                        ></iframe>
                    </div>
                @else
                    <div class="flex aspect-video min-h-[220px] w-full flex-col items-center justify-center gap-3 px-6 text-center sm:min-h-[260px] lg:min-h-[280px]">
                        <svg class="h-10 w-10 text-salon-accent/60" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10z"/>
                            <circle cx="12" cy="11" r="2.5" stroke-width="1.2"/>
                        </svg>
                        <p class="font-serif text-lg tracking-wide text-salon-muted">Google Map</p>
                    </div>
                @endif
            </div>

            @if ($hasLocationDetails || $hasMapLink)
                <div class="mt-5 border-t border-salon-line/70 pt-5">
                    @if ($hasAddress)
                        <div>
                            <p class="text-xs tracking-wide text-salon-muted">住所</p>
                            <p class="mt-1.5 text-sm leading-7 text-salon-text">{{ $setting->address }}</p>
                        </div>
                    @endif

                    @if ($hasAccessDirections)
                        <div @class(['mt-5' => $hasAddress])>
                            <p class="text-xs tracking-wide text-salon-muted">アクセス・道案内</p>
                            <p class="mt-1.5 whitespace-pre-line text-sm leading-7 text-salon-text">{{ $setting->access_directions }}</p>
                        </div>
                    @endif

                    @if ($hasMapLink)
                        <a
                            href="{{ $setting->google_map_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn-outline mt-5 inline-flex w-full sm:w-auto"
                        >Googleマップで開く</a>
                    @endif
                </div>
            @endif
        </section>
    </div>

    {{-- 下段: サロン情報 --}}
    @if ($hasSalonBlock)
        <section class="access-salon-card">
            <h3 class="font-serif text-lg tracking-wide text-salon-text">サロン情報</h3>

            @if ($salonGridItems !== [])
                <div class="mt-5 grid gap-x-10 gap-y-0 md:grid-cols-2">
                    @foreach ($salonGridItems as $item)
                        <div class="border-b border-salon-line/60 py-3.5">
                            <p class="text-xs tracking-wide text-salon-muted">{{ $item['label'] }}</p>
                            <p @class([
                                'mt-1.5 text-sm leading-7 text-salon-text',
                                'whitespace-pre-line' => $item['multiline'],
                            ])>{{ $item['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            @if (filled($parking))
                <div @class([
                    'md:col-span-2 border-t border-salon-line/60 pt-4' => $salonGridItems !== [],
                    'mt-5' => $salonGridItems === [],
                ])>
                    <p class="text-xs tracking-wide text-salon-muted">駐車場</p>
                    <p class="mt-1.5 whitespace-pre-line text-sm leading-7 text-salon-text">{{ $parking }}</p>
                </div>
            @endif
        </section>
    @endif

    {{-- こだわり・補足 --}}
    @if ($extraItems !== [])
        <section class="access-salon-card">
            <h3 class="font-serif text-lg tracking-wide text-salon-text">こだわり・補足情報</h3>
            <div class="mt-5">
                @foreach ($extraItems as $item)
                    <div class="border-b border-salon-line/60 py-3.5 first:pt-0 last:border-b-0 last:pb-0">
                        <p class="text-xs tracking-wide text-salon-muted">{{ $item['label'] }}</p>
                        <p class="mt-1.5 whitespace-pre-line text-sm leading-7 text-salon-text">{{ $item['value'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
