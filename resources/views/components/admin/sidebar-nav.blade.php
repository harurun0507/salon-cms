@php
    $items = config('admin_nav.items', []);
    $external = config('admin_nav.external');
    $userRole = auth()->user()?->role ?? null;

    $canSee = function (array $entry) use ($userRole): bool {
        $roles = $entry['roles'] ?? null;
        if ($roles === null || $roles === []) {
            return true;
        }

        return $userRole !== null && in_array($userRole, $roles, true);
    };
@endphp

<nav class="relative z-10 flex min-h-0 flex-1 flex-col px-4 pb-4 pt-2 text-sm" aria-label="管理メニュー" data-admin-sidebar-nav>
    <div class="min-h-0 flex-1 space-y-2.5 overflow-y-auto pb-2">
        @foreach ($items as $item)
            @if (! $canSee($item))
                @continue
            @endif
            @if (($item['type'] ?? '') === 'link')
                <a
                    href="{{ route($item['route']) }}"
                    class="admin-nav-link {{ request()->routeIs($item['active']) ? 'admin-nav-link-active' : '' }}"
                >
                    <x-admin.nav-icon :name="$item['icon']" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @elseif (($item['type'] ?? '') === 'group')
                @php
                    $visibleChildren = collect($item['children'] ?? [])->filter(fn (array $child) => $canSee($child))->values()->all();
                    if ($visibleChildren === []) {
                        continue;
                    }
                    $groupKey = $item['key'] ?? \Illuminate\Support\Str::slug($item['label'] ?? 'group');
                    $isGroupOpen = collect($visibleChildren)->contains(
                        fn (array $child) => request()->routeIs($child['active'])
                    );
                @endphp
                <details
                    class="admin-nav-group"
                    data-nav-key="{{ $groupKey }}"
                    @if ($isGroupOpen) data-nav-current="1" open @endif
                >
                    <summary class="admin-nav-parent">
                        <x-admin.nav-icon :name="$item['icon']" />
                        <span class="min-w-0 flex-1">{{ $item['label'] }}</span>
                        <svg
                            class="admin-nav-chevron h-4 w-4 text-admin-icon"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="m6 9 6 6 6-6" />
                        </svg>
                    </summary>
                    <div class="admin-nav-children mt-0.5 space-y-0.5">
                        @foreach ($visibleChildren as $child)
                            <a
                                href="{{ route($child['route']) }}"
                                class="admin-nav-link admin-nav-link-child {{ request()->routeIs($child['active']) ? 'admin-nav-link-active' : '' }}"
                            >
                                @if (! empty($child['icon']))
                                    <x-admin.nav-icon :name="$child['icon']" class="admin-nav-child-icon" />
                                @endif
                                <span>{{ $child['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </details>
            @endif
        @endforeach
    </div>

    @if ($external)
        <div class="mt-auto shrink-0 border-t border-admin-border/50 bg-[#F6F2EA]/95 pt-3 backdrop-blur-[1px]">
            <a
                href="{{ route($external['route']) }}"
                @if (! empty($external['target'])) target="{{ $external['target'] }}" rel="noopener noreferrer" @endif
                class="admin-nav-link"
            >
                <x-admin.nav-icon :name="$external['icon']" />
                <span>{{ $external['label'] }}</span>
            </a>
        </div>
    @endif
</nav>

<script>
    (function () {
        const STORAGE_KEY = 'admin-sidebar-open-parents';
        const pending = document.querySelectorAll('[data-admin-sidebar-nav]:not([data-admin-sidebar-nav-ready])');
        if (pending.length === 0) {
            return;
        }

        function readOpenKeys() {
            try {
                const parsed = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
                if (!Array.isArray(parsed)) {
                    return [];
                }

                return parsed.filter((key) => typeof key === 'string');
            } catch (e) {
                return [];
            }
        }

        function writeOpenKeys(keys) {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify([...new Set(keys)]));
            } catch (e) {
                // Ignore quota / private-mode failures.
            }
        }

        pending.forEach((nav) => {
            nav.setAttribute('data-admin-sidebar-nav-ready', '1');

            const groups = Array.from(nav.querySelectorAll('details.admin-nav-group[data-nav-key]'));
            if (groups.length === 0) {
                return;
            }

            const openKeys = new Set(readOpenKeys());

            groups.forEach((details) => {
                if (details.getAttribute('data-nav-current') === '1') {
                    openKeys.add(details.getAttribute('data-nav-key'));
                }
            });

            groups.forEach((details) => {
                const key = details.getAttribute('data-nav-key');
                details.open = openKeys.has(key);
            });

            writeOpenKeys([...openKeys]);

            groups.forEach((details) => {
                details.addEventListener('toggle', function () {
                    const next = groups
                        .filter((group) => group.open)
                        .map((group) => group.getAttribute('data-nav-key'));
                    writeOpenKeys(next);
                });
            });
        });
    })();
</script>
