<?php

namespace App\Http\Controllers\Admin;

use App\Models\SocialLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SnsSettingController extends AdminController
{
    public function edit(): View
    {
        $links = SocialLink::ensureDefaults();

        return view('admin.store.sns', compact('links'));
    }

    public function update(Request $request): RedirectResponse
    {
        $serviceKeys = SocialLink::serviceKeys();

        $validated = $request->validate([
            'links' => ['required', 'array'],
            'links.*.url' => [
                'nullable',
                'string',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null) {
                        return;
                    }

                    $url = trim((string) $value);
                    if ($url === '') {
                        return;
                    }

                    if (! preg_match('#^https?://#i', $url)) {
                        $fail('URLは http:// または https:// で始まる形式で入力してください。');

                        return;
                    }

                    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                        $fail('有効なURLを入力してください。');
                    }
                },
            ],
            'links.*.is_visible' => ['nullable', 'boolean'],
            'links.*.display_order' => ['required', 'integer', 'min:1'],
        ]);

        $submittedKeys = array_keys($validated['links']);
        sort($submittedKeys);
        $expected = $serviceKeys;
        sort($expected);
        if ($submittedKeys !== $expected) {
            return redirect()
                ->route('admin.store.sns')
                ->withErrors(['links' => 'SNSサービスの構成が不正です。ページを再読み込みしてから再度保存してください。'])
                ->withInput();
        }

        DB::transaction(function () use ($validated, $serviceKeys): void {
            $orderedKeys = collect($validated['links'])
                ->sortBy(fn (array $row) => (int) ($row['display_order'] ?? PHP_INT_MAX))
                ->keys()
                ->values();

            // Resolve duplicate / sparse orders into unique 1..N sequence.
            $orderMap = [];
            foreach ($orderedKeys as $index => $key) {
                $orderMap[$key] = $index + 1;
            }

            $instagramUrl = null;

            foreach ($serviceKeys as $key) {
                $row = $validated['links'][$key];
                $url = isset($row['url']) ? trim((string) $row['url']) : '';
                $url = $url === '' ? null : $url;
                $isVisible = filter_var($row['is_visible'] ?? false, FILTER_VALIDATE_BOOLEAN);

                SocialLink::query()->updateOrCreate(
                    ['service_key' => $key],
                    [
                        'url' => $url,
                        'is_visible' => $isVisible,
                        'display_order' => $orderMap[$key] ?? 1,
                    ]
                );

                if ($key === 'instagram') {
                    $instagramUrl = $url;
                }
            }

            SocialLink::syncLegacyInstagramColumn($instagramUrl);
        });

        return redirect()->route('admin.store.sns')->with('success', 'SNS設定を更新しました。');
    }
}
