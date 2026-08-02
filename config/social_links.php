<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SNS / official account services
    |--------------------------------------------------------------------------
    |
    | Add a new service by appending an entry here, adding an icon branch in
    | x-social-icon, and ensuring SocialLink::ensureDefaults() (or a migration
    | seed) creates the row.
    |
    */
    'services' => [
        'instagram' => [
            'label' => 'Instagram',
            'url_label' => 'プロフィールURL',
            'placeholder' => 'https://www.instagram.com/...',
            'default_order' => 1,
        ],
        'line' => [
            'label' => 'LINE公式アカウント',
            'url_label' => '公式アカウントURL',
            'placeholder' => 'https://lin.ee/...',
            'default_order' => 2,
        ],
        'youtube' => [
            'label' => 'YouTube',
            'url_label' => 'チャンネルURL',
            'placeholder' => 'https://www.youtube.com/...',
            'default_order' => 3,
        ],
        'tiktok' => [
            'label' => 'TikTok',
            'url_label' => 'プロフィールURL',
            'placeholder' => 'https://www.tiktok.com/@...',
            'default_order' => 4,
        ],
        'facebook' => [
            'label' => 'Facebook',
            'url_label' => 'ページURL',
            'placeholder' => 'https://www.facebook.com/...',
            'default_order' => 5,
        ],
        'x' => [
            'label' => 'X（Twitter）',
            'url_label' => 'プロフィールURL',
            'placeholder' => 'https://x.com/...',
            'default_order' => 6,
        ],
        'threads' => [
            'label' => 'Threads',
            'url_label' => 'プロフィールURL',
            'placeholder' => 'https://www.threads.net/@...',
            'default_order' => 7,
        ],
    ],
];
