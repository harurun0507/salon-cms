<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salon_closed_weekdays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_setting_id')->constrained('salon_settings')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->timestamps();

            $table->unique(['salon_setting_id', 'weekday']);
        });

        Schema::create('salon_closed_nth_weekdays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_setting_id')->constrained('salon_settings')->cascadeOnDelete();
            $table->unsignedTinyInteger('week_of_month');
            $table->unsignedTinyInteger('weekday');
            $table->timestamps();

            $table->unique(['salon_setting_id', 'week_of_month', 'weekday'], 'salon_closed_nth_unique');
        });

        if (Schema::hasColumn('salon_settings', 'closed_days')) {
            $rows = DB::table('salon_settings')->select(['id', 'closed_days'])->get();
            foreach ($rows as $row) {
                $parsed = $this->parseLegacyClosedDays($row->closed_days);
                foreach ($parsed['weekdays'] as $weekday) {
                    DB::table('salon_closed_weekdays')->insert([
                        'salon_setting_id' => $row->id,
                        'weekday' => $weekday,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                foreach ($parsed['nth'] as $rule) {
                    DB::table('salon_closed_nth_weekdays')->insert([
                        'salon_setting_id' => $row->id,
                        'week_of_month' => $rule['week'],
                        'weekday' => $rule['weekday'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            Schema::table('salon_settings', function (Blueprint $table) {
                $table->dropColumn('closed_days');
            });
        }
    }

    public function down(): void
    {
        Schema::table('salon_settings', function (Blueprint $table) {
            $table->string('closed_days')->nullable();
        });

        $settings = DB::table('salon_settings')->pluck('id');
        foreach ($settings as $settingId) {
            $weekdays = DB::table('salon_closed_weekdays')
                ->where('salon_setting_id', $settingId)
                ->orderBy('weekday')
                ->pluck('weekday')
                ->all();
            $nth = DB::table('salon_closed_nth_weekdays')
                ->where('salon_setting_id', $settingId)
                ->orderBy('weekday')
                ->orderBy('week_of_month')
                ->get(['week_of_month', 'weekday']);

            $parts = [];
            if ($weekdays !== []) {
                $labels = array_map(fn ($day) => $this->weekdayLabel((int) $day), $weekdays);
                $parts[] = '毎週'.implode('・', $labels);
            }
            $grouped = [];
            foreach ($nth as $rule) {
                $grouped[(int) $rule->weekday][] = (int) $rule->week_of_month;
            }
            foreach ($grouped as $weekday => $weeks) {
                sort($weeks);
                $weekLabels = array_map(fn ($week) => '第'.$week, $weeks);
                $parts[] = implode('・', $weekLabels).$this->weekdayLabel($weekday);
            }

            DB::table('salon_settings')->where('id', $settingId)->update([
                'closed_days' => $parts === [] ? null : implode('・', $parts),
            ]);
        }

        Schema::dropIfExists('salon_closed_nth_weekdays');
        Schema::dropIfExists('salon_closed_weekdays');
    }

    /**
     * @return array{weekdays: list<int>, nth: list<array{week: int, weekday: int}>}
     */
    private function parseLegacyClosedDays(mixed $raw): array
    {
        $text = is_string($raw) ? $raw : '';
        $weekdays = [];
        $nth = [];

        $labelToIndex = [
            '日' => 0,
            '月' => 1,
            '火' => 2,
            '水' => 3,
            '木' => 4,
            '金' => 5,
            '土' => 6,
        ];

        if (preg_match('/毎週((?:[日月火水木金土]曜日?)(?:[・･、,]\s*(?:[日月火水木金土]曜日?)*)*)/u', $text, $weeklyMatch)) {
            if (preg_match_all('/([日月火水木金土])曜日?/u', $weeklyMatch[1], $dayMatches)) {
                foreach ($dayMatches[1] as $dayChar) {
                    if (isset($labelToIndex[$dayChar])) {
                        $weekdays[] = $labelToIndex[$dayChar];
                    }
                }
            }
        } elseif (preg_match('/([日月火水木金土])曜定休/u', $text, $simple)) {
            $weekdays[] = $labelToIndex[$simple[1]];
        }

        if (preg_match_all('/((?:第[1-5１-５](?:[・･、,]\s*第[1-5１-５])*)+)\s*([日月火水木金土])曜日?/u', $text, $nthMatches, PREG_SET_ORDER)) {
            foreach ($nthMatches as $match) {
                $weekday = $labelToIndex[$match[2]] ?? null;
                if ($weekday === null) {
                    continue;
                }
                if (preg_match_all('/第([1-5１-５])/u', $match[1], $weekMatches)) {
                    foreach ($weekMatches[1] as $weekRaw) {
                        $week = (int) strtr($weekRaw, ['１' => '1', '２' => '2', '３' => '3', '４' => '4', '５' => '5']);
                        if ($week >= 1 && $week <= 5) {
                            $nth[] = ['week' => $week, 'weekday' => $weekday];
                        }
                    }
                }
            }
        }

        $weekdays = array_values(array_unique($weekdays));
        $uniqueNth = [];
        foreach ($nth as $rule) {
            $key = $rule['week'].'-'.$rule['weekday'];
            $uniqueNth[$key] = $rule;
        }

        return [
            'weekdays' => $weekdays,
            'nth' => array_values($uniqueNth),
        ];
    }

    private function weekdayLabel(int $weekday): string
    {
        return match ($weekday) {
            0 => '日曜日',
            1 => '月曜日',
            2 => '火曜日',
            3 => '水曜日',
            4 => '木曜日',
            5 => '金曜日',
            6 => '土曜日',
            default => '',
        };
    }
};
