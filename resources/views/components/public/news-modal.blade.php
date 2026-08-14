@props(['newsItems'])

@php
    $design = $design ?? \App\Models\DesignSetting::current();
    $usesVerticalScrollIndicator = $design->usesVerticalScrollIndicator();
    $newsSource = collect(
        $newsItems instanceof \Illuminate\Contracts\Pagination\Paginator
            ? $newsItems->items()
            : $newsItems
    )->filter(fn ($news) => $news instanceof \App\Models\News)->values();

    $modalItems = $newsSource
        ->map(fn (\App\Models\News $news) => $news->toPublicModalData())
        ->all();

    $businessCalendars = $usesVerticalScrollIndicator
        ? \App\Models\News::businessCalendarsForPublicModal($newsSource)
        : [];

    $weekdayShortLabels = array_values(\App\Models\News::WEEKDAY_SHORT_LABELS);
@endphp

@if(count($modalItems) > 0)
    <x-public.modal-scroll-lock />
    <div
        id="news-modal"
        class="content-modal"
        hidden
        data-news-modal
        role="dialog"
        aria-modal="true"
        aria-hidden="true"
        aria-labelledby="news-modal-title"
    >
        <div class="content-modal__backdrop" data-news-modal-backdrop></div>
        <div class="content-modal__dialog" data-news-modal-dialog tabindex="-1">
            <button type="button" class="content-modal__close" data-news-modal-close aria-label="お知らせを閉じる">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </button>

            <div class="content-modal__body">
                <header class="content-modal__header content-modal__header--news">
                    <h2 id="news-modal-title" class="content-modal__title" data-news-modal-title></h2>
                </header>

                <section class="content-modal__section" data-news-modal-holiday hidden>
                    <p data-news-modal-holiday-text></p>
                </section>

                <section class="content-modal__section" data-news-modal-temporary hidden>
                    <p class="content-modal__muted">以下の日程は臨時休業となります。</p>
                    <ul class="content-modal__date-list" data-news-modal-temporary-list></ul>
                </section>

                <div class="content-modal__body-text" data-news-modal-body hidden></div>

                <section class="content-modal__calendar" data-news-modal-calendar hidden>
                    <p class="content-modal__calendar-title" data-news-modal-calendar-title></p>
                    <div class="content-modal__calendar-legend" data-news-modal-calendar-legend hidden>
                        <span class="content-modal__calendar-legend-item content-modal__calendar-legend-item--holiday">
                            <span class="content-modal__calendar-legend-swatch" aria-hidden="true"></span>
                            定休日
                        </span>
                        <span class="content-modal__calendar-legend-item content-modal__calendar-legend-item--temporary">
                            <span class="content-modal__calendar-legend-swatch" aria-hidden="true"></span>
                            臨時休業
                        </span>
                        <span class="content-modal__calendar-legend-item content-modal__calendar-legend-item--hours">
                            <span class="content-modal__calendar-legend-swatch" aria-hidden="true"></span>
                            営業時間変更
                        </span>
                    </div>
                    <div class="content-modal__calendar-grid" data-news-modal-calendar-grid></div>
                    <p class="content-modal__calendar-note" data-news-modal-calendar-note hidden></p>
                    <ul class="content-modal__calendar-notes" data-news-modal-calendar-notes hidden></ul>
                </section>

                <footer class="content-modal__news-foot">
                    <span class="news-category-badge content-modal__badge content-modal__news-badge">
                        <svg class="news-category-badge-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                            <rect x="2.25" y="3.25" width="11.5" height="10.5" rx="1.5" stroke="currentColor" stroke-width="1.25"/>
                            <path d="M2.25 6.5h11.5M5.25 2.25v2M10.75 2.25v2" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/>
                        </svg>
                        <span data-news-modal-category></span>
                    </span>
                    <time class="content-modal__news-meta" data-news-modal-date></time>
                </footer>
            </div>
        </div>
    </div>

    <script type="application/json" data-news-modal-data>{!! json_encode(
        [
            'items' => $modalItems,
            'businessCalendars' => $businessCalendars,
            'weekdayLabels' => $weekdayShortLabels,
            'aggregateBusinessCalendar' => $usesVerticalScrollIndicator,
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) !!}</script>

    <script>
        (function () {
            const modal = document.querySelector('[data-news-modal]');
            const dataEl = document.querySelector('[data-news-modal-data]');
            if (!modal || !dataEl) return;

            let payload = {
                items: [],
                businessCalendars: {},
                weekdayLabels: [],
                aggregateBusinessCalendar: false
            };
            try { payload = JSON.parse(dataEl.textContent || '{}'); } catch (e) { return; }
            const items = Array.isArray(payload.items) ? payload.items : [];
            const businessCalendars = payload.businessCalendars && typeof payload.businessCalendars === 'object'
                ? payload.businessCalendars
                : {};
            const aggregateBusinessCalendar = !!payload.aggregateBusinessCalendar;
            const weekdayLabels = Array.isArray(payload.weekdayLabels) && payload.weekdayLabels.length === 7
                ? payload.weekdayLabels
                : ['日', '月', '火', '水', '木', '金', '土'];
            if (!items.length) return;

            const backdrop = modal.querySelector('[data-news-modal-backdrop]');
            const dialog = modal.querySelector('[data-news-modal-dialog]');
            const closeBtn = modal.querySelector('[data-news-modal-close]');
            const dateEl = modal.querySelector('[data-news-modal-date]');
            const categoryEl = modal.querySelector('[data-news-modal-category]');
            const titleEl = modal.querySelector('[data-news-modal-title]');
            const holidayRoot = modal.querySelector('[data-news-modal-holiday]');
            const holidayText = modal.querySelector('[data-news-modal-holiday-text]');
            const temporaryRoot = modal.querySelector('[data-news-modal-temporary]');
            const temporaryList = modal.querySelector('[data-news-modal-temporary-list]');
            const bodyEl = modal.querySelector('[data-news-modal-body]');
            const calendarRoot = modal.querySelector('[data-news-modal-calendar]');
            const calendarTitle = modal.querySelector('[data-news-modal-calendar-title]');
            const calendarLegend = modal.querySelector('[data-news-modal-calendar-legend]');
            const calendarGrid = modal.querySelector('[data-news-modal-calendar-grid]');
            const calendarNote = modal.querySelector('[data-news-modal-calendar-note]');
            const calendarNotes = modal.querySelector('[data-news-modal-calendar-notes]');

            const modalScroll = window.SalonPublicModalScroll;
            let isOpen = false;
            let lastFocus = null;

            function isVerticalIndicator() {
                return document.documentElement.getAttribute('data-scroll-display') === 'vertical_indicator';
            }

            function itemById(id) {
                const numericId = Number(id);
                return items.findIndex(function (item) { return Number(item.id) === numericId; });
            }

            function pad2(value) {
                return String(value).padStart(2, '0');
            }

            function parseNoteDateParts(iso) {
                if (!iso || !/^\d{4}-\d{2}-\d{2}$/.test(String(iso))) {
                    return null;
                }
                const parts = String(iso).split('-');
                return {
                    month: String(Number(parts[1])),
                    day: String(Number(parts[2])),
                };
            }

            function buildCalendarNoteItem(note, focus) {
                const li = document.createElement('li');
                const categoryKey = note && note.categoryKey ? String(note.categoryKey) : '';
                li.className = 'content-modal__calendar-notes-item'
                    + (categoryKey ? ' content-modal__calendar-notes-item--' + categoryKey : '');

                const dateParts = parseNoteDateParts(note && note.date);
                const detailText = (note && note.detail) ? String(note.detail) : '';
                const noteNewsId = note && note.newsId != null ? Number(note.newsId) : null;
                const isFocused = !!(focus && (
                    (focus.holidayNote && categoryKey === 'holiday')
                    || (focus.newsId != null && noteNewsId === Number(focus.newsId))
                ));
                if (isFocused) {
                    li.classList.add('is-selected');
                }

                if (dateParts) {
                    const dateWrap = document.createElement('span');
                    dateWrap.className = 'content-modal__calendar-notes-date';
                    dateWrap.setAttribute('aria-label', dateParts.month + '月' + dateParts.day + '日');

                    const monthEl = document.createElement('span');
                    monthEl.className = 'content-modal__calendar-notes-month';
                    monthEl.textContent = dateParts.month + '月';

                    const dayEl = document.createElement('span');
                    dayEl.className = 'content-modal__calendar-notes-day';
                    dayEl.textContent = dateParts.day;

                    const suffixEl = document.createElement('span');
                    suffixEl.className = 'content-modal__calendar-notes-suffix';
                    suffixEl.textContent = '日';

                    dateWrap.appendChild(monthEl);
                    dateWrap.appendChild(dayEl);
                    dateWrap.appendChild(suffixEl);
                    li.appendChild(dateWrap);

                    const detailEl = document.createElement('span');
                    detailEl.className = 'content-modal__calendar-notes-detail';
                    detailEl.textContent = detailText;
                    li.appendChild(detailEl);
                } else {
                    li.classList.add('content-modal__calendar-notes-item--plain');
                    const detailEl = document.createElement('span');
                    detailEl.className = 'content-modal__calendar-notes-detail';
                    detailEl.textContent = detailText || ((note && note.label) ? String(note.label) : '');
                    li.appendChild(detailEl);
                }

                return li;
            }

            function resolveCalendarFocus(item, calendar) {
                const focus = {
                    dates: new Set(),
                    newsId: item && item.id != null ? Number(item.id) : null,
                    holidayNote: false,
                    categoryKey: item && item.categoryKey ? String(item.categoryKey) : '',
                };
                if (!item) {
                    return focus;
                }

                const category = focus.categoryKey;
                if (category === 'hours' && item.hoursChangeDate) {
                    focus.dates.add(String(item.hoursChangeDate));
                    return focus;
                }

                if (category === 'temporary_closure') {
                    (Array.isArray(item.closedDates) ? item.closedDates : []).forEach(function (date) {
                        if (date) {
                            focus.dates.add(String(date));
                        }
                    });
                    return focus;
                }

                if (category === 'holiday') {
                    focus.holidayNote = true;
                    const days = calendar && calendar.days && typeof calendar.days === 'object'
                        ? calendar.days
                        : {};
                    Object.keys(days).forEach(function (iso) {
                        const types = days[iso];
                        if (Array.isArray(types) && types.indexOf('holiday') !== -1) {
                            focus.dates.add(String(iso));
                        }
                    });
                }

                return focus;
            }

            function primaryDayType(types) {
                if (!Array.isArray(types) || !types.length) return '';
                if (types.indexOf('temporary') !== -1) return 'temporary';
                if (types.indexOf('hours') !== -1) return 'hours';
                if (types.indexOf('holiday') !== -1) return 'holiday';
                return types[0];
            }

            function hideCalendarExtras() {
                if (calendarLegend) calendarLegend.hidden = true;
                if (calendarNote) {
                    calendarNote.hidden = true;
                    calendarNote.textContent = '';
                }
                if (calendarNotes) {
                    calendarNotes.hidden = true;
                    calendarNotes.innerHTML = '';
                }
            }

            function renderMonthGrid(year, month, dayMap, selectedDates) {
                if (!calendarGrid) return;
                calendarGrid.innerHTML = '';
                const selected = selectedDates instanceof Set ? selectedDates : new Set();

                weekdayLabels.forEach(function (label) {
                    const head = document.createElement('span');
                    head.className = 'content-modal__calendar-weekday';
                    head.textContent = label;
                    calendarGrid.appendChild(head);
                });

                const first = new Date(year, month - 1, 1);
                const startPad = first.getDay();
                const daysInMonth = new Date(year, month, 0).getDate();

                for (let i = 0; i < startPad; i += 1) {
                    const empty = document.createElement('span');
                    empty.className = 'content-modal__calendar-day content-modal__calendar-day--empty';
                    empty.setAttribute('aria-hidden', 'true');
                    calendarGrid.appendChild(empty);
                }

                for (let day = 1; day <= daysInMonth; day += 1) {
                    const iso = year + '-' + pad2(month) + '-' + pad2(day);
                    const types = dayMap && dayMap[iso] ? dayMap[iso] : [];
                    const type = primaryDayType(types);
                    const cell = document.createElement('span');
                    cell.className = 'content-modal__calendar-day'
                        + (type ? ' content-modal__calendar-day--' + type : '');
                    if (Array.isArray(types) && types.length) {
                        types.forEach(function (token) {
                            cell.classList.add('is-' + token);
                        });
                    }
                    if (selected.has(iso)) {
                        cell.classList.add('is-selected');
                    }
                    cell.setAttribute('data-calendar-date', iso);
                    cell.textContent = String(day);
                    calendarGrid.appendChild(cell);
                }
            }

            function renderBusinessCalendar(calendar, sourceItem) {
                if (!calendarRoot || !calendar || !calendar.year || !calendar.month) {
                    return false;
                }

                if (titleEl) titleEl.textContent = calendar.title || '';
                if (categoryEl) categoryEl.textContent = '営業カレンダー';
                if (dateEl) dateEl.textContent = calendar.footDate || '';

                if (holidayRoot) holidayRoot.hidden = true;
                if (temporaryRoot) temporaryRoot.hidden = true;
                if (bodyEl) {
                    bodyEl.hidden = true;
                    bodyEl.textContent = '';
                }

                if (calendarTitle) {
                    // Month is already in the modal heading (e.g. "2026年8月 営業カレンダー").
                    calendarTitle.hidden = true;
                    calendarTitle.textContent = '';
                }
                if (calendarLegend) calendarLegend.hidden = false;

                const focus = resolveCalendarFocus(sourceItem, calendar);
                renderMonthGrid(
                    Number(calendar.year),
                    Number(calendar.month),
                    calendar.days || {},
                    focus.dates
                );

                if (calendarNote) {
                    calendarNote.hidden = true;
                    calendarNote.textContent = '';
                }

                if (calendarNotes) {
                    calendarNotes.innerHTML = '';
                    const notes = Array.isArray(calendar.notes) ? calendar.notes : [];
                    if (notes.length) {
                        notes.forEach(function (note) {
                            calendarNotes.appendChild(buildCalendarNoteItem(note, focus));
                        });
                        calendarNotes.hidden = false;
                    } else {
                        calendarNotes.hidden = true;
                    }
                }

                calendarRoot.hidden = false;
                return true;
            }

            function renderSingleCalendar(item) {
                if (!calendarRoot || !calendarGrid || !calendarTitle) return false;

                const canShow = item
                    && item.showCalendar
                    && item.calendarYear
                    && item.calendarMonth;

                if (!canShow) {
                    calendarRoot.hidden = true;
                    calendarGrid.innerHTML = '';
                    calendarTitle.textContent = '';
                    hideCalendarExtras();
                    return false;
                }

                const year = Number(item.calendarYear);
                const month = Number(item.calendarMonth);
                const closedWeekdays = new Set(
                    (Array.isArray(item.closedWeekdays) ? item.closedWeekdays : [])
                        .map(function (day) { return Number(day); })
                );
                const closedDates = new Set(
                    (Array.isArray(item.closedDates) ? item.closedDates : [])
                        .map(function (date) { return String(date); })
                );

                const dayMap = {};
                const daysInMonth = new Date(year, month, 0).getDate();
                for (let day = 1; day <= daysInMonth; day += 1) {
                    const cellDate = new Date(year, month - 1, day);
                    const weekday = cellDate.getDay();
                    const iso = year + '-' + pad2(month) + '-' + pad2(day);
                    if (closedWeekdays.has(weekday)) {
                        dayMap[iso] = ['holiday'];
                    }
                    if (closedDates.has(iso)) {
                        dayMap[iso] = Array.from(new Set([...(dayMap[iso] || []), 'temporary']));
                    }
                }

                calendarTitle.hidden = false;
                calendarTitle.textContent = year + '年' + month + '月';
                if (calendarLegend) calendarLegend.hidden = true;
                renderMonthGrid(year, month, dayMap);

                if (calendarNotes) {
                    calendarNotes.hidden = true;
                    calendarNotes.innerHTML = '';
                }

                if (calendarNote) {
                    let note = '';
                    if (item.holidaySentence) {
                        note = item.holidaySentence;
                    } else if (item.categoryKey === 'temporary_closure') {
                        note = '以下の日程は臨時休業となります。';
                    }
                    if (note) {
                        calendarNote.hidden = false;
                        calendarNote.textContent = note;
                    } else {
                        calendarNote.hidden = true;
                        calendarNote.textContent = '';
                    }
                }

                calendarRoot.hidden = false;
                return true;
            }

            function renderItem(item) {
                const calendarKey = item.businessCalendarKey || '';
                const businessCalendar = calendarKey && businessCalendars[calendarKey]
                    ? businessCalendars[calendarKey]
                    : null;

                if (aggregateBusinessCalendar && isVerticalIndicator() && businessCalendar) {
                    renderBusinessCalendar(businessCalendar, item);
                    return;
                }

                if (dateEl) dateEl.textContent = item.date || '';
                if (categoryEl) categoryEl.textContent = item.category || '';
                if (titleEl) titleEl.textContent = item.title || '';

                const calendarShown = renderSingleCalendar(item);

                if (holidayRoot && holidayText) {
                    if (!calendarShown && item.holidaySentence) {
                        holidayRoot.hidden = false;
                        holidayText.textContent = item.holidaySentence;
                    } else {
                        holidayRoot.hidden = true;
                        holidayText.textContent = '';
                    }
                }

                if (temporaryRoot && temporaryList) {
                    temporaryList.innerHTML = '';
                    const dates = Array.isArray(item.temporaryDates) ? item.temporaryDates : [];
                    if (!calendarShown && dates.length) {
                        temporaryRoot.hidden = false;
                        dates.forEach(function (label) {
                            const li = document.createElement('li');
                            li.textContent = label;
                            temporaryList.appendChild(li);
                        });
                    } else {
                        temporaryRoot.hidden = true;
                    }
                }

                if (bodyEl) {
                    if (item.body) {
                        bodyEl.hidden = false;
                        bodyEl.textContent = item.body;
                    } else {
                        bodyEl.hidden = true;
                        bodyEl.textContent = '';
                    }
                }

                if (!calendarShown) {
                    hideCalendarExtras();
                    if (calendarRoot) calendarRoot.hidden = true;
                }
            }

            function openModal(id) {
                const index = itemById(id);
                if (index < 0) return;
                const item = items[index];
                if (!isOpen) {
                    lastFocus = document.activeElement;
                    isOpen = true;
                    modalScroll?.lock(modal);
                    modal.hidden = false;
                    modal.setAttribute('aria-hidden', 'false');
                    modal.classList.add('is-open');
                }
                renderItem(item);
                if (dialog) dialog.scrollTop = 0;
                window.setTimeout(function () {
                    modalScroll?.focusWithoutScroll(closeBtn || dialog);
                }, 0);
            }

            function closeModal() {
                if (!isOpen) return;
                isOpen = false;
                if (document.activeElement && modal.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
                modal.classList.remove('is-open');
                modal.hidden = true;
                modal.setAttribute('aria-hidden', 'true');
                modalScroll?.unlock();
                modalScroll?.focusWithoutScroll(lastFocus);
                lastFocus = null;
            }

            document.addEventListener('click', function (event) {
                if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }
                const trigger = event.target.closest('[data-news-modal-trigger]');
                if (!trigger) return;
                const id = trigger.getAttribute('data-news-id');
                if (!id || itemById(id) < 0) return;
                event.preventDefault();
                openModal(id);
            });

            closeBtn?.addEventListener('click', closeModal);
            backdrop?.addEventListener('click', closeModal);
            document.addEventListener('keydown', function (event) {
                if (isOpen && event.key === 'Escape') {
                    event.preventDefault();
                    closeModal();
                }
            });
        })();
    </script>
@endif
