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
