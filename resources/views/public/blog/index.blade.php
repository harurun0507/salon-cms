@extends('layouts.public')

@section('title', 'ブログ')

@section('content')
    @php
        $design = $design ?? \App\Models\DesignSetting::current();
        $useBlogModal = $design->usesBlogDetailModal();
    @endphp
    <section class="site-section">
        <div class="mx-auto max-w-6xl px-4 md:px-6">
            <x-public.list-page-header
                eyebrow="Blog"
                title="ブログ"
                :back-href="\App\Models\TopPageSection::listPageBackHref('blog')"
            />

            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @forelse($blogs as $blog)
                    <a
                        href="{{ route('blog.show', $blog->slug) }}"
                        class="group block min-w-0"
                        @if($useBlogModal)
                            data-blog-modal-trigger
                            data-blog-id="{{ $blog->id }}"
                        @endif
                    >
                        <x-public.blog-eyecatch :blog="$blog" />
                        <p class="blog-card-title mt-3">{{ $blog->title }}</p>
                        <time class="blog-card-date mt-1.5 block">{{ $blog->published_at?->format('Y.m.d') }}</time>
                    </a>
                @empty
                    <p class="col-span-full text-salon-muted">ブログ記事はまだありません。</p>
                @endforelse
            </div>

            <div class="mt-10">{{ $blogs->links() }}</div>
        </div>
    </section>

    @if($useBlogModal && $blogs->isNotEmpty())
        <x-public.blog-modal :blogs="$blogs" />
    @endif
@endsection
