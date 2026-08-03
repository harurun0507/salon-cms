@extends('layouts.admin')

@section('heading', 'トップページ設定')

@section('save-bar')
    <div class="flex min-w-0 flex-wrap items-center gap-3">
        <button
            type="button"
            class="admin-btn shadow-md shrink-0"
            data-admin-confirm-trigger
            data-confirm-form="top-page-form"
            data-confirm-title="トップページ設定保存の確認"
            data-confirm-message="トップページ設定を保存します。&#10;よろしいですか？"
            data-confirm-note="ヒーロー、コンセプト、表示件数、トップページの表示順など、現在入力されている内容が反映されます。"
            data-confirm-submit-label="保存する"
        >保存する</button>
        <p class="text-sm text-admin-muted">
            トップページに表示するテキストや各セクションの表示設定を編集します。
        </p>
    </div>

@endsection

@section('content')
    @php
        // Always resolve sections here so the two setting cards never disappear,
        // even if the controller omitted $sections or the DB was empty.
        $sections = isset($sections) && $sections instanceof \Illuminate\Support\Collection && $sections->isNotEmpty()
            ? $sections
            : \App\Models\TopPageSection::ensureDefaults();

        $orderedSections = $sections;
        $oldOrder = old('section_order');
        if (is_array($oldOrder) && count($oldOrder) === $sections->count()) {
            $byKey = $sections->keyBy('section_key');
            $reordered = collect();
            foreach ($oldOrder as $key) {
                if ($byKey->has($key)) {
                    $reordered->push($byKey->get($key));
                }
            }
            if ($reordered->count() === $sections->count()) {
                $orderedSections = $reordered;
            }
        }

        $countSections = $sections->filter(fn ($section) => $section->supportsDisplayCount());
    @endphp

    
    <form id="top-page-form" method="POST" action="{{ route('admin.home.top.update') }}" class="space-y-5">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 items-stretch gap-5 xl:grid-cols-2">
            <section class="admin-card min-w-0 space-y-5">
                <div>
                    <h2 class="text-base font-medium text-admin-text">ヒーロー設定</h2>
                    <p class="mt-1 text-sm text-admin-muted">メインビジュアル上に表示するテキストを設定します。</p>
                </div>
                <div class="min-w-0">
                    <label for="hero_label" class="admin-label">ヒーロー英字ラベル</label>
                    <input type="text" name="hero_label" id="hero_label" value="{{ old('hero_label', $setting->hero_label) }}" class="admin-input" placeholder="Personal Hair Salon">
                    <p class="mt-1 text-xs text-gray-500">メインビジュアル上部の小さい英字テキスト。未入力時は表示しません。</p>
                    @error('hero_label')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="min-w-0">
                    <label for="hero_title" class="admin-label">メインコピー</label>
                    <textarea name="hero_title" id="hero_title" rows="3" class="admin-input min-w-0" placeholder="あなたらしさに、 / 少しだけ今っぽさを。">{{ old('hero_title', $setting->hero_title) }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">改行はトップページで反映されます。未入力時は表示しません。</p>
                    @error('hero_title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            <section class="admin-card min-w-0 space-y-5">
                <div>
                    <h2 class="text-base font-medium text-admin-text">コンセプト設定</h2>
                    <p class="mt-1 text-sm text-admin-muted">トップページのコンセプト欄に表示する内容です。</p>
                </div>
                <div class="min-w-0">
                    <label for="concept_title" class="admin-label">コンセプト見出し</label>
                    <input type="text" name="concept_title" id="concept_title" value="{{ old('concept_title', $setting->concept_title) }}" class="admin-input" placeholder="ナチュラルに、自分らしく。">
                    @error('concept_title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="min-w-0">
                    <label for="concept" class="admin-label">コンセプト文</label>
                    <textarea name="concept" id="concept" rows="5" class="admin-input min-w-0">{{ old('concept', $setting->concept) }}</textarea>
                    @error('concept')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </section>
        </div>

        <div class="admin-card space-y-5">
            <div>
                <h2 class="text-base font-medium text-admin-text">トップページ表示件数</h2>
                <p class="mt-1 text-sm text-admin-muted">各セクションをトップページに何件表示するかを設定します。バナー・アクセスは件数設定の対象外です。</p>
            </div>
            <div class="count-fields-grid">
                @foreach($countSections as $section)
                    @php
                        $key = $section->section_key;
                        $max = $section->maxDisplayCount();
                        $defaultCount = \App\Models\TopPageSection::DEFAULTS[$key]['display_count'] ?? 1;
                        $value = old("sections.{$key}.display_count", $section->display_count ?? $defaultCount);
                    @endphp
                    <div>
                        <label for="section-count-{{ $key }}" class="admin-label">{{ $section->label() }}</label>
                        <div class="count-field-row">
                            <input
                                type="number"
                                name="sections[{{ $key }}][display_count]"
                                id="section-count-{{ $key }}"
                                value="{{ $value }}"
                                min="1"
                                max="{{ $max }}"
                                step="1"
                                required
                                class="admin-input"
                            >
                            <span class="count-field-note text-sm text-admin-muted">件（1〜{{ $max }}）</span>
                        </div>
                        @error("sections.{$key}.display_count")
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>
        </div>

        <div class="admin-card space-y-5">
            <div>
                <h2 class="text-base font-medium text-admin-text">トップページの表示順</h2>
                <p class="mt-1 text-sm text-admin-muted">トップページに表示する各セクションの表示・非表示と表示順を設定します。ドラッグで並び替え、スイッチで表示を切り替えられます。</p>
                <p class="mt-1 text-xs text-admin-muted">お知らせ・ギャラリー・メニュー・スタッフの一覧・詳細ページには影響しません。</p>
            </div>

            @error('section_order')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('section_order.*')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <ul class="divide-y divide-admin-border/80 rounded-lg border border-admin-border/60 bg-admin-bg/40" data-top-section-list>
                @foreach($orderedSections as $section)
                    @php
                        $key = $section->section_key;
                        $isVisible = filter_var(
                            old("sections.{$key}.is_visible", $section->is_visible),
                            FILTER_VALIDATE_BOOLEAN
                        );
                    @endphp
                    <li
                        class="top-section-row flex items-center gap-3 px-3 py-3"
                        data-top-section-row
                        data-section-key="{{ $key }}"
                    >
                        <span
                            class="top-section-drag-handle"
                            data-top-section-drag-handle
                            draggable="true"
                            role="button"
                            tabindex="0"
                            aria-label="{{ $section->label() }}を並び替え"
                            title="ドラッグして並び替え"
                            aria-roledescription="ドラッグハンドル"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <circle cx="7" cy="5" r="1.25"/>
                                <circle cx="13" cy="5" r="1.25"/>
                                <circle cx="7" cy="10" r="1.25"/>
                                <circle cx="13" cy="10" r="1.25"/>
                                <circle cx="7" cy="15" r="1.25"/>
                                <circle cx="13" cy="15" r="1.25"/>
                            </svg>
                        </span>

                        <input type="hidden" name="section_order[]" value="{{ $key }}" data-section-order-input>

                        <span class="min-w-0 flex-1 text-sm font-medium text-admin-text">{{ $section->label() }}</span>

                        <label class="admin-switch" data-admin-switch>
                            <input type="hidden" name="sections[{{ $key }}][is_visible]" value="0">
                            <input
                                type="checkbox"
                                name="sections[{{ $key }}][is_visible]"
                                value="1"
                                class="admin-switch-input"
                                {{ $isVisible ? 'checked' : '' }}
                                aria-label="{{ $section->label() }}の表示"
                                data-admin-switch-input
                            >
                            <span class="admin-switch-track" aria-hidden="true">
                                <span class="admin-switch-thumb"></span>
                            </span>
                            <span class="admin-switch-text" data-admin-switch-text>{{ $isVisible ? 'ON' : 'OFF' }}</span>
                        </label>
                    </li>
                @endforeach
            </ul>
        </div>
    </form>

    <style>
        .top-section-drag-handle {
            display: inline-flex;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 2rem;
            color: rgba(115, 109, 101, 0.55);
            cursor: grab;
            touch-action: none;
            user-select: none;
            -webkit-user-select: none;
        }
        .top-section-drag-handle:hover,
        .top-section-drag-handle:focus-visible {
            color: #556344;
        }
        .top-section-drag-handle:focus {
            outline: none;
        }
        .top-section-drag-handle:focus-visible {
            box-shadow: inset 0 0 0 2px rgba(105, 122, 85, 0.35);
            border-radius: 0.25rem;
        }
        .top-section-drag-handle:active,
        .top-section-row.is-dragging .top-section-drag-handle {
            cursor: grabbing;
        }
        .top-section-row {
            transition: background-color 0.15s ease;
            position: relative;
            background-color: #fff;
        }
        .top-section-row.is-dragging {
            opacity: 0.55;
            background-color: #F7F5F1;
        }
        .top-section-row.drag-insert-before::before,
        .top-section-row.drag-insert-after::after {
            content: '';
            position: absolute;
            left: 0.5rem;
            right: 0.5rem;
            height: 2px;
            background-color: #697A55;
            border-radius: 9999px;
            pointer-events: none;
        }
        .top-section-row.drag-insert-before::before {
            top: -1px;
        }
        .top-section-row.drag-insert-after::after {
            bottom: -1px;
        }
        .admin-switch {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            user-select: none;
            min-height: 2.5rem;
        }
        .admin-switch-input {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        .admin-switch-track {
            position: relative;
            width: 2.75rem;
            height: 1.5rem;
            flex-shrink: 0;
            border-radius: 9999px;
            background-color: #D8D2C7;
            transition: background-color 0.15s ease, box-shadow 0.15s ease;
        }
        .admin-switch-thumb {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 9999px;
            background-color: #fff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.12);
            transition: transform 0.15s ease;
        }
        .admin-switch-input:checked + .admin-switch-track {
            background-color: #697A55;
        }
        .admin-switch-input:checked + .admin-switch-track .admin-switch-thumb {
            transform: translateX(1.25rem);
        }
        .admin-switch-input:focus-visible + .admin-switch-track {
            box-shadow: 0 0 0 3px rgba(105, 122, 85, 0.25);
        }
        .admin-switch-text {
            min-width: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            color: #77736D;
        }
        .admin-switch-input:checked ~ .admin-switch-text {
            color: #5F7351;
        }
    </style>

    <script>
        (function () {
            const list = document.querySelector('[data-top-section-list]');
            if (!list) return;

            function clearIndicators() {
                list.querySelectorAll('.drag-insert-before, .drag-insert-after').forEach(function (row) {
                    row.classList.remove('drag-insert-before', 'drag-insert-after');
                });
            }

            let dragRow = null;

            list.addEventListener('dragstart', function (e) {
                const handle = e.target.closest('[data-top-section-drag-handle]');
                if (!handle) return;
                const row = handle.closest('[data-top-section-row]');
                if (!row || !list.contains(row)) return;

                dragRow = row;
                row.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', row.getAttribute('data-section-key') || '');
                try {
                    e.dataTransfer.setDragImage(row, 24, 20);
                } catch (err) {
                    // ignore browsers that reject custom drag images
                }
            });

            list.addEventListener('dragend', function () {
                if (dragRow) {
                    dragRow.classList.remove('is-dragging');
                }
                clearIndicators();
                dragRow = null;
            });

            list.addEventListener('dragover', function (e) {
                if (!dragRow) return;
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                clearIndicators();

                const overRow = e.target.closest('[data-top-section-row]');
                if (!overRow || overRow === dragRow || !list.contains(overRow)) return;

                const rect = overRow.getBoundingClientRect();
                const before = e.clientY < rect.top + rect.height / 2;
                overRow.classList.add(before ? 'drag-insert-before' : 'drag-insert-after');
            });

            list.addEventListener('dragleave', function (e) {
                if (!list.contains(e.relatedTarget)) {
                    clearIndicators();
                }
            });

            list.addEventListener('drop', function (e) {
                if (!dragRow) return;
                e.preventDefault();
                clearIndicators();

                const overRow = e.target.closest('[data-top-section-row]');
                if (overRow && overRow !== dragRow && list.contains(overRow)) {
                    const rect = overRow.getBoundingClientRect();
                    const before = e.clientY < rect.top + rect.height / 2;
                    if (before) {
                        list.insertBefore(dragRow, overRow);
                    } else {
                        list.insertBefore(dragRow, overRow.nextSibling);
                    }
                }

                dragRow.classList.remove('is-dragging');
                dragRow = null;
            });

            list.querySelectorAll('[data-admin-switch-input]').forEach(function (input) {
                const text = input.closest('[data-admin-switch]')?.querySelector('[data-admin-switch-text]');
                const sync = function () {
                    if (text) {
                        text.textContent = input.checked ? 'ON' : 'OFF';
                    }
                };
                input.addEventListener('change', sync);
                sync();
            });
        })();
    </script>
@endsection
