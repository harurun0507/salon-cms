@extends('layouts.admin')

@section('heading', 'SNS')

@section('content')
    <div class="sticky top-[4.5rem] z-10 -mx-4 -mt-4 mb-6 border-b border-admin-border/50 bg-admin-bg/95 px-4 py-3 shadow-[0_1px_0_rgba(61,56,51,0.03)] backdrop-blur-sm md:-mx-8 md:-mt-8 md:px-8">
        <div class="flex min-w-0 flex-wrap items-center gap-3">
            <button
                type="button"
                class="admin-btn shadow-md shrink-0"
                data-admin-confirm-trigger
                data-confirm-form="sns-form"
                data-confirm-title="SNS設定保存の確認"
                data-confirm-message="SNS設定を保存します。&#10;よろしいですか？"
                data-confirm-note="Instagramなど、現在入力されているSNSリンクが反映されます。"
                data-confirm-submit-label="保存する"
            >保存する</button>
            <p class="text-sm text-admin-muted">
                各項目を編集し、「保存する」でまとめて反映できます
            </p>
        </div>
    </div>

    <form id="sns-form" method="POST" action="{{ route('admin.store.sns.update') }}" class="space-y-5">
        @csrf @method('PUT')

        <div class="admin-card space-y-5">
            <div>
                <h2 class="text-base font-medium text-admin-text">SNSアカウント</h2>
                <p class="mt-1 text-sm text-admin-muted">公開サイトで表示するSNSアカウントを設定します。</p>
            </div>

            <div class="space-y-6">
                <x-admin.sns-service
                    name="Instagram"
                    field="instagram_url"
                    field-label="プロフィールURL"
                    :value="old('instagram_url', $setting->instagram_url)"
                    placeholder="https://www.instagram.com/..."
                    help="公開サイトのInstagramアイコンから遷移するURLです。"
                >
                    <x-slot:icon>
                        <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5">
                            <rect x="3.5" y="3.5" width="17" height="17" rx="5" stroke="currentColor" stroke-width="1.5"/>
                            <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.5"/>
                            <circle cx="17.25" cy="6.75" r="1" fill="currentColor"/>
                        </svg>
                    </x-slot:icon>
                </x-admin.sns-service>

                {{-- Future: LINE / TikTok / YouTube / Facebook / X via x-admin.sns-service --}}
            </div>
        </div>
    </form>
@endsection
