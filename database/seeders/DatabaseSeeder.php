<?php

namespace Database\Seeders;

use App\Models\Gallery;
use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\News;
use App\Models\SalonSetting;
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
                'business_hours' => "平日 10:00 - 20:00\n土日祝 9:00 - 19:00",
                'closed_days' => '毎週火曜日・第3水曜日',
                'phone' => '0120-111-1111',
                'google_map_url' => 'https://maps.app.goo.gl/EWjyqDYPWgRaWG219',
                'google_map_embed_url' => 'https://maps.google.com/maps?q='.urlencode('埼玉県川口市幸町２－14－27－102号').'&output=embed',
                'instagram_url' => 'https://instagram.com/',
                'hot_pepper_url' => 'https://beauty.hotpepper.jp/slnH000428792/?wak=CPMY100402_link_reservesalon_salon_beauty_20220223',
            ]
        );

        $cut = MenuCategory::query()->updateOrCreate(['name' => 'カット'], ['sort_order' => 1]);
        $color = MenuCategory::query()->updateOrCreate(['name' => 'カラー'], ['sort_order' => 2]);

        Menu::query()->updateOrCreate(
            ['menu_category_id' => $cut->id, 'name' => 'カット'],
            ['price' => 5500, 'description' => 'シャンプー・ブロー込み', 'sort_order' => 1, 'is_published' => true]
        );
        Menu::query()->updateOrCreate(
            ['menu_category_id' => $cut->id, 'name' => 'カット + トリートメント'],
            ['price' => 7700, 'description' => 'ダメージケア込み', 'sort_order' => 2, 'is_published' => true]
        );
        Menu::query()->updateOrCreate(
            ['menu_category_id' => $color->id, 'name' => 'カラー'],
            ['price' => 8800, 'description' => 'カット・シャンプー・ブロー込み', 'sort_order' => 1, 'is_published' => true]
        );

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
