@props([
    'banners',
])

<div class="grid grid-cols-1 gap-8 md:grid-cols-2 md:gap-x-8 md:gap-y-10">
    @forelse($banners as $banner)
        @php
            $alt = $banner->altTextOrTitle();
            $imgClass = 'aspect-[3/1] h-full w-full object-cover transition duration-500 ease-out';
        @endphp
        @if($banner->hasLink())
            <a
                href="{{ $banner->link_url }}"
                @if($banner->opensInNewTab()) target="_blank" rel="noopener noreferrer" @endif
                class="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-salon-accent focus-visible:ring-offset-2"
            >
                <div class="overflow-hidden rounded-sm bg-salon-line">
                    <img
                        src="{{ asset('storage/'.$banner->image_path) }}"
                        alt="{{ $alt }}"
                        class="{{ $imgClass }} group-hover:scale-[1.02]"
                    >
                </div>
                @if($banner->title)
                    <p class="mt-4 text-left font-medium leading-relaxed text-salon-text transition group-hover:text-salon-accent">
                        {{ $banner->title }}
                    </p>
                @endif
            </a>
        @else
            <article>
                <div class="overflow-hidden rounded-sm bg-salon-line">
                    <img
                        src="{{ asset('storage/'.$banner->image_path) }}"
                        alt="{{ $alt }}"
                        class="{{ $imgClass }}"
                    >
                </div>
                @if($banner->title)
                    <p class="mt-4 text-left font-medium leading-relaxed text-salon-text">
                        {{ $banner->title }}
                    </p>
                @endif
            </article>
        @endif
    @empty
        <p class="col-span-full text-center text-salon-muted">キャンペーン準備中です。</p>
    @endforelse
</div>
