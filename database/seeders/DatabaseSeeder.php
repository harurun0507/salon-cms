<?php

namespace Database\Seeders;

use App\Models\Gallery;
use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\News;
use App\Models\SalonSetting;
use App\Models\SocialLink;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@salon.local'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );

        SalonSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'shop_name' => 'Sun＆ Me',
                'hero_label' => 'Personal Hair Salon',
                'hero_title' => "あなたらしさに、\n少しだけ今っぽさを。",
                'concept_title' => 'ナチュラルに、自分らしく。',
                'concept' => "一人ひとりの髪質やライフスタイルに合わせた、丁寧なカウンセリングと施術を大切にしています。\n\nナチュラルで扱いやすいスタイルを、あなたらしく。",
                'address' => '埼玉県川口市幸町２－14－27－102号',
                'access_directions' => '銀座通り商店街（樹モール）を抜けてふじのいち商店街をアリオ方面に進み、市役所通りに出ましたら右手をご覧いただくとピンクとグリーンのテナントビルがございます。アースサポートさんのお隣、1階、店舗上の黒い看板が目印です。',
                'business_hours' => "平日 10:00 - 20:00\n土日祝 9:00 - 19:00",
                'closed_days' => '毎週火曜日・第3水曜日',
                'phone' => '0120-111-1111',
                'payment_methods' => 'Visa／Mastercard／JCB／American Express／Diners Club／Discover',
                'cut_price' => '¥5,940',
                'seat_count' => 'セット面3席',
                'staff_count' => 'スタイリスト1人',
                'parking' => "なし\n近隣のパーキングをご利用ください",
                'commitment_conditions' => '4席以下の小型サロン／夜19時以降も受付OK／1人のスタイリストが仕上げまで担当／ヘアセット／店頭でのカード支払いOK／お子さま同伴OK／禁煙／DVDが視聴できる',
                'notes' => '施術中につきお電話に出られない場合がございます。留守録にお名前・ご連絡先・ご用件を残していただければ、折り返しご連絡いたします。',
                'other_info' => 'ポイント利用OK、即時予約OK、メンズにもオススメ',
                'google_map_url' => 'https://maps.app.goo.gl/EWjyqDYPWgRaWG219',
                'google_map_embed_url' => 'https://maps.google.com/maps?q='.urlencode('埼玉県川口市幸町２－14－27－102号').'&output=embed',
                'instagram_url' => 'https://instagram.com/',
                'hot_pepper_url' => 'https://beauty.hotpepper.jp/slnH000428792/?wak=CPMY100402_link_reservesalon_salon_beauty_20220223',
            ]
        );

        SocialLink::ensureDefaults();

        $categories = [
            'カット' => 1,
            'カラー' => 2,
            'パーマ' => 3,
            '縮毛矯正' => 4,
            'トリートメント' => 5,
            'ヘッドスパ' => 6,
        ];

        $categoryModels = [];
        foreach ($categories as $name => $sortOrder) {
            $categoryModels[$name] = MenuCategory::query()->updateOrCreate(
                ['name' => $name],
                ['sort_order' => $sortOrder]
            );
        }

        $menus = [
            'カット' => [
                [
                    'name' => 'カット',
                    'price' => '¥5,940',
                    'description' => 'ナチュラルで軽やかな毛束感の表現☆似合う長さをイメージしながら☆SB込',
                ],
                [
                    'name' => '高校生　大学生　学割カット',
                    'price' => '¥5,500',
                    'description' => '高校生～大学生までご利用ください☆',
                ],
                [
                    'name' => 'ジュニアカット',
                    'price' => '¥4,950',
                    'description' => '中学生までご利用いただけます☆',
                ],
                [
                    'name' => 'キッズカット＊10歳まで ￥3960',
                    'price' => '要問い合わせ',
                    'description' => '10歳までのお子様カット☆原則お子様のシャンプーはなしでお願いしています＊ご予約はお電話にてお願いいたします',
                ],
                [
                    'name' => '前髪カット ￥1650',
                    'price' => '要問い合わせ',
                    'description' => '気になればいつでもご連絡ください☆S・Bは含みません',
                ],
            ],
            'カラー' => [
                [
                    'name' => 'ヘアカラー',
                    'price' => '¥7,700～',
                    'description' => '肌色や髪質に合わせてご提案させていただきます☆カラーブランドにより別途料金あり　ロング料金1100～　SB別途1650',
                ],
                [
                    'name' => 'ヘアマニキュア',
                    'price' => '¥8,250～',
                    'description' => '酸性コーティングカラーで艶感を☆細くなってきた髪にもオススメです☆ノンダメージ☆ハリとコシが蘇りますロング料金1100～SB別途1650',
                ],
                [
                    'name' => 'ケアブリーチ',
                    'price' => '要問い合わせ',
                    'description' => 'ハイトーンカラーのベース作り☆ワンブリーチの料金になります☆ロング1100～SB別途1650',
                ],
            ],
            'パーマ' => [
                [
                    'name' => 'パーマ',
                    'price' => '¥8,800～',
                    'description' => 'ウェーブやカールデザインの表現☆乾かしただけでキマル☆軽やかに動く毛束感 スタイリングも楽になります　ロング料金1100～SB別途1650',
                ],
                [
                    'name' => 'デジタルパーマ',
                    'price' => '¥12,980～',
                    'description' => 'かかりづらい髪質でもしっかりカール☆艶と柔らかい印象のヘアデザインに☆ロング料金1100～SB別途1650',
                ],
            ],
            '縮毛矯正' => [
                [
                    'name' => '縮毛矯正',
                    'price' => '¥16,940～',
                    'description' => 'ナチュラルでやわらかい質感の仕上がりに☆ロング料金1100～SB別途1650',
                ],
                [
                    'name' => 'ポイント前髪縮毛矯正',
                    'price' => '¥8,800～',
                    'description' => '前髪だけ等気になる部分に施術する部分縮毛矯正です☆かける範囲に応じてプラス料金あり',
                ],
            ],
            'トリートメント' => [
                [
                    'name' => 'ダメージ、エイジングによるクセ改善　Link酸熱トリートメント ホームケア付',
                    'price' => '¥13,200～',
                    'description' => '髪を補修しながら＜うねり＞＜広がり＞＜ぱさつき＞解消　つややかな髪が持続するトリートメント☆エイジングが気になる髪にも☆ロング料金1100～SB込',
                ],
                [
                    'name' => 'Link髪質改善トリートメント　ホームケア付',
                    'price' => '¥5,940',
                    'description' => 'ハリや弾力が欲しい髪、しっとりとやわらかい質感にしたい髪、お悩み毎にアプローチするパーソナルトリートメント☆SB1650',
                ],
                [
                    'name' => 'Link高保湿トリートメント',
                    'price' => '¥3,960',
                    'description' => 'ゼロタイムでもしっかり補修☆月１のメンテナンスに☆SB別途1650',
                ],
            ],
            'ヘッドスパ' => [
                [
                    'name' => 'リラックスヘッドスパ',
                    'price' => '¥6,600',
                    'description' => 'フラットブースで行うリラクゼーションメニュー☆頭皮環境を整え、健康的なヘアサイクルへSB別途1650',
                ],
            ],
        ];

        foreach ($menus as $categoryName => $items) {
            $category = $categoryModels[$categoryName];
            foreach ($items as $index => $item) {
                Menu::query()->updateOrCreate(
                    [
                        'menu_category_id' => $category->id,
                        'name' => $item['name'],
                    ],
                    [
                        'price' => $item['price'],
                        'description' => $item['description'],
                        'sort_order' => $index + 1,
                        'is_published' => true,
                    ]
                );
            }
        }

        StaffMember::query()->updateOrCreate(
            ['name' => 'タナカ コウヘイ'],
            [
                'role' => 'オーナースタイリスト',
                'profile' => '☆つくる事に誠実に☆よろしくお願いします。',
                'sort_order' => 1,
                'is_published' => true,
            ]
        );

        News::query()->updateOrCreate(
            ['slug' => 'grand-open'],
            [
                'title' => 'Webサイトを公開しました',
                'body' => "Sun＆ Meの公式Webサイトを公開いたしました。\n\n最新のお知らせやメニュー情報はこちらからご確認ください。",
                'published_at' => now(),
                'is_published' => true,
            ]
        );
    }
}
