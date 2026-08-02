@extends('layouts.admin')

@section('heading', 'Analytics（GA4）')

@section('content')
    <div class="sticky top-[4.5rem] z-10 -mx-4 -mt-4 mb-6 border-b border-admin-border/50 bg-admin-bg/95 px-4 py-3 shadow-[0_1px_0_rgba(61,56,51,0.03)] backdrop-blur-sm md:-mx-8 md:-mt-8 md:px-8">
        <div class="flex min-w-0 flex-wrap items-center gap-3">
            <button
                type="button"
                class="admin-btn shadow-md shrink-0"
                data-admin-confirm-trigger
                data-confirm-form="analytics-form"
                data-confirm-title="Analytics設定保存の確認"
                data-confirm-message="Analytics設定を保存します。&#10;よろしいですか？"
                data-confirm-note="Google Analytics 4 の Measurement ID が公開サイトへ反映されます。未入力の場合は計測タグは出力されません。"
                data-confirm-submit-label="保存する"
            >保存する</button>
            <p class="text-sm text-admin-muted">
                各項目を編集し、「保存する」でまとめて反映できます
            </p>
        </div>
    </div>

    <form id="analytics-form" method="POST" action="{{ route('admin.system.analytics.update') }}" class="space-y-5">
        @csrf @method('PUT')

        <div class="admin-card space-y-5">
            <div>
                <h2 class="text-base font-medium text-admin-text">Google Analytics 4</h2>
                <p class="mt-1 text-sm text-admin-muted">公開サイトのアクセス解析に使う GA4 Measurement ID を設定します。</p>
            </div>

            <div class="space-y-6">
                <div class="admin-service">
                    <div class="admin-service-heading">
                        <svg viewBox="0 0 24 24" fill="none" class="admin-service-icon" aria-hidden="true">
                            <path d="M3 3v18h18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M7 14v4M12 9v9M17 5v13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                        <span class="admin-service-name">GA4</span>
                    </div>

                    <label for="ga_measurement_id" class="admin-label">Measurement ID</label>
                    <input
                        type="text"
                        name="ga_measurement_id"
                        id="ga_measurement_id"
                        value="{{ old('ga_measurement_id', $setting->ga_measurement_id) }}"
                        class="admin-input"
                        placeholder="G-XXXXXXXXXX"
                        maxlength="32"
                        autocomplete="off"
                        spellcheck="false"
                    >
                    @error('ga_measurement_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="admin-service-help">Google Analytics 管理画面の「データストリーム」に表示される ID（G- で始まる）です。空欄のときは公開サイトに計測タグを出力しません。</p>
                </div>
            </div>
        </div>
    </form>
@endsection
