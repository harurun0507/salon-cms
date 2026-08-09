@extends('layouts.public')

@section('title', 'キャンペーン')

@section('content')
    <section class="site-section">
        <div class="mx-auto max-w-6xl px-4 md:px-6">
            <x-back-link :href="route('home')">トップページへ戻る</x-back-link>

            <div class="mb-12 mt-6 text-center">
                <p class="mb-2 text-sm tracking-widest text-salon-accent">Campaign</p>
                <h1 class="section-title">キャンペーン</h1>
            </div>

            @include('public.partials.campaign-cards', ['banners' => $banners])
        </div>
    </section>
@endsection
