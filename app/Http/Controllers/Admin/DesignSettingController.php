<?php

namespace App\Http\Controllers\Admin;

use App\Models\DesignSetting;
use App\Models\SalonSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DesignSettingController extends AdminController
{
    public function edit(): View
    {
        $design = DesignSetting::current();
        $setting = SalonSetting::current();

        return view('admin.system.design', [
            'design' => $design,
            'setting' => $setting,
            'defaults' => DesignSetting::DEFAULTS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $colorFields = [
            'primary_color',
            'secondary_color',
            'background_color',
            'text_color',
            'scrollbar_thumb_color',
            'scrollbar_track_color',
            'scrollbar_thumb_hover_color',
            'modal_overlay_color',
            'footer_background_color',
            'footer_text_color',
            'footer_link_color',
            'footer_link_hover_color',
        ];

        foreach ($colorFields as $field) {
            $request->merge([
                $field => DesignSetting::normalizeHex((string) $request->input($field, '')),
            ]);
        }

        $validated = $request->validate([
            'primary_color' => ['required', 'string', 'regex:/^#[0-9a-f]{6}$/'],
            'secondary_color' => ['required', 'string', 'regex:/^#[0-9a-f]{6}$/'],
            'background_color' => ['required', 'string', 'regex:/^#[0-9a-f]{6}$/'],
            'text_color' => ['required', 'string', 'regex:/^#[0-9a-f]{6}$/'],
            'scrollbar_thumb_color' => ['required', 'string', 'regex:/^#[0-9a-f]{6}$/'],
            'scrollbar_track_color' => ['required', 'string', 'regex:/^#[0-9a-f]{6}$/'],
            'scrollbar_thumb_hover_color' => ['required', 'string', 'regex:/^#[0-9a-f]{6}$/'],
            'scroll_display_type' => ['required', Rule::in(DesignSetting::SCROLL_DISPLAY_TYPES)],
            'news_detail_display' => ['required', Rule::in(DesignSetting::DETAIL_DISPLAY_TYPES)],
            'blog_detail_display' => ['required', Rule::in(DesignSetting::DETAIL_DISPLAY_TYPES)],
            'gallery_detail_display' => ['required', Rule::in(DesignSetting::DETAIL_DISPLAY_TYPES)],
            'modal_overlay_style' => ['required', Rule::in(DesignSetting::MODAL_OVERLAY_STYLES)],
            'modal_overlay_color' => ['required', 'string', 'regex:/^#[0-9a-f]{6}$/'],
            'footer_background_color' => ['required', 'string', 'regex:/^#[0-9a-f]{6}$/'],
            'footer_text_color' => ['required', 'string', 'regex:/^#[0-9a-f]{6}$/'],
            'footer_link_color' => ['required', 'string', 'regex:/^#[0-9a-f]{6}$/'],
            'footer_link_hover_color' => ['required', 'string', 'regex:/^#[0-9a-f]{6}$/'],
            'heading_font' => ['required', Rule::in(DesignSetting::FONTS)],
            'body_font' => ['required', Rule::in(DesignSetting::FONTS)],
            'button_radius' => ['required', Rule::in(DesignSetting::RADII)],
            'card_radius' => ['required', Rule::in(DesignSetting::RADII)],
            'layout_density' => ['required', Rule::in(DesignSetting::DENSITIES)],
        ], [
            'primary_color.regex' => 'カラーは #RRGGBB 形式で入力してください。',
            'secondary_color.regex' => 'カラーは #RRGGBB 形式で入力してください。',
            'background_color.regex' => 'カラーは #RRGGBB 形式で入力してください。',
            'text_color.regex' => 'カラーは #RRGGBB 形式で入力してください。',
            'scrollbar_thumb_color.regex' => 'カラーは #RRGGBB 形式で入力してください。',
            'scrollbar_track_color.regex' => 'カラーは #RRGGBB 形式で入力してください。',
            'scrollbar_thumb_hover_color.regex' => 'カラーは #RRGGBB 形式で入力してください。',
            'modal_overlay_color.regex' => 'カラーは #RRGGBB 形式で入力してください。',
            'footer_background_color.regex' => 'カラーは #RRGGBB 形式で入力してください。',
            'footer_text_color.regex' => 'カラーは #RRGGBB 形式で入力してください。',
            'footer_link_color.regex' => 'カラーは #RRGGBB 形式で入力してください。',
            'footer_link_hover_color.regex' => 'カラーは #RRGGBB 形式で入力してください。',
            'scroll_display_type.in' => 'スクロール表示の値が不正です。',
            'news_detail_display.in' => 'お知らせの詳細表示方式が不正です。',
            'blog_detail_display.in' => 'ブログの詳細表示方式が不正です。',
            'gallery_detail_display.in' => 'ギャラリーの詳細表示方式が不正です。',
            'modal_overlay_style.in' => 'モーダル背景の値が不正です。',
            'heading_font.in' => '見出しフォントの値が不正です。',
            'body_font.in' => '本文フォントの値が不正です。',
            'button_radius.in' => 'ボタンの角丸の値が不正です。',
            'card_radius.in' => 'カードの角丸の値が不正です。',
            'layout_density.in' => 'レイアウト密度の値が不正です。',
        ]);

        DesignSetting::current()->update($validated);

        return redirect()->route('admin.system.design')->with('success', 'デザイン設定を更新しました。');
    }
}
