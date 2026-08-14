<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Japanese national holidays (内閣府の定義に基づく簡易計算).
 */
final class JapanesePublicHolidays
{
    /**
     * @return list<string> Y-m-d
     */
    public static function datesForYear(int $year): array
    {
        $dates = [];

        $add = function (int $month, int $day) use (&$dates, $year): void {
            $dates[] = sprintf('%04d-%02d-%02d', $year, $month, $day);
        };

        // Fixed / calculated base holidays
        $add(1, 1); // 元日
        $add(2, 11); // 建国記念の日
        $add(2, 23); // 天皇誕生日
        $add(4, 29); // 昭和の日
        $add(5, 3); // 憲法記念日
        $add(5, 4); // みどりの日
        $add(5, 5); // こどもの日
        $add(8, 11); // 山の日
        $add(11, 3); // 文化の日
        $add(11, 23); // 勤労感謝の日

        // 春分・秋分
        $add(3, self::vernalEquinoxDay($year));
        $add(9, self::autumnalEquinoxDay($year));

        // Happy Monday
        $add(1, self::nthMonday($year, 1, 2)); // 成人の日
        $add(7, self::nthMonday($year, 7, 3)); // 海の日
        $add(9, self::nthMonday($year, 9, 3)); // 敬老の日
        $add(10, self::nthMonday($year, 10, 2)); // スポーツの日

        // 振替休日
        $set = array_fill_keys($dates, true);
        foreach (array_keys($set) as $iso) {
            $date = \Carbon\Carbon::parse($iso)->startOfDay();
            if ($date->isSunday()) {
                $candidate = $date->copy()->addDay();
                while (isset($set[$candidate->toDateString()])) {
                    $candidate->addDay();
                }
                $set[$candidate->toDateString()] = true;
            }
        }

        // 国民の休日（祝日に挟まれた平日）
        ksort($set);
        $sorted = array_keys($set);
        for ($i = 0, $n = count($sorted) - 1; $i < $n; $i++) {
            $left = \Carbon\Carbon::parse($sorted[$i])->startOfDay();
            $right = \Carbon\Carbon::parse($sorted[$i + 1])->startOfDay();
            if ($left->diffInDays($right) === 2) {
                $middle = $left->copy()->addDay();
                if (! $middle->isSunday()) {
                    $set[$middle->toDateString()] = true;
                }
            }
        }

        $result = array_keys($set);
        sort($result);

        return $result;
    }

    public static function isHoliday(CarbonInterface $date): bool
    {
        return in_array($date->toDateString(), self::datesForYear((int) $date->year), true);
    }

    /**
     * @return list<string>
     */
    public static function datesBetweenYears(int $fromYear, int $toYear): array
    {
        $dates = [];
        for ($year = $fromYear; $year <= $toYear; $year++) {
            $dates = array_merge($dates, self::datesForYear($year));
        }

        return array_values(array_unique($dates));
    }

    private static function nthMonday(int $year, int $month, int $nth): int
    {
        $first = \Carbon\Carbon::create($year, $month, 1)->startOfDay();
        $offset = (8 - $first->dayOfWeek) % 7; // days until Monday
        $monday = $first->copy()->addDays($offset + (($nth - 1) * 7));

        return (int) $monday->day;
    }

    private static function vernalEquinoxDay(int $year): int
    {
        return (int) floor(20.8431 + 0.242194 * ($year - 1980) - floor(($year - 1980) / 4));
    }

    private static function autumnalEquinoxDay(int $year): int
    {
        return (int) floor(23.2488 + 0.242194 * ($year - 1980) - floor(($year - 1980) / 4));
    }
}
