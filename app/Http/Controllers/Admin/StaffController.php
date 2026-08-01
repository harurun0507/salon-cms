<?php

namespace App\Http\Controllers\Admin;

use App\Models\StaffMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffController extends AdminController
{
    public function index(): View
    {
        $staffMembers = StaffMember::query()->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.staff.index', compact('staffMembers'));
    }

    public function create(): View
    {
        return view('admin.staff.create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'profile' => ['nullable', 'string'],
            'role' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $staff = StaffMember::create([
            'name' => $validated['name'],
            'photo_path' => $this->storeImage($request->file('photo'), 'staff'),
            'profile' => $validated['profile'] ?? null,
            'role' => $validated['role'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published', true),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'スタッフを登録しました。',
                'staff' => [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'role' => $staff->role,
                    'profile' => $staff->profile,
                    'sort_order' => (int) $staff->sort_order,
                    'is_published' => (bool) $staff->is_published,
                    'photo_url' => $staff->photo_path ? asset('storage/'.$staff->photo_path) : null,
                    'photo_path' => $staff->photo_path,
                    'update_url' => route('admin.staff.update', $staff),
                    'destroy_url' => route('admin.staff.destroy', $staff),
                ],
            ]);
        }

        return redirect()->route('admin.staff.index')->with('success', 'スタッフを登録しました。');
    }

    public function edit(StaffMember $staff): View
    {
        return view('admin.staff.edit', compact('staff'));
    }

    public function update(Request $request, StaffMember $staff): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'profile' => ['nullable', 'string'],
            'role' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $staff->update([
            'name' => $validated['name'],
            'photo_path' => $this->storeImage($request->file('photo'), 'staff', $staff->photo_path),
            'profile' => $validated['profile'] ?? null,
            'role' => $validated['role'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);

        if ($request->wantsJson()) {
            $staff->refresh();

            return response()->json([
                'message' => 'スタッフ情報を更新しました。',
                'staff' => [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'role' => $staff->role,
                    'profile' => $staff->profile,
                    'sort_order' => (int) $staff->sort_order,
                    'is_published' => (bool) $staff->is_published,
                    'photo_url' => $staff->photo_path ? asset('storage/'.$staff->photo_path) : null,
                    'photo_path' => $staff->photo_path,
                ],
            ]);
        }

        return redirect()->route('admin.staff.index')->with('success', 'スタッフを更新しました。');
    }

    public function destroy(StaffMember $staff): RedirectResponse
    {
        $this->deleteImage($staff->photo_path);
        $staff->delete();

        return redirect()->route('admin.staff.index')->with('success', 'スタッフを削除しました。');
    }
}
