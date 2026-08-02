<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        $users = User::query()->orderBy('id')->get();

        return view('admin.system.users', [
            'users' => $users,
            'currentUserId' => Auth::id(),
            'activeAdminCount' => User::activeAdminCount(),
        ]);
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $currentUserId = (int) Auth::id();

        $validator = Validator::make($request->all(), [
            'users' => ['nullable', 'array'],
            'users.*.name' => ['required', 'string', 'max:255'],
            'users.*.email' => ['required', 'email', 'max:255'],
            'users.*.role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_EDITOR])],
            'users.*.is_active' => ['nullable', 'in:0,1'],
            'users.*.password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'new_users' => ['nullable', 'array'],
            'new_users.*.name' => ['required', 'string', 'max:255'],
            'new_users.*.email' => ['required', 'email', 'max:255'],
            'new_users.*.role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_EDITOR])],
            'new_users.*.is_active' => ['nullable', 'in:0,1'],
            'new_users.*.password' => ['required', 'string', 'min:8', 'confirmed'],
            'deleted_ids' => ['nullable', 'array'],
            'deleted_ids.*' => ['integer', 'exists:users,id'],
        ], [
            'users.*.name.required' => '名前は必須です。',
            'users.*.email.required' => 'メールアドレスは必須です。',
            'users.*.email.email' => 'メールアドレスの形式が正しくありません。',
            'users.*.password.min' => 'パスワードは8文字以上で入力してください。',
            'users.*.password.confirmed' => 'パスワード（確認）が一致しません。',
            'new_users.*.name.required' => '名前は必須です。',
            'new_users.*.email.required' => 'メールアドレスは必須です。',
            'new_users.*.email.email' => 'メールアドレスの形式が正しくありません。',
            'new_users.*.password.required' => 'パスワードは必須です。',
            'new_users.*.password.min' => 'パスワードは8文字以上で入力してください。',
            'new_users.*.password.confirmed' => 'パスワード（確認）が一致しません。',
        ]);

        $validator->after(function ($validator) use ($request, $currentUserId) {
            $existingPayload = $request->input('users', []) ?: [];
            $newPayload = $request->input('new_users', []) ?: [];
            $deletedIds = collect($request->input('deleted_ids', []))->map(fn ($id) => (int) $id)->unique()->all();

            if (in_array($currentUserId, $deletedIds, true)) {
                $validator->errors()->add('deleted_ids', '自分自身のアカウントは削除できません。');
            }

            $emails = [];
            foreach ($existingPayload as $id => $data) {
                if (! is_array($data) || in_array((int) $id, $deletedIds, true)) {
                    continue;
                }
                $email = strtolower(trim((string) ($data['email'] ?? '')));
                if ($email === '') {
                    continue;
                }
                if (isset($emails[$email])) {
                    $validator->errors()->add("users.{$id}.email", 'メールアドレスが重複しています。');
                }
                $emails[$email] = "users.{$id}.email";

                $unique = Rule::unique('users', 'email')->ignore((int) $id);
                $emailValidator = Validator::make(
                    ['email' => $data['email'] ?? ''],
                    ['email' => [$unique]]
                );
                if ($emailValidator->fails()) {
                    $validator->errors()->add("users.{$id}.email", 'このメールアドレスは既に使用されています。');
                }

                $isActive = ($data['is_active'] ?? '0') === '1';
                $role = (string) ($data['role'] ?? '');

                if ((int) $id === $currentUserId) {
                    if (! $isActive) {
                        $validator->errors()->add("users.{$id}.is_active", '自分自身のアカウントは無効にできません。');
                    }
                    if ($role !== User::ROLE_ADMIN) {
                        $validator->errors()->add("users.{$id}.role", '自分自身の権限は変更できません。');
                    }
                }
            }

            foreach ($newPayload as $key => $data) {
                if (! is_array($data)) {
                    continue;
                }
                $email = strtolower(trim((string) ($data['email'] ?? '')));
                if ($email === '') {
                    continue;
                }
                if (isset($emails[$email])) {
                    $validator->errors()->add("new_users.{$key}.email", 'メールアドレスが重複しています。');
                }
                $emails[$email] = "new_users.{$key}.email";

                $emailValidator = Validator::make(
                    ['email' => $data['email'] ?? ''],
                    ['email' => [Rule::unique('users', 'email')]]
                );
                if ($emailValidator->fails()) {
                    $validator->errors()->add("new_users.{$key}.email", 'このメールアドレスは既に使用されています。');
                }
            }

            // Simulate resulting active admins after this save.
            $remainingUsers = User::query()->get()->keyBy('id');
            foreach ($deletedIds as $deletedId) {
                $target = $remainingUsers->get($deletedId);
                if (! $target) {
                    continue;
                }
                // Hard delete only never-logged-in; otherwise soft-disable.
                if ($target->last_login_at === null) {
                    $remainingUsers->forget($deletedId);
                } else {
                    $target->is_active = false;
                }
            }

            foreach ($existingPayload as $id => $data) {
                if (! is_array($data) || in_array((int) $id, $deletedIds, true)) {
                    continue;
                }
                $user = $remainingUsers->get((int) $id);
                if (! $user) {
                    continue;
                }
                $user->role = (string) ($data['role'] ?? $user->role);
                $user->is_active = ($data['is_active'] ?? '0') === '1';
            }

            foreach ($newPayload as $data) {
                if (! is_array($data)) {
                    continue;
                }
                $remainingUsers->push(new User([
                    'role' => (string) ($data['role'] ?? User::ROLE_EDITOR),
                    'is_active' => ($data['is_active'] ?? '0') === '1',
                ]));
            }

            $activeAdmins = $remainingUsers->filter(
                fn (User $user) => $user->role === User::ROLE_ADMIN && $user->is_active
            )->count();

            if ($activeAdmins < 1) {
                $validator->errors()->add('users', '有効な管理者を少なくとも1人残す必要があります。');
            }
        });

        $validated = $validator->validate();

        $existingPayload = $validated['users'] ?? [];
        $newPayload = $validated['new_users'] ?? [];
        $deletedIds = collect($validated['deleted_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->all();

        DB::transaction(function () use ($existingPayload, $newPayload, $deletedIds, $currentUserId) {
            if ($deletedIds !== []) {
                $toProcess = User::query()->whereIn('id', $deletedIds)->get();
                foreach ($toProcess as $user) {
                    if ((int) $user->id === $currentUserId) {
                        continue;
                    }

                    if ($user->last_login_at === null) {
                        $user->delete();
                    } else {
                        $user->update([
                            'is_active' => false,
                        ]);
                    }
                }
            }

            foreach ($existingPayload as $id => $data) {
                if (in_array((int) $id, $deletedIds, true)) {
                    continue;
                }

                $user = User::query()->find((int) $id);
                if (! $user) {
                    continue;
                }

                $attrs = [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'role' => $data['role'],
                    'is_active' => ($data['is_active'] ?? '0') === '1',
                ];

                if ((int) $user->id === $currentUserId) {
                    $attrs['role'] = User::ROLE_ADMIN;
                    $attrs['is_active'] = true;
                }

                if (! empty($data['password'])) {
                    $attrs['password'] = $data['password'];
                }

                $user->update($attrs);
            }

            foreach ($newPayload as $data) {
                User::query()->create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'role' => $data['role'],
                    'is_active' => ($data['is_active'] ?? '0') === '1',
                    'password' => $data['password'],
                ]);
            }
        });

        return redirect()->route('admin.system.users')->with('success', '管理ユーザーを保存しました。');
    }
}
