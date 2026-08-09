@extends('layouts.admin')

@section('heading', 'ダッシュボード')

@section('content')
    @php
        $statusBadge = function (string $tone, string $label): string {
            return '<span class="dashboard-status-badge '.$tone.'">'.e($label).'</span>';
        };

        $isAdmin = auth()->user()?->isAdmin() ?? false;
        $publicHost = parse_url($siteStatus['public_url'], PHP_URL_HOST) ?: $siteStatus['public_url'];

        $siteStatusItems = [
            [
                'label' => '公開サイト',
                'value' => '表示可能',
                'tone' => 'is-set',
                'meta' => $publicHost,
                'url' => $siteStatus['public_url'],
                'external' => true,
            ],
            [
                'label' => '検索エンジン',
                'value' => $siteStatus['indexing'] ? 'インデックスする' : 'インデックスしない',
                'tone' => $siteStatus['indexing'] ? 'is-set' : 'is-caution',
                'meta' => null,
                'url' => $isAdmin ? route('admin.system.seo') : null,
                'external' => false,
            ],
            [
                'label' => 'アクセス解析',
                'value' => $siteStatus['ga_configured'] ? '設定済み' : '未設定',
                'tone' => $siteStatus['ga_configured'] ? 'is-set' : 'is-unset',
                'meta' => null,
                'url' => $isAdmin ? route('admin.system.analytics') : null,
                'external' => false,
            ],
            [
                'label' => '予約設定',
                'value' => $siteStatus['reservation_configured'] ? '設定済み' : '未設定',
                'tone' => $siteStatus['reservation_configured'] ? 'is-set' : 'is-unset',
                'meta' => null,
                'url' => route('admin.store.reservations'),
                'external' => false,
            ],
            [
                'label' => 'SNS',
                'value' => $siteStatus['sns_configured'] ? '設定済み' : '未設定',
                'tone' => $siteStatus['sns_configured'] ? 'is-set' : 'is-unset',
                'meta' => null,
                'url' => route('admin.store.sns'),
                'external' => false,
            ],
        ];
    @endphp

    {{-- 1. Site status --}}
    <section class="admin-card dashboard-section">
        <div class="dashboard-section-header">
            <div class="dashboard-section-heading">
                <span class="dashboard-section-icon" aria-hidden="true">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
                        <path d="M2 12h20" />
                    </svg>
                </span>
                <div>
                    <h2 class="dashboard-section-title">サイトの状態</h2>
                </div>
            </div>
            <a
                href="{{ $siteStatus['public_url'] }}"
                target="_blank"
                rel="noopener noreferrer"
                class="admin-btn-secondary inline-flex items-center gap-2"
            >
                <x-admin.nav-icon name="external" class="h-4 w-4 text-admin-icon" />
                公開サイトを見る
            </a>
        </div>

        <div class="dashboard-status-grid">
            @foreach ($siteStatusItems as $statusItem)
                @php
                    $isClickable = filled($statusItem['url']);
                @endphp
                @if ($isClickable)
                    <a
                        href="{{ $statusItem['url'] }}"
                        @if ($statusItem['external'])
                            target="_blank"
                            rel="noopener noreferrer"
                        @endif
                        class="dashboard-status-chip {{ $statusItem['tone'] }} is-link"
                    >
                @else
                    <div class="dashboard-status-chip {{ $statusItem['tone'] }}">
                @endif
                    <span class="dashboard-status-chip-icon" aria-hidden="true">
                        @if ($statusItem['tone'] === 'is-set')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 8v4" />
                                <path d="M12 16h.01" />
                            </svg>
                        @endif
                    </span>
                    <div class="dashboard-status-chip-body min-w-0">
                        <p class="dashboard-status-chip-label">{{ $statusItem['label'] }}</p>
                        <p class="dashboard-status-chip-value">
                            @if ($statusItem['tone'] === 'is-caution')
                                <span class="dashboard-status-badge is-caution">{{ $statusItem['value'] }}</span>
                            @else
                                {{ $statusItem['value'] }}
                            @endif
                        </p>
                        @if ($statusItem['meta'])
                            <p class="dashboard-status-chip-meta truncate" title="{{ $statusItem['meta'] }}">{{ $statusItem['meta'] }}</p>
                        @endif
                    </div>
                    @if ($isClickable)
                        <span class="dashboard-status-chip-nav shrink-0" aria-hidden="true">
                            @if ($statusItem['external'])
                                <x-admin.nav-icon name="external" class="h-3.5 w-3.5" />
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m9 18 6-6-6-6" />
                                </svg>
                            @endif
                        </span>
                    @endif
                @if ($isClickable)
                    </a>
                @else
                    </div>
                @endif
            @endforeach
        </div>
    </section>

    {{-- 2. Count cards --}}
    <section class="dashboard-count-grid">
        @php
            $countCards = [
                [
                    'label' => 'お知らせ',
                    'icon' => 'news',
                    'counts' => $newsCounts,
                    'route' => route('admin.news.index'),
                ],
                [
                    'label' => 'ギャラリー',
                    'icon' => 'gallery',
                    'counts' => $galleryCounts,
                    'route' => route('admin.galleries.index'),
                ],
                [
                    'label' => 'メニュー',
                    'icon' => 'menus',
                    'counts' => $menuCounts,
                    'route' => route('admin.menus.index'),
                ],
                [
                    'label' => 'スタッフ',
                    'icon' => 'staff',
                    'counts' => $staffCounts,
                    'route' => route('admin.staff.index'),
                ],
            ];
        @endphp

        @foreach ($countCards as $card)
            <a href="{{ $card['route'] }}" class="admin-card dashboard-count-card">
                <div class="dashboard-count-card-top">
                    <span class="dashboard-count-card-icon" aria-hidden="true">
                        <x-admin.nav-icon :name="$card['icon']" class="h-4 w-4" />
                    </span>
                    <p class="dashboard-count-card-title">{{ $card['label'] }}</p>
                </div>
                <div class="dashboard-count">
                    <span class="dashboard-count-number">{{ $card['counts']['total'] }}</span>
                    <span class="dashboard-count-unit">件</span>
                </div>
                <div class="dashboard-count-card-badges">
                    {!! $statusBadge('is-active', '公開 '.$card['counts']['published']) !!}
                    {!! $statusBadge('is-unset', '非公開 '.$card['counts']['unpublished']) !!}
                </div>
            </a>
        @endforeach
    </section>

    {{-- 3 & 4. Recent updates | Attention items --}}
    <section class="dashboard-split-grid">
        <div class="admin-card dashboard-section">
            <div class="dashboard-section-heading">
                <span class="dashboard-section-icon" aria-hidden="true">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                </span>
                <div>
                    <h2 class="dashboard-section-title">最近の更新</h2>
                    <p class="dashboard-section-desc">コンテンツの直近の変更です</p>
                </div>
            </div>

            @if (count($recentUpdates) === 0)
                <p class="dashboard-empty-text">まだ更新されたコンテンツはありません。</p>
            @else
                <ul class="dashboard-recent-list">
                    @foreach ($recentUpdates as $item)
                        @php
                            $displayTitle = trim((string) ($item['title'] ?? ''));
                            $hasDistinctTitle = $displayTitle !== '' && $displayTitle !== $item['type_label'];
                        @endphp
                        <li>
                            <a href="{{ $item['edit_url'] }}" class="dashboard-recent-row">
                                <div class="dashboard-recent-main min-w-0">
                                    <div class="dashboard-recent-badges">
                                        <span class="dashboard-type-badge">{{ $item['type_label'] }}</span>
                                        @if ($item['is_published'])
                                            {!! $statusBadge('is-active', '公開') !!}
                                        @else
                                            {!! $statusBadge('is-unset', '非公開') !!}
                                        @endif
                                        @if ($hasDistinctTitle)
                                            <span class="dashboard-recent-title truncate">{{ $displayTitle }}</span>
                                        @endif
                                    </div>
                                </div>
                                <p class="dashboard-recent-time shrink-0">{{ $item['updated_at']->format('n月j日 G:i') }}</p>
                                <span class="dashboard-recent-chevron shrink-0" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m9 18 6-6-6-6" />
                                    </svg>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="admin-card dashboard-section dashboard-attention-card">
            <div class="dashboard-section-heading">
                <span class="dashboard-section-icon" aria-hidden="true">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 11l3 3L22 4" />
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                    </svg>
                </span>
                <div>
                    <h2 class="dashboard-section-title">確認が必要な項目</h2>
                    <p class="dashboard-section-desc">公開前に確認しておきたい設定です</p>
                </div>
            </div>

            @if (count($attentionItems) === 0)
                <div class="dashboard-attention-clear">
                    <p>現在、確認が必要な項目はありません。</p>
                </div>
            @else
                <ul class="dashboard-attention-list">
                    @foreach ($attentionItems as $item)
                        <li>
                            <a href="{{ $item['url'] }}" class="dashboard-attention-item">
                                <span class="dashboard-attention-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3" />
                                        <path d="M12 9v4" />
                                        <path d="M12 17h.01" />
                                    </svg>
                                </span>
                                <span class="dashboard-attention-body min-w-0">
                                    <span class="dashboard-attention-label">{{ $item['label'] }}</span>
                                    @if (! empty($item['secondary']))
                                        <span class="dashboard-attention-secondary">{{ $item['secondary'] }}</span>
                                    @endif
                                </span>
                                <span class="dashboard-attention-chevron shrink-0" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m9 18 6-6-6-6" />
                                    </svg>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    {{-- 5. Quick actions --}}
    <section class="admin-card dashboard-section">
        <div class="dashboard-section-heading">
            <span class="dashboard-section-icon" aria-hidden="true">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14" />
                    <path d="M12 5v14" />
                </svg>
            </span>
            <div>
                <h2 class="dashboard-section-title">クイックアクション</h2>
                <p class="dashboard-section-desc">よく使う追加画面へすぐ移動できます</p>
            </div>
        </div>

        <div class="dashboard-quick-grid">
            <a href="{{ route('admin.news.index') }}" class="dashboard-quick-action">
                <span class="dashboard-quick-action-icon" aria-hidden="true">
                    <x-admin.nav-icon name="news" class="h-4 w-4" />
                </span>
                お知らせ追加
            </a>
            <a href="{{ route('admin.galleries.create') }}" class="dashboard-quick-action">
                <span class="dashboard-quick-action-icon" aria-hidden="true">
                    <x-admin.nav-icon name="gallery" class="h-4 w-4" />
                </span>
                ギャラリー追加
            </a>
            <a href="{{ route('admin.home.banners') }}" class="dashboard-quick-action">
                <span class="dashboard-quick-action-icon" aria-hidden="true">
                    <x-admin.nav-icon name="megaphone" class="h-4 w-4" />
                </span>
                キャンペーン追加
            </a>
            <a href="{{ route('admin.staff.index') }}" class="dashboard-quick-action">
                <span class="dashboard-quick-action-icon" aria-hidden="true">
                    <x-admin.nav-icon name="staff" class="h-4 w-4" />
                </span>
                スタッフ追加
            </a>
        </div>
    </section>
@endsection
