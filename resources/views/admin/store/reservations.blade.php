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

    <div class="admin-card flex flex-col">
        <form id="reservations-form" method="POST" action="{{ route('admin.store.reservations.update') }}" class="flex h-full flex-col space-y-5">
            @csrf @method('PUT')

            <div>
                <label for="hot_pepper_url" class="admin-label">Hot Pepper 予約URL</label>
                <input type="url" name="hot_pepper_url" id="hot_pepper_url" value="{{ old('hot_pepper_url', $setting->hot_pepper_url) }}" class="admin-input" placeholder="https://beauty.hotpepper.jp/...">
            </div>

            {{-- Future: LINE予約 / 電話予約 / 自社予約URL fields --}}
        </form>
    </div>
@endsection
