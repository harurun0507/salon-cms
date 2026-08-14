@php
    $members = collect($staffMembers ?? [])->take(3)->values();
    $memberCount = $members->count();
    $stageLayout = match ($memberCount) {
        0 => 'empty',
        1 => 'solo',
        2 => 'duo',
        default => 'trio',
    };
@endphp

<section id="staff" class="home-vi-staff" aria-label="Staff">
    <div class="home-vi-staff__intro">
        <p class="home-vi-kicker">Staff</p>
        <h2 class="home-vi-staff__title">スタッフ紹介</h2>
    </div>

    @if($members->isNotEmpty())
        <div class="home-vi-staff__stage home-vi-staff__stage--{{ $stageLayout }}">
            @foreach($members as $member)
                <article @class([
                    'home-vi-staff__panel',
                    'home-vi-staff__panel--solo' => $stageLayout === 'solo',
                ])>
                    <div class="home-vi-staff__photo">
                        @if($member->photo_path)
                            <img
                                src="{{ asset('storage/'.$member->photo_path) }}"
                                alt="{{ $member->name }}"
                                class="home-vi-staff__image"
                            >
                        @else
                            <div class="home-vi-staff__placeholder" aria-hidden="true"></div>
                        @endif
                        @if($stageLayout !== 'solo')
                            <div class="home-vi-staff__shade"></div>
                            <div class="home-vi-staff__meta">
                                @if($member->role)
                                    <p class="home-vi-staff__role">{{ $member->role }}</p>
                                @endif
                                <h3 class="home-vi-staff__name">{{ $member->name }}</h3>
                            </div>
                        @endif
                    </div>

                    @if($stageLayout === 'solo')
                        <div class="home-vi-staff__profile">
                            @if($member->role)
                                <p class="home-vi-staff__role home-vi-staff__role--on-light">{{ $member->role }}</p>
                            @endif
                            <h3 class="home-vi-staff__name home-vi-staff__name--on-light">{{ $member->name }}</h3>
                            @if(filled($member->profile))
                                <p class="home-vi-staff__bio">{{ $member->profile }}</p>
                            @endif
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    @else
        <p class="home-vi-staff__empty">スタッフ情報準備中です。</p>
    @endif

    <div class="home-vi-staff__footer">
        <x-section-more-link :href="route('staff')">すべて見る →</x-section-more-link>
    </div>
</section>
