@props([
    'setting',
])

{{-- Shared public site footer (colored-scrollbar + VI). --}}
<footer {{ $attributes->class('site-footer border-t border-salon-line bg-white/50') }}>
    <div class="mx-auto max-w-6xl px-4 py-12 md:px-6">
        <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="font-serif text-lg">{{ $setting->shop_name }}</p>
                <p class="mt-2 text-sm text-salon-muted">{{ $setting->address }}</p>
            </div>
            <div class="flex flex-col items-start gap-4 text-sm sm:flex-row sm:items-center sm:gap-6">
                <a href="{{ route('privacy') }}" class="hover:text-salon-accent">Privacy Policy</a>
                <x-social-links variant="footer" />
            </div>
        </div>
        <p class="mt-8 text-center text-xs text-salon-muted">&copy; {{ date('Y') }} {{ $setting->shop_name }}</p>
    </div>
</footer>
