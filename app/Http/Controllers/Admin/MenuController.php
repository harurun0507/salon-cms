<?php

namespace App\Http\Controllers\Admin;

use App\Models\Menu;
use App\Models\MenuCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MenuController extends AdminController
{
    public function index(): View
    {
        $categories = MenuCategory::query()->with(['menus' => fn ($q) => $q->orderBy('sort_order')])->orderBy('sort_order')->get();

        return view('admin.menus.index', compact('categories'));
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'categories' => ['nullable', 'array'],
            'categories.*.name' => ['required', 'string', 'max:255'],
            'categories.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'menus' => ['nullable', 'array'],
            'menus.*.name' => ['required', 'string', 'max:255'],
            'menus.*.price' => ['required', 'integer', 'min:0'],
            'menus.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'menus.*.is_published' => ['nullable', 'in:0,1'],
            'menus.*.description' => ['nullable', 'string'],
            'menus.*.category_id' => ['nullable'],
            'selected_category_id' => ['nullable'],
        ]);

        $categoryPayload = $validated['categories'] ?? [];
        $menuPayload = $validated['menus'] ?? [];

        foreach ($menuPayload as $key => $data) {
            if (! preg_match('/^new_menu_\d+$/', (string) $key)) {
                continue;
            }

            $categoryRef = $data['category_id'] ?? null;
            if ($categoryRef === null || $categoryRef === '') {
                return back()
                    ->withErrors(["menus.{$key}.category_id" => 'カテゴリを指定してください。'])
                    ->withInput();
            }

            if (preg_match('/^new_\d+$/', (string) $categoryRef)) {
                if (! array_key_exists((string) $categoryRef, $categoryPayload)) {
                    return back()
                        ->withErrors(["menus.{$key}.category_id" => 'カテゴリを指定してください。'])
                        ->withInput();
                }
            } elseif (! MenuCategory::query()->whereKey($categoryRef)->exists()) {
                return back()
                    ->withErrors(["menus.{$key}.category_id" => '選択されたカテゴリは無効です。'])
                    ->withInput();
            }
        }

        $newCategoryMap = [];
        $lastCreatedId = null;

        DB::transaction(function () use ($categoryPayload, $menuPayload, &$newCategoryMap, &$lastCreatedId) {
            foreach ($categoryPayload as $key => $data) {
                if (preg_match('/^new_\d+$/', (string) $key)) {
                    continue;
                }

                MenuCategory::query()->whereKey($key)->update([
                    'name' => $data['name'],
                    'sort_order' => $data['sort_order'] ?? 0,
                ]);
            }

            foreach ($categoryPayload as $key => $data) {
                if (! preg_match('/^new_\d+$/', (string) $key)) {
                    continue;
                }

                $cat = MenuCategory::query()->create([
                    'name' => $data['name'],
                    'sort_order' => $data['sort_order'] ?? 0,
                ]);
                $newCategoryMap[(string) $key] = (int) $cat->id;
                $lastCreatedId = (int) $cat->id;
            }

            foreach ($menuPayload as $id => $data) {
                if (preg_match('/^new_menu_\d+$/', (string) $id)) {
                    continue;
                }

                Menu::query()->whereKey($id)->update([
                    'name' => $data['name'],
                    'price' => $data['price'],
                    'sort_order' => $data['sort_order'] ?? 0,
                    'is_published' => ($data['is_published'] ?? '0') === '1',
                    'description' => $data['description'] ?? null,
                ]);
            }

            foreach ($menuPayload as $id => $data) {
                if (! preg_match('/^new_menu_\d+$/', (string) $id)) {
                    continue;
                }

                $categoryRef = (string) ($data['category_id'] ?? '');
                if (preg_match('/^new_\d+$/', $categoryRef)) {
                    $resolvedCategoryId = $newCategoryMap[$categoryRef];
                } else {
                    $resolvedCategoryId = (int) $categoryRef;
                }

                Menu::query()->create([
                    'menu_category_id' => $resolvedCategoryId,
                    'name' => $data['name'],
                    'price' => $data['price'],
                    'sort_order' => $data['sort_order'] ?? 0,
                    'is_published' => ($data['is_published'] ?? '0') === '1',
                    'description' => $data['description'] ?? null,
                ]);
            }
        });

        $validIds = MenuCategory::query()->orderBy('sort_order')->pluck('id')->map(fn ($id) => (int) $id)->all();
        $selectedCategoryId = null;
        $requested = $request->input('selected_category_id');

        if ($requested !== null && $requested !== '' && preg_match('/^new_\d+$/', (string) $requested) && isset($newCategoryMap[(string) $requested])) {
            $selectedCategoryId = $newCategoryMap[(string) $requested];
        } elseif ($lastCreatedId !== null) {
            $selectedCategoryId = (int) $lastCreatedId;
        } elseif ($requested !== null && $requested !== '' && in_array((int) $requested, $validIds, true)) {
            $selectedCategoryId = (int) $requested;
        } elseif ($validIds !== []) {
            $selectedCategoryId = $validIds[0];
        }

        $redirect = redirect()->route('admin.menus.index')->with('success', 'メニュー情報を一括保存しました。');

        if ($selectedCategoryId !== null) {
            $redirect->with('selected_category_id', $selectedCategoryId);
        }

        return $redirect;
    }

    public function createCategory(): View
    {
        return view('admin.menus.create-category');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $category = MenuCategory::create([
            'name' => $validated['name'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'カテゴリを登録しました。')
            ->with('selected_category_id', $category->id);
    }

    public function editCategory(MenuCategory $category): View
    {
        return view('admin.menus.edit-category', compact('category'));
    }

    public function updateCategory(Request $request, MenuCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()->route('admin.menus.index')->with('success', 'カテゴリを更新しました。');
    }

    public function destroyCategory(MenuCategory $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('admin.menus.index')->with('success', 'カテゴリを削除しました。');
    }

    public function create(MenuCategory $category): View
    {
        return view('admin.menus.create', compact('category'));
    }

    public function store(Request $request, MenuCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $category->menus()->create([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published', true),
        ]);

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'メニューを登録しました。')
            ->with('selected_category_id', $category->id);
    }

    public function edit(Menu $menu): View
    {
        return view('admin.menus.edit', compact('menu'));
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $validated = $request->validate([
            'menu_category_id' => ['required', 'exists:menu_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $menu->update([
            ...$validated,
            'is_published' => $request->boolean('is_published'),
        ]);

        return redirect()->route('admin.menus.index')->with('success', 'メニューを更新しました。');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();

        return redirect()->route('admin.menus.index')->with('success', 'メニューを削除しました。');
    }
}
