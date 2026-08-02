<?php

return [
    'items' => [
        [
            'type' => 'link',
            'label' => 'ダッシュボード',
            'route' => 'admin.dashboard',
            'active' => 'admin.dashboard',
            'icon' => 'dashboard',
        ],
        [
            'type' => 'group',
            'key' => 'home',
            'label' => 'ホームページ',
            'icon' => 'home-page',
            'children' => [
                [
                    'label' => 'メインビジュアル',
                    'route' => 'admin.home.hero',
                    'active' => 'admin.home.hero*',
                    'icon' => 'image',
                ],
                [
                    'label' => 'トップページ設定',
                    'route' => 'admin.home.top',
                    'active' => 'admin.home.top*',
                    'icon' => 'document',
                ],
                [
                    'label' => 'バナー',
                    'route' => 'admin.home.banners',
                    'active' => 'admin.home.banners*',
                    'icon' => 'megaphone',
                ],
            ],
        ],
        [
            'type' => 'group',
            'key' => 'content',
            'label' => 'コンテンツ',
            'icon' => 'content',
            'children' => [
                [
                    'label' => 'お知らせ',
                    'route' => 'admin.news.index',
                    'active' => 'admin.news.*',
                    'icon' => 'bell',
                ],
                [
                    'label' => 'ギャラリー',
                    'route' => 'admin.galleries.index',
                    'active' => 'admin.galleries.*',
                    'icon' => 'gallery',
                ],
                [
                    'label' => 'メニュー',
                    'route' => 'admin.menus.index',
                    'active' => 'admin.menus.*',
                    'icon' => 'list',
                ],
                [
                    'label' => 'スタッフ',
                    'route' => 'admin.staff.index',
                    'active' => 'admin.staff.*',
                    'icon' => 'user',
                ],
            ],
        ],
        [
            'type' => 'group',
            'key' => 'store',
            'label' => '店舗情報',
            'icon' => 'settings',
            'children' => [
                [
                    'label' => '基本情報',
                    'route' => 'admin.settings.edit',
                    'active' => 'admin.settings.*',
                    'icon' => 'home',
                ],
                [
                    'label' => 'SNS',
                    'route' => 'admin.store.sns',
                    'active' => 'admin.store.sns*',
                    'icon' => 'share',
                ],
                [
                    'label' => '予約設定',
                    'route' => 'admin.store.reservations',
                    'active' => 'admin.store.reservations*',
                    'icon' => 'calendar',
                ],

            ],
        ],
        [
            'type' => 'group',
            'key' => 'system',
            'label' => 'システム',
            'icon' => 'system',
            'children' => [
                [
                    'label' => 'SEO',
                    'route' => 'admin.system.seo',
                    'active' => 'admin.system.seo',
                    'icon' => 'search',
                ],
                [
                    'label' => '管理ユーザー',
                    'route' => 'admin.system.users',
                    'active' => 'admin.system.users',
                    'icon' => 'users',
                ],
                [
                    'label' => 'デザイン設定',
                    'route' => 'admin.system.design',
                    'active' => 'admin.system.design',
                    'icon' => 'palette',
                ],
            ],
        ],
    ],
    'external' => [
        'label' => '公開サイトを見る',
        'route' => 'home',
        'icon' => 'external',
        'target' => '_blank',
    ],
];
