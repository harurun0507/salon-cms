<?php

namespace App\Http\Controllers\Admin;

use App\Models\StaffMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StaffController extends AdminController
{
    public function index(): View
    {
        $staffMembers = StaffMember::query()->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.staff.index', compact('staffMembers'));
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'staff' => ['nullable', 'array'],
            'staff.*.name' => ['required', 'string', 'max:255'],
            'staff.*.role' => ['nullable', 'string', 'max:255'],
            'staff.*.profile' => ['nullable', 'string'],
            'staff.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'staff.*.is_published' => ['nullable', 'in:0,1'],
            'staff.*.photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'new_staff' => ['nullable', 'array'],
            'new_staff.*.name' => ['required', 'string', 'max:255'],
            'new_staff.*.role' => ['nullable', 'string', 'max:255'],
            'new_staff.*.profile' => ['nullable', 'string'],
            'new_staff.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'new_staff.*.is_published' => ['nullable', 'in:0,1'],
            'new_staff.*.photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'deleted_ids' => ['nullable', 'array'],
            'deleted_ids.*' => ['integer', 'exists:staff_members,id'],
        ]);

        $existingPayload = $validated['staff'] ?? [];
        $newPayload = $validated['new_staff'] ?? [];
        $deletedIds = collect($validated['deleted_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->all();

        $orderedItems = [];
        foreach ($existingPayload as $id => $data) {
            if (in_array((int) $id, $deletedIds, true)) {
                continue;
            }
            $orderedItems[] = [
                'type' => 'existing',
                'id' => (int) $id,
                'data' => $data,
                'order' => (int) ($data['sort_order'] ?? 0),
            ];
        }
        foreach ($newPayload as $key => $data) {
            $orderedItems[] = [
                'type' => 'new',
                'key' => (string) $key,
                'data' => $data,
                'order' => (int) ($data['sort_order'] ?? 0),
            ];
        }

        usort($orderedItems, function (array $a, array $b) {
            if ($a['order'] === $b['order']) {
                return 0;
            }

            return $a['order'] < $b['order'] ? -1 : 1;
        });

        DB::transaction(function () use ($request, $orderedItems, $deletedIds) {
            if ($deletedIds !== []) {
                $toDelete = StaffMember::query()->whereIn('id', $deletedIds)->get();
                foreach ($toDelete as $member) {
                    $this->deleteImage($member->photo_path);
                    $member->delete();
                }
            }

            $order = 1;
            foreach ($orderedItems as $item) {
                $data = $item['data'];
                $attrs = [
                    'name' => $data['name'],
                    'role' => $data['role'] ?? null,
                    'profile' => $data['profile'] ?? null,
                    'sort_order' => $order,
                    'is_published' => ($data['is_published'] ?? '0') === '1',
                ];

                if ($item['type'] === 'existing') {
                    $member = StaffMember::query()->find($item['id']);
                    if (! $member) {
                        continue;
                    }

                    $photoFile = $request->file("staff.{$item['id']}.photo");
                    $attrs['photo_path'] = $this->storeImage($photoFile, 'staff', $member->photo_path);
                    $member->update($attrs);
                } else {
                    $photoFile = $request->file("new_staff.{$item['key']}.photo");
                    StaffMember::query()->create(array_merge($attrs, [
                        'photo_path' => $this->storeImage($photoFile, 'staff'),
                    ]));
                }

                $order++;
            }
        });

        return redirect()->route('admin.staff.index')->with('success', 'スタッフを一括保存しました。');
    }
}
