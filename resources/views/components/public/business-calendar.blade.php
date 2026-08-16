@props([
    'year' => null,
    'month' => null,
    'showTitle' => true,
    'showLegend' => true,
    'showNotes' => true,
    /** @var string 'default' | 'access' */
    'variant' => 'default',
])

@php
    $isAccess = $variant === 'access';
    $now = now();
    $year = (int) ($year ?: $now->year);
    $month = (int) ($month ?: $now->month);
    $calendar = \App\Models\News::buildBusinessCalendarPayload($year, $month);
    $weekdayLabels = array_values(\App\Models\News::WEEKDAY_SHORT_LABELS);
    $days = $calendar['days'] ?? [];
    $notes = $calendar['notes'] ?? [];

    // Access card: legend lives on the card (not inside calendar), no date list.
    $showTopLegend = $showLegend && ! $isAccess;
    $showNotesList = $showNotes && ! $isAccess && count($notes) > 0;

    $primaryDayType = static function (array $types): string {
        if (in_array('temporary', $types, true)) {
            return 'temporary';
        }
        if (in_array('hours', $types, true)) {
            return 'hours';
        }
        if (in_array('holiday', $types, true)) {
            return 'holiday';
        }

        return (string) ($types[0] ?? '');
    };

    $first = \Carbon\Carbon::create($year, $month, 1)->startOfDay();
    $startPad = (int) $first->dayOfWeek;
    $daysInMonth = (int) $first->daysInMonth;
@endphp

<div {{ $attributes->class([
    'site-business-calendar',
    'content-modal__calendar',
    'site-business-calendar--access' => $isAccess,
]) }}>
    @if($showTitle)
        <p class="content-modal__calendar-title">{{ $calendar['title'] }}</p>
    @endif

    @if($showTopLegend)
        @include('components.public.partials.business-calendar-legend')
    @endif

    <div class="content-modal__calendar-grid" role="grid" aria-label="{{ $calendar['title'] }}">
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

    @if($showNotesList)
        <ul class="content-modal__calendar-notes">
            @foreach($notes as $note)
                @php
                    $categoryKey = (string) ($note['categoryKey'] ?? '');
                    $label = (string) ($note['label'] ?? '');
                    $detail = (string) ($note['detail'] ?? '');
                    $isPlain = $label === '';
                    $itemClass = 'content-modal__calendar-notes-item';
                    if ($categoryKey !== '') {
                        $itemClass .= ' content-modal__calendar-notes-item--'.$categoryKey;
                    }
                    if ($isPlain) {
                        $itemClass .= ' content-modal__calendar-notes-item--plain';
                    }
                    $monthLabel = '';
                    $dayLabel = '';
                    if (preg_match('/^(\d+)月(\d+)日$/u', $label, $matches)) {
                        $monthLabel = $matches[1].'月';
                        $dayLabel = $matches[2];
                    }
                @endphp
                <li class="{{ $itemClass }}">
                    @if(! $isPlain && $monthLabel !== '')
                        <span class="content-modal__calendar-notes-date">
                            <span class="content-modal__calendar-notes-month">{{ $monthLabel }}</span>
                            <span class="content-modal__calendar-notes-day">{{ $dayLabel }}</span>
                            <span class="content-modal__calendar-notes-suffix">日</span>
                        </span>
                        <span class="content-modal__calendar-notes-detail">{{ $detail }}</span>
                    @else
                        <span class="content-modal__calendar-notes-detail">{{ $detail }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
