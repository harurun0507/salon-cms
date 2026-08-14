@extends('layouts.public')

@section('title', 'スタッフ紹介')

@section('content')
    <section class="site-section">
        <div class="mx-auto max-w-5xl px-4 md:px-6">
            <x-public.list-page-header
                eyebrow="Staff"
                title="スタッフ紹介"
                :back-href="\App\Models\TopPageSection::listPageBackHref('staff')"
            />

            <div class="grid gap-12 md:grid-cols-2">
                @forelse($staffMembers as $member)
                    <article class="flex flex-col gap-6 sm:flex-row">
                        <div class="h-56 w-full shrink-0 overflow-hidden rounded-sm bg-salon-line sm:w-48">
                            @if($member->photo_path)
                                <img src="{{ asset('storage/'.$member->photo_path) }}" alt="{{ $member->name }}" class="h-full w-full object-cover">
                            @endif
                        </div>
                        <div>
                            <h2 class="text-xl font-medium">{{ $member->name }}</h2>
                            @if($member->role)
                                <p class="mt-1 text-sm text-salon-accent">{{ $member->role }}</p>
                            @endif
                            @if($member->profile)
                                <p class="mt-4 whitespace-pre-line text-sm leading-7 text-salon-muted">{{ $member->profile }}</p>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="text-salon-muted">スタッフ情報を準備中です。</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
