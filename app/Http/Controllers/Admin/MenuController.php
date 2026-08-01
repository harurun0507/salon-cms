<?php

namespace App\Http\Controllers\Admin;

use App\Models\Menu;
use App\Models\MenuCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ]);

        foreach ($validated['categories'] ?? [] as $id => $data) {
            MenuCategory::query()->whereKey($id)->update([
                'name' => $data['name'],
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
        }

        foreach ($validated['menus'] ?? [] as $id => $data) {
            Menu::query()->whereKey($id)->update([
                'name' => $data['name'],
                'price' => $data['price'],
                'sort_order' => $data['sort_order'] ?? 0,
                'is_published' => ($data['is_published'] ?? '0') === '1',
                'description' => $data['description'] ?? null,
            ]);
        }

        return redirect()->route('admin.menus.index')->with('success', 'メニュー情報を一括保存しました。');
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

        MenuCategory::create([
            'name' => $validated['name'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()->route('admin.menus.index')->with('success', 'カテゴリを登録しました。');
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

        return redirect()->route('admin.menus.index')->with('success', 'メニューを登録しました。');
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
