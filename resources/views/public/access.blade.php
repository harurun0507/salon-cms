@extends('layouts.public')

@section('title', 'アクセス・店舗情報')

@section('content')
    <section class="site-section">
        <div class="mx-auto max-w-6xl px-4 md:px-6">
            <p class="mb-2 text-sm tracking-widest text-salon-accent">Access</p>
            <h1 class="section-title mb-12">アクセス・店舗情報</h1>

            <div class="grid gap-8 md:grid-cols-2 md:items-stretch">
                <div class="site-card border border-salon-line bg-white/70">
                    <dl class="space-y-6 text-sm leading-7">
                        <div><dt class="font-medium">店名</dt><dd class="text-salon-muted">{{ $setting->shop_name }}</dd></div>
                        <div><dt class="font-medium">住所</dt><dd class="text-salon-muted">{{ $setting->address }}</dd></div>
                        <div><dt class="font-medium">営業時間</dt><dd class="whitespace-pre-line text-salon-muted">{{ $setting->business_hours }}</dd></div>
                        <div><dt class="font-medium">定休日</dt><dd class="text-salon-muted">{{ $setting->closed_days }}</dd></div>
                        <div><dt class="font-medium">電話番号</dt><dd class="text-salon-muted">{{ $setting->phone }}</dd></div>
                    </dl>
                    <div class="mt-8 flex flex-wrap gap-3">
                        @if($setting->hot_pepper_url)
                            <a href="{{ $setting->hot_pepper_url }}" target="_blank" rel="noopener noreferrer" class="btn-primary">予約する</a>
                        @endif
                        @if($setting->google_map_url)
                            <a href="{{ $setting->google_map_url }}" target="_blank" rel="noopener noreferrer" class="btn-outline">Googleマップで開く</a>
                        @endif
                        @if($setting->instagram_url)
                            <a href="{{ $setting->instagram_url }}" target="_blank" rel="noopener noreferrer" class="btn-outline">Instagram</a>
                        @endif
                    </div>
                </div>

                <div class="min-h-[280px] overflow-hidden border border-salon-line bg-salon-line/40 md:min-h-[420px]" style="border-radius: var(--site-card-radius, 0.5rem);">
                    @if($setting->google_map_embed_url)
                        <iframe
                            src="{{ $setting->google_map_embed_url }}"
                            class="h-full min-h-[280px] w-full border-0 md:min-h-[420px]"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="Google Map"
                        ></iframe>
                    @else
                        <div class="flex h-full min-h-[280px] flex-col items-center justify-center gap-3 px-6 text-center md:min-h-[420px]">
                            <svg class="h-10 w-10 text-salon-accent/60" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10z"/>
                                <circle cx="12" cy="11" r="2.5" stroke-width="1.2"/>
                            </svg>
                            <p class="font-serif text-lg tracking-wide text-salon-muted">Google Map</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
