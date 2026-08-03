@extends('layouts.public')

@section('title', 'アクセス・店舗情報')

@section('content')
    <section class="site-section">
        <div class="mx-auto max-w-6xl px-4 md:px-6">
            <p class="mb-2 text-sm tracking-widest text-salon-accent">Access</p>
            <h1 class="section-title mb-10 md:mb-12">アクセス・店舗情報</h1>

            <x-public.store-info :setting="$setting" />
        </div>
    </section>
@endsection
