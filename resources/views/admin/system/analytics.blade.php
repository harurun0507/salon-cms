@extends('layouts.admin')

@section('heading', 'Analytics（GA4）')

@section('content')
    @php
        $initialMeasurementId = (string) old('ga_measurement_id', $setting->ga_measurement_id ?? '');
        $isConfigured = filled(trim($initialMeasurementId));
    @endphp

    <div class="sticky top-[4.5rem] z-10 -mx-4 -mt-4 mb-6 border-b border-admin-border/50 bg-admin-bg/95 px-4 py-3 shadow-[0_1px_0_rgba(61,56,51,0.03)] backdrop-blur-sm md:-mx-8 md:-mt-8 md:px-8">
        <div class="flex min-w-0 flex-wrap items-center gap-3">
            <button
                type="button"
                class="admin-btn shadow-md shrink-0"
                data-admin-confirm-trigger
                data-confirm-form="analytics-form"
                data-confirm-title="Analytics設定保存の確認"
                data-confirm-message="Analytics設定を保存します。&#10;よろしいですか？"
                data-confirm-note="測定IDが公開サイトへ反映されます。未入力の場合はアクセス解析は行われません。"
                data-confirm-submit-label="保存する"
            >保存する</button>
            <p class="text-sm text-admin-muted">
                Google Analyticsの測定IDを設定します。
            </p>
        </div>
    </div>

    <form id="analytics-form" method="POST" action="{{ route('admin.system.analytics.update') }}" class="space-y-5" data-analytics-form>
        @csrf @method('PUT')

        <div class="admin-card">
            <div>
                <h2 class="text-base font-medium text-admin-text">Google Analytics 4</h2>
                <p class="mt-1 text-sm text-admin-muted">
                    Google Analyticsを利用すると、公開サイトの閲覧数やアクセス状況をGoogle Analyticsの管理画面で確認できます。<br>
                    Google Analyticsで発行された「測定ID（G-から始まるID）」を入力してください。
                </p>
            </div>

            <div
                class="mt-4"
                data-analytics-status
                data-configured="{{ $isConfigured ? '1' : '0' }}"
                role="status"
                aria-live="polite"
            >
                <div class="analytics-status-heading">
                    <span class="analytics-status-label">状態</span>
                    <span
                        class="analytics-status-badge {{ $isConfigured ? 'is-set' : 'is-unset' }}"
                        data-analytics-status-badge
                    >
                        @if ($isConfigured)
                            <svg class="analytics-status-badge-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                <path d="M3.5 8.5 6.5 11.5 12.5 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            設定済み
                        @else
                            <svg class="analytics-status-badge-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                <path d="M8 5.5V8.5M8 11h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M7.14 2.9 1.7 12.2A1 1 0 0 0 2.56 13.7h10.88a1 1 0 0 0 .86-1.5L8.86 2.9a1 1 0 0 0-1.72 0Z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>
                            </svg>
                            未設定
                        @endif
                    </span>
                </div>
                <div
                    class="text-xs leading-relaxed {{ $isConfigured ? 'text-[#5F7A52]' : 'text-admin-muted' }}"
                    data-analytics-status-text
                >
                    @if ($isConfigured)
                        公開サイトでアクセス解析が有効です。
                    @else
                        <p>アクセス解析は現在無効です。</p>
                        <p class="mt-0.5">測定IDを設定すると、公開サイトのアクセス解析を開始できます。</p>
                    @endif
                </div>
            </div>

            <div class="mt-3">
                <div class="mb-1.5 flex flex-wrap items-center justify-between gap-2">
                    <label for="ga_measurement_id" class="admin-label mb-0">Google Analytics 測定ID</label>
                    <a
                        href="https://analytics.google.com/"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="admin-btn-secondary inline-flex shrink-0 items-center gap-1.5"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                            <path d="M6.5 3.5H4.2A1.7 1.7 0 0 0 2.5 5.2v6.6A1.7 1.7 0 0 0 4.2 13.5h6.6a1.7 1.7 0 0 0 1.7-1.7V9.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
                            <path d="M9.5 2.5h4v4M13.5 2.5 8 8" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Google Analyticsを開く
                    </a>
                </div>
                <input
                    type="text"
                    name="ga_measurement_id"
                    id="ga_measurement_id"
                    value="{{ $initialMeasurementId }}"
                    class="admin-input"
                    placeholder="G-XXXXXXXXXX"
                    maxlength="32"
                    autocomplete="off"
                    spellcheck="false"
                    data-analytics-measurement-id
                >
                @error('ga_measurement_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1.5 text-xs leading-relaxed text-admin-muted">
                    測定IDは、Google Analyticsの「管理」→「データストリーム」→対象のWebサイトから確認できます。
                </p>
            </div>
        </div>
    </form>

    <script>
        (function () {
            const form = document.querySelector('[data-analytics-form]');
            if (!form) {
                return;
            }

            const input = form.querySelector('[data-analytics-measurement-id]');
            const status = form.querySelector('[data-analytics-status]');
            const badge = form.querySelector('[data-analytics-status-badge]');
            const text = form.querySelector('[data-analytics-status-text]');
            if (!input || !status || !badge || !text) {
                return;
            }

            const emptyBadgeClass = 'analytics-status-badge is-unset';
            const filledBadgeClass = 'analytics-status-badge is-set';
            const emptyTextClass = 'text-xs leading-relaxed text-admin-muted';
            const filledTextClass = 'text-xs leading-relaxed text-[#5F7A52]';
            const warnIcon = '<svg class="analytics-status-badge-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M8 5.5V8.5M8 11h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M7.14 2.9 1.7 12.2A1 1 0 0 0 2.56 13.7h10.88a1 1 0 0 0 .86-1.5L8.86 2.9a1 1 0 0 0-1.72 0Z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/></svg>';
            const checkIcon = '<svg class="analytics-status-badge-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3.5 8.5 6.5 11.5 12.5 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';

            const syncStatus = () => {
                const configured = input.value.trim() !== '';
                status.dataset.configured = configured ? '1' : '0';
                badge.className = configured ? filledBadgeClass : emptyBadgeClass;
                badge.innerHTML = configured
                    ? checkIcon + '設定済み'
                    : warnIcon + '未設定';
                text.className = configured ? filledTextClass : emptyTextClass;
                text.innerHTML = configured
                    ? '公開サイトでアクセス解析が有効です。'
                    : '<p>アクセス解析は現在無効です。</p><p class="mt-0.5">測定IDを設定すると、公開サイトのアクセス解析を開始できます。</p>';
            };

            input.addEventListener('input', syncStatus);
            input.addEventListener('change', syncStatus);
            syncStatus();
        })();
    </script>
@endsection
