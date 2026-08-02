<?php

namespace App\Http\Controllers\Admin;

use App\Models\SalonSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsSettingController extends AdminController
{
    public function edit(): View
    {
        $setting = SalonSetting::current();

        return view('admin.system.analytics', compact('setting'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge([
            'ga_measurement_id' => trim((string) $request->input('ga_measurement_id', '')),
        ]);

        $validated = $request->validate([
            'ga_measurement_id' => ['nullable', 'string', 'max:32', 'regex:/^G-[A-Z0-9-]+$/i'],
        ], [
            'ga_measurement_id.regex' => '「G-」から始まる測定IDを入力してください。',
        ]);

        $measurementId = isset($validated['ga_measurement_id'])
            ? strtoupper((string) $validated['ga_measurement_id'])
            : null;

        if ($measurementId === '') {
            $measurementId = null;
        }

        SalonSetting::current()->update([
            'ga_measurement_id' => $measurementId,
        ]);

        return redirect()->route('admin.system.analytics')->with('success', 'Analytics設定を更新しました。');
    }
}
