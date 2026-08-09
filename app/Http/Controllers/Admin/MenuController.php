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
        $categories = MenuCategory::query()
            ->with(['menus' => fn ($q) => $q->orderBy('menu_category_menu.sort_order')->orderBy('menus.id')])
            ->orderBy('sort_order')
            ->get();

        return view('admin.menus.index', compact('categories'));
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $request->merge([
            'categories' => $this->normalizeCategorySortOrders($request->input('categories')),
            'menus' => $this->normalizeMenuSortOrders(
                $this->normalizeMenuPrices($request->input('menus'))
            ),
        ]);

        $validated = $request->validate([
            'categories' => ['nullable', 'array'],
            'categories.*.name' => ['required', 'string', 'max:255'],
            'categories.*.sort_order' => ['nullable', 'integer', 'min:1'],
            'menus' => ['nullable', 'array'],
            'menus.*.name' => ['required', 'string', 'max:255'],
            'menus.*.price' => ['nullable', 'string', 'max:100'],
            'menus.*.is_published' => ['nullable', 'in:0,1'],
            'menus.*.description' => ['nullable', 'string'],
            'menus.*.category_ids' => ['nullable', 'array'],
            'menus.*.category_ids.*' => ['nullable'],
            'menus.*.sorts' => ['nullable', 'array'],
            'menus.*.sorts.*' => ['nullable', 'integer', 'min:1'],
            'selected_category_id' => ['nullable'],
        ], [
            'categories.*.name.required' => 'カテゴリ名は必須です。',
            'menus.*.name.required' => 'メニュー名は必須です。',
            'menus.*.sorts.*.required' => '表示順は必須です。',
            'menus.*.sorts.*.integer' => '表示順は整数で入力してください。',
            'menus.*.sorts.*.min' => '表示順は1以上で入力してください。',
            'menus.*.is_published.in' => '公開状態を選択してください。',
        ]);

        $categoryPayload = $validated['categories'] ?? [];
        $menuPayload = $validated['menus'] ?? [];
        $existingCategoryIds = MenuCategory::query()->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($menuPayload as $key => $data) {
            $categoryRefs = $this->normalizedCategoryRefs($data['category_ids'] ?? null);
            if ($categoryRefs === []) {
                return back()
                    ->withErrors(["menus.{$key}.category_ids" => 'カテゴリを1つ以上選択してください。'])
                    ->withInput();
            }

            foreach ($categoryRefs as $categoryRef) {
                if (preg_match('/^new_\d+$/', $categoryRef)) {
                    if (! array_key_exists($categoryRef, $categoryPayload)) {
                        return back()
                            ->withErrors(["menus.{$key}.category_ids" => 'カテゴリを指定してください。'])
                            ->withInput();
                    }
                } elseif (! in_array((int) $categoryRef, $existingCategoryIds, true)) {
                    return back()
                        ->withErrors(["menus.{$key}.category_ids" => '選択されたカテゴリは無効です。'])
                        ->withInput();
                }
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
                    'sort_order' => $data['sort_order'] ?? 1,
                ]);
            }

            foreach ($categoryPayload as $key => $data) {
                if (! preg_match('/^new_\d+$/', (string) $key)) {
                    continue;
                }

                $cat = MenuCategory::query()->create([
                    'name' => $data['name'],
                    'sort_order' => $data['sort_order'] ?? 1,
                ]);
                $newCategoryMap[(string) $key] = (int) $cat->id;
                $lastCreatedId = (int) $cat->id;
            }

            foreach ($menuPayload as $id => $data) {
                if (preg_match('/^new_menu_\d+$/', (string) $id)) {
                    continue;
                }

                $menu = Menu::query()->find($id);
                if (! $menu) {
                    continue;
                }

                $sync = $this->buildCategorySyncPayload(
                    $data['category_ids'] ?? [],
                    $data['sorts'] ?? [],
                    $newCategoryMap
                );

                $menu->update([
                    'name' => $data['name'],
                    'price' => $data['price'],
                    'sort_order' => $this->primarySortOrder($sync),
                    'is_published' => ($data['is_published'] ?? '0') === '1',
                    'description' => $data['description'] ?? null,
                ]);
                $menu->categories()->sync($sync);
            }

            foreach ($menuPayload as $id => $data) {
                if (! preg_match('/^new_menu_\d+$/', (string) $id)) {
                    continue;
                }

                $sync = $this->buildCategorySyncPayload(
                    $data['category_ids'] ?? [],
                    $data['sorts'] ?? [],
                    $newCategoryMap
                );

                $menu = Menu::query()->create([
                    'name' => $data['name'],
                    'price' => $data['price'],
                    'sort_order' => $this->primarySortOrder($sync),
                    'is_published' => ($data['is_published'] ?? '0') === '1',
                    'description' => $data['description'] ?? null,
                ]);
                $menu->categories()->sync($sync);
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

    /**
     * @param  mixed  $categories
     * @return array<string, array<string, mixed>>
     */
    private function normalizeCategorySortOrders(mixed $categories): array
    {
        if (! is_array($categories) || $categories === []) {
            return [];
        }

        $needsRenumber = false;
        $seen = [];
        foreach ($categories as $data) {
            if (! is_array($data)) {
                continue;
            }
            $raw = $data['sort_order'] ?? null;
            if ($raw === null || $raw === '' || ! is_numeric($raw) || (int) $raw < 1 || isset($seen[(int) $raw])) {
                $needsRenumber = true;
                break;
            }
            $seen[(int) $raw] = true;
        }

        if (! $needsRenumber) {
            return $categories;
        }

        $i = 1;
        foreach ($categories as $key => $data) {
            if (! is_array($data)) {
                continue;
            }
            $categories[$key]['sort_order'] = $i++;
        }

        return $categories;
    }

    /**
     * @param  mixed  $menus
     * @return array<string, array<string, mixed>>
     */
    private function normalizeMenuSortOrders(mixed $menus): array
    {
        if (! is_array($menus) || $menus === []) {
            return [];
        }

        $groups = [];
        foreach ($menus as $key => $data) {
            if (! is_array($data)) {
                continue;
            }

            $sorts = is_array($data['sorts'] ?? null) ? $data['sorts'] : [];
            if ($sorts === []) {
                $categoryRefs = $this->normalizedCategoryRefs($data['category_ids'] ?? null);
                $fallbackSort = $data['sort_order'] ?? 1;
                foreach ($categoryRefs as $categoryRef) {
                    $menus[$key]['sorts'][$categoryRef] = $fallbackSort;
                    $groups[$categoryRef][] = (string) $key;
                }
                continue;
            }

            foreach ($sorts as $categoryRef => $sort) {
                $groups[(string) $categoryRef][] = (string) $key;
            }
        }

        foreach ($groups as $categoryRef => $keys) {
            $keys = array_values(array_unique($keys));
            $needsRenumber = false;
            $seen = [];
            foreach ($keys as $key) {
                $raw = $menus[$key]['sorts'][$categoryRef] ?? null;
                if ($raw === null || $raw === '' || ! is_numeric($raw) || (int) $raw < 1 || isset($seen[(int) $raw])) {
                    $needsRenumber = true;
                    break;
                }
                $seen[(int) $raw] = true;
            }
            if (! $needsRenumber) {
                continue;
            }
            $i = 1;
            foreach ($keys as $key) {
                $menus[$key]['sorts'][$categoryRef] = $i++;
            }
        }

        return $menus;
    }

    /**
     * @param  mixed  $categoryIds
     * @return list<string>
     */
    private function normalizedCategoryRefs(mixed $categoryIds): array
    {
        if (! is_array($categoryIds)) {
            return [];
        }

        $refs = [];
        foreach ($categoryIds as $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $refs[] = (string) $value;
        }

        return array_values(array_unique($refs));
    }

    /**
     * @param  mixed  $categoryIds
     * @param  mixed  $sorts
     * @param  array<string, int>  $newCategoryMap
     * @return array<int, array{sort_order: int}>
     */
    private function buildCategorySyncPayload(mixed $categoryIds, mixed $sorts, array $newCategoryMap): array
    {
        $refs = $this->normalizedCategoryRefs($categoryIds);
        $sorts = is_array($sorts) ? $sorts : [];
        $sync = [];
        $i = 1;

        foreach ($refs as $ref) {
            if (preg_match('/^new_\d+$/', $ref)) {
                $categoryId = $newCategoryMap[$ref] ?? null;
            } else {
                $categoryId = (int) $ref;
            }

            if (! $categoryId) {
                continue;
            }

            $sort = $sorts[$ref] ?? $sorts[(string) $categoryId] ?? $i;
            $sync[$categoryId] = ['sort_order' => max(1, (int) $sort)];
            $i++;
        }

        return $sync;
    }

    /**
     * @param  array<int, array{sort_order: int}>  $sync
     */
    private function primarySortOrder(array $sync): int
    {
        if ($sync === []) {
            return 1;
        }

        // menus.sort_order follows the earliest category (by category display order),
        // so updating a secondary category's pivot sort does not rewrite it.
        $primaryCategoryId = MenuCategory::query()
            ->whereIn('id', array_keys($sync))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('id');

        if ($primaryCategoryId !== null && isset($sync[(int) $primaryCategoryId])) {
            return max(1, (int) $sync[(int) $primaryCategoryId]['sort_order']);
        }

        return max(1, (int) reset($sync)['sort_order']);
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
        $soleMenus = $category->menus()
            ->whereDoesntHave('categories', function ($query) use ($category) {
                $query->where('menu_categories.id', '!=', $category->id);
            })
            ->orderBy('menus.id')
            ->get(['menus.id', 'menus.name']);

        if ($soleMenus->isNotEmpty()) {
            $names = $soleMenus->pluck('name')->take(5)->implode('、');
            $suffix = $soleMenus->count() > 5 ? ' など' : '';

            return redirect()
                ->route('admin.menus.index')
                ->with('selected_category_id', $category->id)
                ->with(
                    'error',
                    'このカテゴリのみが設定されているメニューがあるため削除できません。（'.$names.$suffix.'）'
                );
        }

        $nextCategoryId = MenuCategory::query()
            ->where('id', '!=', $category->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('id');

        // Pivot rows are removed by FK ON DELETE CASCADE on menu_category_menu.
        // Menus that still belong to other categories are kept as-is.
        $category->delete();

        $redirect = redirect()
            ->route('admin.menus.index')
            ->with('success', 'カテゴリを削除しました。');

        if ($nextCategoryId !== null) {
            $redirect->with('selected_category_id', $nextCategoryId);
        }

        return $redirect;
    }

    public function create(MenuCategory $category): View
    {
        $categories = MenuCategory::query()->orderBy('sort_order')->get();

        return view('admin.menus.create', compact('category', 'categories'));
    }

    public function store(Request $request, MenuCategory $category): RedirectResponse
    {
        $request->merge([
            'price' => $this->normalizePrice($request->input('price')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:menu_categories,id'],
        ]);

        $categoryIds = collect($validated['category_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->push((int) $category->id)
            ->unique()
            ->values()
            ->all();

        $sortOrder = $validated['sort_order'] ?? 0;

        $menu = Menu::query()->create([
            'name' => $validated['name'],
            'price' => $validated['price'] ?? null,
            'description' => $validated['description'] ?? null,
            'sort_order' => $sortOrder,
            'is_published' => $request->boolean('is_published', true),
        ]);

        $sync = [];
        foreach ($categoryIds as $index => $categoryId) {
            $sync[$categoryId] = ['sort_order' => $categoryId === (int) $category->id ? max(1, (int) $sortOrder) : ($index + 1)];
        }
        $menu->categories()->sync($sync);

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'メニューを登録しました。')
            ->with('selected_category_id', $category->id);
    }

    public function edit(Menu $menu): View
    {
        $menu->load('categories');
        $categories = MenuCategory::query()->orderBy('sort_order')->get();

        return view('admin.menus.edit', compact('menu', 'categories'));
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $request->merge([
            'price' => $this->normalizePrice($request->input('price')),
        ]);

        $validated = $request->validate([
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:menu_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $categoryIds = array_values(array_unique(array_map('intval', $validated['category_ids'])));
        $sortOrder = max(1, (int) ($validated['sort_order'] ?? 1));
        $existingSorts = $menu->categories()->pluck('menu_category_menu.sort_order', 'menu_categories.id');

        $sync = [];
        foreach ($categoryIds as $index => $categoryId) {
            $sync[$categoryId] = [
                'sort_order' => (int) ($existingSorts[$categoryId] ?? ($index === 0 ? $sortOrder : ($index + 1))),
            ];
        }

        $menu->update([
            'name' => $validated['name'],
            'price' => $validated['price'] ?? null,
            'description' => $validated['description'] ?? null,
            'sort_order' => $sortOrder,
            'is_published' => $request->boolean('is_published'),
        ]);
        $menu->categories()->sync($sync);

        return redirect()->route('admin.menus.index')->with('success', 'メニューを更新しました。');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();

        return redirect()->route('admin.menus.index')->with('success', 'メニューを削除しました。');
    }

    /**
     * @param  mixed  $menus
     * @return array<string, array<string, mixed>>|mixed
     */
    private function normalizeMenuPrices(mixed $menus): mixed
    {
        if (! is_array($menus)) {
            return $menus;
        }

        foreach ($menus as $key => $data) {
            if (! is_array($data) || ! array_key_exists('price', $data)) {
                continue;
            }

            $menus[$key]['price'] = $this->normalizePrice(
                is_scalar($data['price']) ? (string) $data['price'] : null
            );
        }

        return $menus;
    }

    private function normalizePrice(mixed $price): ?string
    {
        if ($price === null) {
            return null;
        }

        if (! is_scalar($price)) {
            return null;
        }

        $trimmed = trim((string) $price);

        return $trimmed === '' ? null : $trimmed;
    }
}
