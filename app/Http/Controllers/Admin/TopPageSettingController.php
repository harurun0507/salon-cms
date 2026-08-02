<?php

namespace App\Http\Controllers\Admin;

use App\Models\SalonSetting;
use App\Models\TopPageSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TopPageSettingController extends AdminController
{
    public function edit(): View
    {
        $setting = SalonSetting::current();
        // Seed defaults on every visit so the display-count / order cards always have rows.
        $sections = TopPageSection::ensureDefaults();

        return view('admin.home.top', [
            'setting' => $setting,
            'sections' => $sections,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        TopPageSection::ensureDefaults();

        $countRules = [];
        foreach (TopPageSection::COUNT_MAX as $key => $max) {
            $countRules["sections.{$key}.display_count"] = ['required', 'integer', 'min:1', 'max:'.$max];
        }

        $validated = $request->validate(array_merge([
            'hero_label' => ['nullable', 'string', 'max:255'],
            'hero_title' => ['nullable', 'string'],
            'concept_title' => ['nullable', 'string', 'max:255'],
            'concept' => ['nullable', 'string'],
            'section_order' => ['required', 'array', 'size:'.count(TopPageSection::KEYS)],
            'section_order.*' => ['required', 'string', Rule::in(TopPageSection::KEYS), 'distinct'],
            'sections' => ['required', 'array'],
            'sections.*.is_visible' => ['nullable', 'boolean'],
        ], $countRules));

        DB::transaction(function () use ($validated) {
            SalonSetting::current()->update([
                'hero_label' => $validated['hero_label'] ?? null,
                'hero_title' => $validated['hero_title'] ?? null,
                'concept_title' => $validated['concept_title'] ?? null,
                'concept' => $validated['concept'] ?? null,
            ]);

            foreach ($validated['section_order'] as $index => $key) {
                $payload = [
                    'is_visible' => filter_var(
                        data_get($validated, "sections.{$key}.is_visible", false),
                        FILTER_VALIDATE_BOOLEAN
                    ),
                    'display_order' => $index + 1,
                ];

                if (array_key_exists($key, TopPageSection::COUNT_MAX)) {
                    $payload['display_count'] = (int) data_get($validated, "sections.{$key}.display_count");
                }

                TopPageSection::query()
                    ->where('section_key', $key)
                    ->update($payload);
            }
        });

        return redirect()->route('admin.home.top')->with('success', 'トップページ設定を更新しました。');
    }
}
