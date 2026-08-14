<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salon_settings', function (Blueprint $table) {
            $table->time('weekday_open_time')->nullable()->after('access_directions');
            $table->time('weekday_close_time')->nullable()->after('weekday_open_time');
            $table->time('weekend_open_time')->nullable()->after('weekday_close_time');
            $table->time('weekend_close_time')->nullable()->after('weekend_open_time');
        });

        if (Schema::hasColumn('salon_settings', 'business_hours')) {
            $rows = DB::table('salon_settings')->select(['id', 'business_hours'])->get();
            foreach ($rows as $row) {
                $parsed = $this->parseLegacyBusinessHours($row->business_hours);
                DB::table('salon_settings')->where('id', $row->id)->update($parsed);
            }

            Schema::table('salon_settings', function (Blueprint $table) {
                $table->dropColumn('business_hours');
            });
        }
    }

    public function down(): void
    {
        Schema::table('salon_settings', function (Blueprint $table) {
            $table->text('business_hours')->nullable()->after('access_directions');
        });

        $rows = DB::table('salon_settings')->select([
            'id',
            'weekday_open_time',
            'weekday_close_time',
            'weekend_open_time',
            'weekend_close_time',
        ])->get();

        foreach ($rows as $row) {
            $lines = [];
            if ($row->weekday_open_time && $row->weekday_close_time) {
                $lines[] = '平日 '.$this->formatClock($row->weekday_open_time)
                    .' - '.$this->formatClock($row->weekday_close_time);
            }
            if ($row->weekend_open_time && $row->weekend_close_time) {
                $lines[] = '土日祝 '.$this->formatClock($row->weekend_open_time)
                    .' - '.$this->formatClock($row->weekend_close_time);
            }

            DB::table('salon_settings')->where('id', $row->id)->update([
                'business_hours' => $lines === [] ? null : implode("\n", $lines),
            ]);
        }

        Schema::table('salon_settings', function (Blueprint $table) {
            $table->dropColumn([
                'weekday_open_time',
                'weekday_close_time',
                'weekend_open_time',
                'weekend_close_time',
            ]);
        });
    }

    /**
     * @return array{
     *     weekday_open_time: ?string,
     *     weekday_close_time: ?string,
     *     weekend_open_time: ?string,
     *     weekend_close_time: ?string
     * }
     */
    private function parseLegacyBusinessHours(mixed $raw): array
    {
        $text = is_string($raw) ? $raw : '';
        $weekday = $this->matchLabeledRange($text, '平日');
        $weekend = $this->matchLabeledRange($text, '土日祝');

        if ($weekday === null && $weekend === null) {
            $weekday = $this->matchBareRange($text);
        }

        return [
            'weekday_open_time' => $weekday[0] ?? null,
            'weekday_close_time' => $weekday[1] ?? null,
            'weekend_open_time' => $weekend[0] ?? null,
            'weekend_close_time' => $weekend[1] ?? null,
        ];
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function matchLabeledRange(string $text, string $label): ?array
    {
        $pattern = '/'.preg_quote($label, '/').'[^\d]*(\d{1,2}):(\d{2})\s*[-〜～~－]\s*(\d{1,2}):(\d{2})/u';
        if (! preg_match($pattern, $text, $matches)) {
            return null;
        }

        return [
            $this->toTimeString((int) $matches[1], (int) $matches[2]),
            $this->toTimeString((int) $matches[3], (int) $matches[4]),
        ];
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function matchBareRange(string $text): ?array
    {
        if (! preg_match('/(\d{1,2}):(\d{2})\s*[-〜～~－]\s*(\d{1,2}):(\d{2})/u', $text, $matches)) {
            return null;
        }

        return [
            $this->toTimeString((int) $matches[1], (int) $matches[2]),
            $this->toTimeString((int) $matches[3], (int) $matches[4]),
        ];
    }

    private function toTimeString(int $hour, int $minute): string
    {
        return sprintf('%02d:%02d:00', $hour, $minute);
    }

    private function formatClock(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return '';
        }

        if (preg_match('/^(\d{1,2}):(\d{2})/', $value, $matches)) {
            return ((int) $matches[1]).':'.$matches[2];
        }

        return $value;
    }
};
