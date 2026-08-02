@extends('layouts.admin')

@section('heading', 'メインビジュアル')

@section('content')
    @php
        $heroImages = $setting->heroImages;
        $heroCount = $heroImages->count();
        $maxHeroImages = \App\Models\HeroImage::MAX_COUNT;
    @endphp

    <div class="sticky top-[4.5rem] z-10 -mx-4 -mt-4 mb-6 border-b border-admin-border/50 bg-admin-bg/95 px-4 py-3 shadow-[0_1px_0_rgba(61,56,51,0.03)] backdrop-blur-sm md:-mx-8 md:-mt-8 md:px-8">
        <div class="flex min-w-0 flex-wrap items-center gap-3">
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
            <p class="text-sm text-admin-muted">
                各項目を編集し、「保存する」でまとめて反映できます
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
