@extends('layouts.admin')

@section('heading', '予約設定')

@section('content')
    <div class="sticky top-[4.5rem] z-10 -mx-4 -mt-4 mb-6 border-b border-admin-border/50 bg-admin-bg/95 px-4 py-3 shadow-[0_1px_0_rgba(61,56,51,0.03)] backdrop-blur-sm md:-mx-8 md:-mt-8 md:px-8">
        <div class="flex min-w-0 flex-wrap items-center gap-3">
            <button
                type="button"
                class="admin-btn shadow-md shrink-0"
                data-admin-confirm-trigger
                data-confirm-form="reservations-form"
                data-confirm-title="予約設定保存の確認"
                data-confirm-message="予約設定を保存します。&#10;よろしいですか？"
                data-confirm-note="Hot Pepper予約URLなど、現在入力されている予約リンクが反映されます。"
                data-confirm-submit-label="保存する"
            >保存する</button>
            <p class="text-sm text-admin-muted">
                各項目を編集し、「保存する」でまとめて反映できます
            </p>
        </div>
    </div>

    <form id="reservations-form" method="POST" action="{{ route('admin.store.reservations.update') }}" class="space-y-5">
        @csrf @method('PUT')

        <div class="admin-card space-y-5">
            <div>
                <h2 class="text-base font-medium text-admin-text">予約サービス</h2>
                <p class="mt-1 text-sm text-admin-muted">公開サイトから利用する予約サービスを設定します。</p>
            </div>

            <div class="space-y-6">
                <x-admin.service-field
                    name="Hot Pepper Beauty"
                    field="hot_pepper_url"
                    field-label="予約URL"
                    :value="old('hot_pepper_url', $setting->hot_pepper_url)"
                    placeholder="https://beauty.hotpepper.jp/..."
                    help="公開サイトの「予約する」ボタンから遷移するURLです。"
                >
                    <x-slot:icon>
                        <svg viewBox="0 0 24 24" fill="none" class="admin-service-icon" aria-hidden="true">
                            <rect x="3.5" y="5" width="17" height="15.5" rx="2" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M3.5 10h17" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M8 3.5v3M16 3.5v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </x-slot:icon>
                </x-admin.service-field>

                {{-- Future: LINE予約 / 楽天ビューティー / 自社予約 / 電話予約 / その他 via x-admin.service-field --}}
            </div>
        </div>
    </form>
@endsection
