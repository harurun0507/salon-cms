<?php

namespace App\Http\Controllers\Admin;

use App\Models\SalonSetting;
use App\Support\GoogleMapEmbedUrl;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SalonSettingController extends AdminController
{
    public function edit(): View
    {
        $setting = SalonSetting::current()->load(['closedWeekdays', 'closedNthWeekdays']);
        $weekdayLabels = SalonSetting::WEEKDAY_SHORT_LABELS;

        return view('admin.settings.edit', compact('setting', 'weekdayLabels'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge([
            'google_map_embed_url' => GoogleMapEmbedUrl::normalize($request->input('google_map_embed_url')),
        ]);

        $this->normalizeBusinessHourInputs($request);

        $validator = Validator::make($request->all(), [
            'shop_name' => ['required', 'string', 'max:255'],
            'shop_name_display_type' => ['required', Rule::in([SalonSetting::DISPLAY_TYPE_TEXT, SalonSetting::DISPLAY_TYPE_LOGO])],
            'logo_alt_text' => ['nullable', 'string', 'max:255'],
            'logo_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'address' => ['nullable', 'string', 'max:255'],
            'access_directions' => ['nullable', 'string'],
            'weekday_open_time' => ['nullable', 'date_format:H:i'],
            'weekday_close_time' => ['nullable', 'date_format:H:i'],
            'weekend_open_time' => ['nullable', 'date_format:H:i'],
            'weekend_close_time' => ['nullable', 'date_format:H:i'],
            'closed_weekdays' => ['nullable', 'array'],
            'closed_weekdays.*' => ['integer', 'between:0,6'],
            'closed_nth' => ['nullable', 'array'],
            'closed_nth.*.week' => ['required', 'integer', 'between:1,5'],
            'closed_nth.*.weekday' => ['required', 'integer', 'between:0,6'],
            'phone' => ['nullable', 'string', 'max:50'],
            'payment_methods' => ['nullable', 'string'],
            'cut_price' => ['nullable', 'string', 'max:255'],
            'seat_count' => ['nullable', 'string', 'max:255'],
            'staff_count' => ['nullable', 'string', 'max:255'],
            'parking' => ['nullable', 'string'],
            'commitment_conditions' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'other_info' => ['nullable', 'string'],
            'google_map_url' => ['nullable', 'url', 'max:500'],
            'google_map_embed_url' => ['nullable', 'string', 'url'],
        ], [
            'weekday_open_time.date_format' => '開店時間の形式が正しくありません。',
            'weekday_close_time.date_format' => '閉店時間の形式が正しくありません。',
            'weekend_open_time.date_format' => '開店時間の形式が正しくありません。',
            'weekend_close_time.date_format' => '閉店時間の形式が正しくありません。',
            'closed_nth.*.week.required' => '追加定休日の週を選択してください。',
            'closed_nth.*.weekday.required' => '追加定休日の曜日を選択してください。',
        ]);

        $validator->after(function ($validator) use ($request) {
            $this->validateBusinessHourPair(
                $validator,
                $request,
                'weekday_open_time',
                'weekday_close_time',
                '平日'
            );
            $this->validateBusinessHourPair(
                $validator,
                $request,
                'weekend_open_time',
                'weekend_close_time',
                '土日祝'
            );
        });

        $validated = $validator->validate();

        $setting = SalonSetting::current();

        DB::transaction(function () use ($request, $validated, $setting) {
            $setting->update([
                ...collect($validated)->except([
                    'logo_image',
                    'closed_weekdays',
                    'closed_nth',
                ])->all(),
                'weekday_open_time' => $this->nullableTime($validated['weekday_open_time'] ?? null),
                'weekday_close_time' => $this->nullableTime($validated['weekday_close_time'] ?? null),
                'weekend_open_time' => $this->nullableTime($validated['weekend_open_time'] ?? null),
                'weekend_close_time' => $this->nullableTime($validated['weekend_close_time'] ?? null),
                'logo_image' => $this->storeImage($request->file('logo_image'), 'settings/logos', $setting->logo_image),
            ]);

            $this->syncClosedDays($setting, $validated);
        });

        return redirect()->route('admin.settings.edit')->with('success', '店舗情報を更新しました。');
    }

    public function destroyLogo(): RedirectResponse
    {
        $setting = SalonSetting::current();

        if ($setting->logo_image) {
            $this->deleteImage($setting->logo_image);
            $setting->update([
                'logo_image' => null,
            ]);
        }

        return redirect()->route('admin.settings.edit')->with('success', 'ロゴ画像を削除しました。');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncClosedDays(SalonSetting $setting, array $validated): void
    {
        $weekdays = collect($validated['closed_weekdays'] ?? [])
            ->map(fn ($value) => (int) $value)
            ->filter(fn (int $value) => $value >= 0 && $value <= 6)
            ->unique()
            ->sort()
            ->values();

        $setting->closedWeekdays()->delete();
        foreach ($weekdays as $weekday) {
            $setting->closedWeekdays()->create(['weekday' => $weekday]);
        }

        $nthRules = collect($validated['closed_nth'] ?? [])
            ->filter(fn ($rule) => is_array($rule))
            ->map(fn (array $rule) => [
                'week' => (int) ($rule['week'] ?? 0),
                'weekday' => (int) ($rule['weekday'] ?? -1),
            ])
            ->filter(fn (array $rule) => $rule['week'] >= 1
                && $rule['week'] <= 5
                && $rule['weekday'] >= 0
                && $rule['weekday'] <= 6)
            ->unique(fn (array $rule) => $rule['week'].'-'.$rule['weekday'])
            ->sortBy(fn (array $rule) => sprintf('%d-%d', $rule['weekday'], $rule['week']))
            ->values();

        $setting->closedNthWeekdays()->delete();
        foreach ($nthRules as $rule) {
            $setting->closedNthWeekdays()->create([
                'week_of_month' => $rule['week'],
                'weekday' => $rule['weekday'],
            ]);
        }
    }

    private function normalizeBusinessHourInputs(Request $request): void
    {
        $payload = [];
        foreach ([
            'weekday_open_time',
            'weekday_close_time',
            'weekend_open_time',
            'weekend_close_time',
        ] as $field) {
            $raw = $request->input($field);
            if (! is_string($raw) || $raw === '') {
                $payload[$field] = null;

                continue;
            }

            try {
                $payload[$field] = Carbon::parse($raw)->format('H:i');
            } catch (\Throwable) {
                $payload[$field] = $raw;
            }
        }

        $request->merge($payload);
    }

    private function validateBusinessHourPair(
        $validator,
        Request $request,
        string $openField,
        string $closeField,
        string $label
    ): void {
        $open = $request->input($openField);
        $close = $request->input($closeField);
        $openFilled = filled($open);
        $closeFilled = filled($close);

        if ($openFilled xor $closeFilled) {
            $missing = $openFilled ? $closeField : $openField;
            $missingLabel = $openFilled ? '閉店時間' : '開店時間';
            $validator->errors()->add($missing, "{$label}の{$missingLabel}を入力してください。");

            return;
        }

        if ($openFilled && $closeFilled && (string) $open >= (string) $close) {
            $validator->errors()->add(
                $closeField,
                "{$label}の閉店時間は開店時間より後にしてください。"
            );
        }
    }

    private function nullableTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->format('H:i:s');
    }
}
