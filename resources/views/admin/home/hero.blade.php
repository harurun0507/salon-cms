@extends('layouts.admin')

@section('heading', 'メインビジュアル')

@section('content')
    @php
        $heroImages = $setting->heroImages;
        $heroCount = $heroImages->count();
        $maxHeroImages = \App\Models\HeroImage::MAX_COUNT;
    @endphp

    <div class="sticky top-[4.5rem] z-10 -mx-4 -mt-4 mb-6 border-b border-admin-border/50 bg-admin-bg/95 px-4 py-3 shadow-[0_1px_0_rgba(61,56,51,0.03)] backdrop-blur-sm md:-mx-8 md:-mt-8 md:px-8">
        <div class="flex min-w-0 flex-wrap items-center gap-4">
            <button
                type="button"
                class="admin-btn shadow-md shrink-0"
                data-admin-confirm-trigger
                data-confirm-form="hero-form"
                data-confirm-title="メインビジュアル保存の確認"
                data-confirm-message="メインビジュアルを保存します。&#10;よろしいですか？"
                data-confirm-note="画像・表示順・公開状態・altテキストなど、現在入力されている内容が反映されます。"
                data-confirm-submit-label="保存する"
            >保存する</button>
            <p class="min-w-0 flex-1 text-sm text-admin-muted">
                トップページのメインビジュアルを登録します。推奨：横長画像（JPEG / PNG / WebP、5MBまで、合計最大{{ $maxHeroImages }}枚）
            </p>
            <p class="hero-count-badge shrink-0{{ $heroCount >= $maxHeroImages ? ' is-full' : '' }}">
                <x-admin.nav-icon name="image" class="hero-count-badge-icon h-4 w-4" />
                <span class="text-xs font-normal">登録数</span>
                <span id="hero-image-count" class="text-sm font-semibold">{{ $heroCount }} / {{ $maxHeroImages }}枚</span>
            </p>
        </div>
    </div>

    <form id="hero-form" method="POST" action="{{ route('admin.home.hero.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        <div id="hero-deleted-ids"></div>

        {{-- メインビジュアル画像一覧（将来: キャッチコピー / リンク等の per-image フィールドを各ブロックへ追加） --}}
        @include('admin.home.partials.hero-images', [
            'heroImages' => $heroImages,
            'heroCount' => $heroCount,
            'maxHeroImages' => $maxHeroImages,
        ])
    </form>

    @include('admin.home.partials.hero-images-script', [
        'heroImages' => $heroImages,
        'maxHeroImages' => $maxHeroImages,
    ])
@endsection
