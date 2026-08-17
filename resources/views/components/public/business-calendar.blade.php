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

    $accessMonths = [];
    if ($isAccess) {
        $currentStart = \Carbon\Carbon::create($year, $month, 1)->startOfDay();
        $nextStart = $currentStart->copy()->addMonthNoOverflow();
        $accessMonths = [
            \App\Models\News::buildBusinessCalendarPayload(
                (int) $currentStart->year,
                (int) $currentStart->month
            ),
            \App\Models\News::buildBusinessCalendarPayload(
                (int) $nextStart->year,
                (int) $nextStart->month
            ),
        ];
        $calendar = $accessMonths[0];
    }
@endphp

<div {{ $attributes->class([
    'site-business-calendar',
    'content-modal__calendar',
    'site-business-calendar--access' => $isAccess,
])->merge($isAccess ? ['data-access-calendar-switcher' => true] : []) }}>
    @if($isAccess)
        <div class="site-business-calendar__nav" data-access-cal-nav>
            <p class="content-modal__calendar-title" data-access-cal-title>{{ $calendar['title'] }}</p>
            <div class="site-business-calendar__nav-actions" aria-hidden="false">
                <button
                    type="button"
                    class="site-business-calendar__nav-btn"
                    data-access-cal-prev
                    aria-label="前の月"
                    disabled
                    aria-disabled="true"
                >‹</button>
                <button
                    type="button"
                    class="site-business-calendar__nav-btn"
                    data-access-cal-next
                    aria-label="次の月"
                >›</button>
            </div>
        </div>

        <div class="site-business-calendar__viewport" data-access-cal-viewport>
            @include('components.public.partials.business-calendar-grid', [
                'calendar' => $calendar,
                'weekdayLabels' => $weekdayLabels,
                'primaryDayType' => $primaryDayType,
            ])
        </div>

        <script type="application/json" data-access-cal-months>
            {!! json_encode(
                array_map(static fn (array $monthPayload): array => [
                    'key' => $monthPayload['key'],
                    'year' => $monthPayload['year'],
                    'month' => $monthPayload['month'],
                    'title' => $monthPayload['title'],
                    'days' => $monthPayload['days'] ?? new \stdClass(),
                ], $accessMonths),
                JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            ) !!}
        </script>
        <script type="application/json" data-access-cal-weekdays>
            {!! json_encode($weekdayLabels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
        </script>
    @else
        @if($showTitle)
            <p class="content-modal__calendar-title">{{ $calendar['title'] }}</p>
        @endif

        @if($showTopLegend)
            @include('components.public.partials.business-calendar-legend')
        @endif

        @include('components.public.partials.business-calendar-grid', [
            'calendar' => $calendar,
            'weekdayLabels' => $weekdayLabels,
            'primaryDayType' => $primaryDayType,
        ])

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
    @endif
</div>

@if($isAccess)
    <script>
        (function () {
            const roots = document.querySelectorAll('[data-access-calendar-switcher]:not([data-access-cal-ready])');
            roots.forEach(function (root) {
                root.setAttribute('data-access-cal-ready', '1');

                const titleEl = root.querySelector('[data-access-cal-title]');
                const prevBtn = root.querySelector('[data-access-cal-prev]');
                const nextBtn = root.querySelector('[data-access-cal-next]');
                const viewport = root.querySelector('[data-access-cal-viewport]');
                const monthsNode = root.querySelector('[data-access-cal-months]');
                const weekdaysNode = root.querySelector('[data-access-cal-weekdays]');
                if (!titleEl || !prevBtn || !nextBtn || !viewport || !monthsNode || !weekdaysNode) {
                    return;
                }

                let months = [];
                let weekdayLabels = [];
                try {
                    months = JSON.parse(monthsNode.textContent || '[]');
                    weekdayLabels = JSON.parse(weekdaysNode.textContent || '[]');
                } catch (e) {
                    return;
                }
                if (!Array.isArray(months) || months.length < 2) {
                    return;
                }

                let index = 0;

                function pad2(value) {
                    return String(value).padStart(2, '0');
                }

                function primaryDayType(types) {
                    if (!Array.isArray(types) || !types.length) return '';
                    if (types.indexOf('temporary') !== -1) return 'temporary';
                    if (types.indexOf('hours') !== -1) return 'hours';
                    if (types.indexOf('holiday') !== -1) return 'holiday';
                    return String(types[0] || '');
                }

                function renderMonth(calendar) {
                    const year = Number(calendar.year);
                    const month = Number(calendar.month);
                    const dayMap = calendar.days && typeof calendar.days === 'object' ? calendar.days : {};
                    const title = calendar.title || (year + '年' + month + '月 営業カレンダー');

                    const grid = document.createElement('div');
                    grid.className = 'content-modal__calendar-grid';
                    grid.setAttribute('role', 'grid');
                    grid.setAttribute('aria-label', title);

                    weekdayLabels.forEach(function (label) {
                        const head = document.createElement('span');
                        head.className = 'content-modal__calendar-weekday';
                        head.textContent = label;
                        grid.appendChild(head);
                    });

                    const first = new Date(year, month - 1, 1);
                    const startPad = first.getDay();
                    const daysInMonth = new Date(year, month, 0).getDate();

                    for (let i = 0; i < startPad; i += 1) {
                        const empty = document.createElement('span');
                        empty.className = 'content-modal__calendar-day content-modal__calendar-day--empty';
                        empty.setAttribute('aria-hidden', 'true');
                        grid.appendChild(empty);
                    }

                    for (let day = 1; day <= daysInMonth; day += 1) {
                        const iso = year + '-' + pad2(month) + '-' + pad2(day);
                        const types = Array.isArray(dayMap[iso]) ? dayMap[iso] : [];
                        const type = primaryDayType(types);
                        const cell = document.createElement('span');
                        cell.className = 'content-modal__calendar-day'
                            + (type ? ' content-modal__calendar-day--' + type : '');
                        types.forEach(function (token) {
                            cell.classList.add('is-' + token);
                        });
                        cell.setAttribute('data-calendar-date', iso);
                        cell.textContent = String(day);
                        grid.appendChild(cell);
                    }

                    viewport.replaceChildren(grid);
                    titleEl.textContent = title;
                }

                function setNavState() {
                    const atStart = index <= 0;
                    const atEnd = index >= months.length - 1;

                    prevBtn.disabled = atStart;
                    prevBtn.setAttribute('aria-disabled', atStart ? 'true' : 'false');

                    nextBtn.disabled = atEnd;
                    nextBtn.setAttribute('aria-disabled', atEnd ? 'true' : 'false');
                }

                function show(nextIndex) {
                    const clamped = Math.max(0, Math.min(months.length - 1, nextIndex));
                    index = clamped;
                    renderMonth(months[index]);
                    setNavState();
                }

                prevBtn.addEventListener('click', function () {
                    show(index - 1);
                });
                nextBtn.addEventListener('click', function () {
                    show(index + 1);
                });

                setNavState();
            });
        })();
    </script>
@endif
