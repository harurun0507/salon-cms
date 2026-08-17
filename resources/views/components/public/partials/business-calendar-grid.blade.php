@php
    /** @var array $calendar */
    /** @var list<string> $weekdayLabels */
    /** @var callable $primaryDayType */

    $year = (int) ($calendar['year'] ?? 0);
    $month = (int) ($calendar['month'] ?? 0);
    $days = $calendar['days'] ?? [];
    $title = (string) ($calendar['title'] ?? '');
    $first = \Carbon\Carbon::create($year, $month, 1)->startOfDay();
    $startPad = (int) $first->dayOfWeek;
    $daysInMonth = (int) $first->daysInMonth;
@endphp

<div class="content-modal__calendar-grid" role="grid" aria-label="{{ $title }}">
    @foreach($weekdayLabels as $label)
        <span class="content-modal__calendar-weekday">{{ $label }}</span>
    @endforeach

    @for($i = 0; $i < $startPad; $i++)
        <span class="content-modal__calendar-day content-modal__calendar-day--empty" aria-hidden="true"></span>
    @endfor

    @for($day = 1; $day <= $daysInMonth; $day++)
        @php
            $iso = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $types = array_values($days[$iso] ?? []);
            $type = $primaryDayType($types);
            $dayClasses = ['content-modal__calendar-day'];
            if ($type !== '') {
                $dayClasses[] = 'content-modal__calendar-day--'.$type;
            }
            foreach ($types as $token) {
                $dayClasses[] = 'is-'.$token;
            }
        @endphp
        <span
            class="{{ implode(' ', array_unique($dayClasses)) }}"
            data-calendar-date="{{ $iso }}"
        >{{ $day }}</span>
    @endfor
</div>
