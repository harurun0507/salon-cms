<?php

namespace App\Http\Controllers\Admin;

use App\Models\DesignSetting;
use App\Models\News;
use App\Models\SalonSetting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NewsController extends AdminController
{
    public function index(): View
    {
        $newsList = News::query()->with(['closedDates', 'closedWeekdays'])->ordered()->get();
        $categories = News::CATEGORIES;
        $weekdayLabels = News::WEEKDAY_SHORT_LABELS;
        $design = DesignSetting::current();
        $calendarColors = $design->resolvedBusinessCalendarColors();
        $regularBusinessHours = SalonSetting::current()->regularBusinessHoursAdminPayload();

        return view('admin.news.index', compact(
            'newsList',
            'categories',
            'weekdayLabels',
            'design',
            'calendarColors',
            'regularBusinessHours'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $categoryRule = ['required', 'string', Rule::in(News::categoryKeys())];

        foreach ([
            'calendar_holiday_color',
            'calendar_temporary_color',
            'calendar_hours_color',
        ] as $field) {
            $raw = $request->input($field);
            if (filled($raw)) {
                $request->merge([
                    $field => DesignSetting::normalizeHex((string) $raw),
                ]);
            }
        }

        $this->normalizeHoursTimeInputs($request);

        $validator = Validator::make($request->all(), [
            'calendar_holiday_color' => ['nullable', 'string', 'regex:/^#[0-9a-f]{6}$/'],
            'calendar_temporary_color' => ['nullable', 'string', 'regex:/^#[0-9a-f]{6}$/'],
            'calendar_hours_color' => ['nullable', 'string', 'regex:/^#[0-9a-f]{6}$/'],
            'news' => ['nullable', 'array'],
            'news.*.title' => ['required', 'string', 'max:255'],
            'news.*.body' => ['nullable', 'string'],
            'news.*.category' => $categoryRule,
            'news.*.closed_dates' => ['nullable', 'array'],
            'news.*.closed_dates.*' => ['nullable', 'date_format:Y-m-d'],
            'news.*.closed_weekdays' => ['nullable', 'array'],
            'news.*.closed_weekdays.*' => ['nullable', 'integer', 'between:0,6'],
            'news.*.hours_change_date' => ['nullable', 'date_format:Y-m-d'],
            'news.*.hours_start_time' => ['nullable', 'date_format:H:i'],
            'news.*.hours_end_time' => ['nullable', 'date_format:H:i'],
            'news.*.holiday_period_type' => ['nullable', 'string', Rule::in(News::holidayPeriodTypeKeys())],
            'news.*.holiday_period_from' => ['nullable', 'date_format:Y-m-d'],
            'news.*.holiday_period_to' => ['nullable', 'date_format:Y-m-d'],
            'news.*.published_at' => ['required', 'date'],
            'news.*.is_published' => ['required', 'in:0,1'],
            'news.*.display_order' => ['nullable', 'integer', 'min:0'],
            'new_news' => ['nullable', 'array'],
            'new_news.*.title' => ['required', 'string', 'max:255'],
            'new_news.*.body' => ['nullable', 'string'],
            'new_news.*.category' => $categoryRule,
            'new_news.*.closed_dates' => ['nullable', 'array'],
            'new_news.*.closed_dates.*' => ['nullable', 'date_format:Y-m-d'],
            'new_news.*.closed_weekdays' => ['nullable', 'array'],
            'new_news.*.closed_weekdays.*' => ['nullable', 'integer', 'between:0,6'],
            'new_news.*.hours_change_date' => ['nullable', 'date_format:Y-m-d'],
            'new_news.*.hours_start_time' => ['nullable', 'date_format:H:i'],
            'new_news.*.hours_end_time' => ['nullable', 'date_format:H:i'],
            'new_news.*.holiday_period_type' => ['nullable', 'string', Rule::in(News::holidayPeriodTypeKeys())],
            'new_news.*.holiday_period_from' => ['nullable', 'date_format:Y-m-d'],
            'new_news.*.holiday_period_to' => ['nullable', 'date_format:Y-m-d'],
            'new_news.*.published_at' => ['required', 'date'],
            'new_news.*.is_published' => ['required', 'in:0,1'],
            'new_news.*.display_order' => ['nullable', 'integer', 'min:0'],
            'deleted_ids' => ['nullable', 'array'],
            'deleted_ids.*' => ['integer', 'exists:news,id'],
        ], $this->validationMessages());

        $validator->after(function ($validator) use ($request) {
            foreach (['news', 'new_news'] as $group) {
                $items = $request->input($group, []);
                if (! is_array($items)) {
                    continue;
                }

                foreach ($items as $key => $data) {
                    if (! is_array($data)) {
                        continue;
                    }

                    $category = $data['category'] ?? null;

                    if (News::usesClosedWeekdays($category)) {
                        $weekdays = collect($data['closed_weekdays'] ?? [])
                            ->filter(fn ($value) => $value !== null && $value !== '')
                            ->map(fn ($value) => (int) $value)
                            ->filter(fn (int $value) => $value >= 0 && $value <= 6)
                            ->unique()
                            ->values();

                        if ($weekdays->isEmpty()) {
                            $validator->errors()->add(
                                "{$group}.{$key}.closed_weekdays",
                                '定休日の曜日を1つ以上選択してください。'
                            );
                        }
                    }

                    if (News::usesClosedDates($category)) {
                        $dates = collect($data['closed_dates'] ?? [])
                            ->filter(fn ($value) => is_string($value) && $value !== '')
                            ->unique()
                            ->values();

                        if ($dates->isEmpty()) {
                            $validator->errors()->add(
                                "{$group}.{$key}.closed_dates",
                                '休業日を1件以上選択してください。'
                            );
                        }
                    }

                    if (News::usesHoursChangeFields($category)) {
                        if (! filled($data['hours_change_date'] ?? null)) {
                            $validator->errors()->add(
                                "{$group}.{$key}.hours_change_date",
                                '変更日を選択してください。'
                            );
                        }
                        if (! filled($data['hours_start_time'] ?? null)) {
                            $validator->errors()->add(
                                "{$group}.{$key}.hours_start_time",
                                '開始時間を入力してください。'
                            );
                        }
                        if (! filled($data['hours_end_time'] ?? null)) {
                            $validator->errors()->add(
                                "{$group}.{$key}.hours_end_time",
                                '終了時間を入力してください。'
                            );
                        }

                        $start = (string) ($data['hours_start_time'] ?? '');
                        $end = (string) ($data['hours_end_time'] ?? '');
                        if ($start !== '' && $end !== '' && $start >= $end) {
                            $validator->errors()->add(
                                "{$group}.{$key}.hours_end_time",
                                '終了時間は開始時間より後にしてください。'
                            );
                        }
                    }

                    if (News::usesHolidayPeriodFields($category)) {
                        $periodType = (string) ($data['holiday_period_type'] ?? '');
                        if (! in_array($periodType, News::holidayPeriodTypeKeys(), true)) {
                            $validator->errors()->add(
                                "{$group}.{$key}.holiday_period_type",
                                '対象期間を選択してください。'
                            );
                        }

                        $from = (string) ($data['holiday_period_from'] ?? '');
                        $to = (string) ($data['holiday_period_to'] ?? '');
                        if ($from === '') {
                            $validator->errors()->add(
                                "{$group}.{$key}.holiday_period_from",
                                '開始日（From）を入力してください。'
                            );
                        }
                        if ($to === '') {
                            $validator->errors()->add(
                                "{$group}.{$key}.holiday_period_to",
                                '終了日（To）を入力してください。'
                            );
                        }
                        if ($from !== '' && $to !== '' && $from > $to) {
                            $validator->errors()->add(
                                "{$group}.{$key}.holiday_period_to",
                                '終了日は開始日以降にしてください。'
                            );
                        }
                    }
                }
            }
        });

        $validated = $validator->validate();

        $existingPayload = $validated['news'] ?? [];
        $newPayload = $validated['new_news'] ?? [];
        $deletedIds = collect($validated['deleted_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->all();

        $orderedItems = [];
        foreach ($existingPayload as $id => $data) {
            if (in_array((int) $id, $deletedIds, true)) {
                continue;
            }
            $orderedItems[] = [
                'type' => 'existing',
                'id' => (int) $id,
                'data' => $data,
                'order' => (int) ($data['display_order'] ?? 0),
            ];
        }
        foreach ($newPayload as $key => $data) {
            $orderedItems[] = [
                'type' => 'new',
                'key' => (string) $key,
                'data' => $data,
                'order' => (int) ($data['display_order'] ?? 0),
            ];
        }

        usort($orderedItems, function (array $a, array $b) {
            if ($a['order'] === $b['order']) {
                return 0;
            }

            return $a['order'] < $b['order'] ? -1 : 1;
        });

        DB::transaction(function () use ($orderedItems, $deletedIds, $validated) {
            $calendarColorUpdates = array_filter([
                'calendar_holiday_color' => $validated['calendar_holiday_color'] ?? null,
                'calendar_temporary_color' => $validated['calendar_temporary_color'] ?? null,
                'calendar_hours_color' => $validated['calendar_hours_color'] ?? null,
            ], fn ($value) => is_string($value) && $value !== '');
            if ($calendarColorUpdates !== []) {
                DesignSetting::current()->update($calendarColorUpdates);
            }

            if ($deletedIds !== []) {
                News::query()->whereIn('id', $deletedIds)->delete();
            }

            $order = 1;
            foreach ($orderedItems as $item) {
                $data = $item['data'];
                $attrs = $this->newsAttributes($data, $order);

                if ($item['type'] === 'existing') {
                    $news = News::query()->find($item['id']);
                    if (! $news) {
                        continue;
                    }

                    $news->update(array_merge($attrs, [
                        'slug' => $this->uniqueSlug(News::class, $attrs['title'], $news->id),
                    ]));
                } else {
                    $news = News::query()->create(array_merge($attrs, [
                        'slug' => $this->uniqueSlug(News::class, $attrs['title']),
                    ]));
                }

                $this->syncClosureSelections($news, $data);

                $order++;
            }
        });

        return redirect()->route('admin.news.index')->with('success', 'お知らせを保存しました。');
    }

    /**
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        $messages = [
            'calendar_holiday_color.regex' => 'カラーは #RRGGBB 形式で入力してください。',
            'calendar_temporary_color.regex' => 'カラーは #RRGGBB 形式で入力してください。',
            'calendar_hours_color.regex' => 'カラーは #RRGGBB 形式で入力してください。',
        ];

        foreach (['news', 'new_news'] as $group) {
            $messages["{$group}.*.title.required"] = 'タイトルは必須です。';
            $messages["{$group}.*.category.required"] = 'お知らせの種類は必須です。';
            $messages["{$group}.*.category.in"] = 'お知らせの種類を選択してください。';
            $messages["{$group}.*.published_at.required"] = '公開日時は必須です。';
            $messages["{$group}.*.published_at.date"] = '公開日時の形式が正しくありません。';
            $messages["{$group}.*.is_published.required"] = '公開状態を選択してください。';
            $messages["{$group}.*.is_published.in"] = '公開状態を選択してください。';
            $messages["{$group}.*.hours_change_date.date_format"] = '変更日の形式が正しくありません。';
            $messages["{$group}.*.hours_start_time.date_format"] = '開始時間の形式が正しくありません。';
            $messages["{$group}.*.hours_end_time.date_format"] = '終了時間の形式が正しくありません。';
        }

        return $messages;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function newsAttributes(array $data, int $displayOrder): array
    {
        $category = $data['category'] ?? News::CATEGORY_OTHER;
        $isHours = News::usesHoursChangeFields($category);
        $isHolidayPeriod = News::usesHolidayPeriodFields($category);

        $holidayType = null;
        $holidayFrom = null;
        $holidayTo = null;
        if ($isHolidayPeriod) {
            $holidayType = (string) ($data['holiday_period_type'] ?? News::HOLIDAY_PERIOD_1_YEAR);
            $holidayFrom = $this->nullableDateOnly($data['holiday_period_from'] ?? null);
            $holidayTo = $this->nullableDateOnly($data['holiday_period_to'] ?? null);

            if (
                $holidayFrom
                && ! $holidayTo
                && isset(News::HOLIDAY_PERIOD_MONTHS[$holidayType])
            ) {
                $holidayTo = News::computeHolidayPeriodTo($holidayFrom, $holidayType)?->toDateString();
            }
        }

        return [
            'title' => $data['title'],
            'body' => (string) ($data['body'] ?? ''),
            'category' => $category,
            'hours_change_date' => $isHours
                ? $this->nullableDateOnly($data['hours_change_date'] ?? null)
                : null,
            'hours_start_time' => $isHours
                ? $this->nullableTime($data['hours_start_time'] ?? null)
                : null,
            'hours_end_time' => $isHours
                ? $this->nullableTime($data['hours_end_time'] ?? null)
                : null,
            'holiday_period_type' => $holidayType,
            'holiday_period_from' => $holidayFrom,
            'holiday_period_to' => $holidayTo,
            'published_at' => $this->nullableDate($data['published_at'] ?? null),
            'is_published' => ($data['is_published'] ?? '0') === '1',
            'display_order' => $displayOrder,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncClosureSelections(News $news, array $data): void
    {
        $category = $data['category'] ?? null;

        if (News::usesClosedWeekdays($category)) {
            $news->closedDates()->delete();
            $this->syncClosedWeekdays($news, $data);

            return;
        }

        if (News::usesClosedDates($category)) {
            $news->closedWeekdays()->delete();
            $this->syncClosedDates($news, $data);

            return;
        }

        $news->closedDates()->delete();
        $news->closedWeekdays()->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncClosedDates(News $news, array $data): void
    {
        $news->closedDates()->delete();

        $dates = collect($data['closed_dates'] ?? [])
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->map(fn (string $value) => Carbon::parse($value)->toDateString())
            ->unique()
            ->sort()
            ->values();

        foreach ($dates as $date) {
            $news->closedDates()->create([
                'closed_date' => $date,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncClosedWeekdays(News $news, array $data): void
    {
        $news->closedWeekdays()->delete();

        $weekdays = collect($data['closed_weekdays'] ?? [])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->filter(fn (int $value) => $value >= 0 && $value <= 6)
            ->unique()
            ->sort()
            ->values();

        foreach ($weekdays as $weekday) {
            $news->closedWeekdays()->create([
                'weekday' => $weekday,
            ]);
        }
    }

    private function nullableDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }

    private function nullableDateOnly(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }

    private function nullableTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->format('H:i:s');
    }

    private function normalizeHoursTimeInputs(Request $request): void
    {
        foreach (['news', 'new_news'] as $group) {
            $items = $request->input($group, []);
            if (! is_array($items)) {
                continue;
            }

            foreach ($items as $key => $data) {
                if (! is_array($data)) {
                    continue;
                }

                foreach (['hours_start_time', 'hours_end_time'] as $field) {
                    $raw = $data[$field] ?? null;
                    if (! is_string($raw) || $raw === '') {
                        continue;
                    }

                    try {
                        $items[$key][$field] = Carbon::parse($raw)->format('H:i');
                    } catch (\Throwable) {
                        // Keep original value for validation error messaging.
                    }
                }
            }

            $request->merge([$group => $items]);
        }
    }
}
