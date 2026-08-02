<?php

namespace App\Support;

class AdminNav
{
    /**
     * Resolve the sidebar icon key for the current admin route.
     * Prefers the matching leaf/child item so group parents are not used as page icons.
     */
    public static function currentPageIcon(?string $override = null): ?string
    {
        $override = is_string($override) ? trim($override) : null;
        if ($override !== null && $override !== '') {
            return $override;
        }

        foreach (config('admin_nav.items', []) as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (($item['type'] ?? '') === 'link') {
                $active = $item['active'] ?? null;
                if (is_string($active) && $active !== '' && request()->routeIs($active)) {
                    $icon = $item['icon'] ?? null;

                    return is_string($icon) && $icon !== '' ? $icon : null;
                }

                continue;
            }

            if (($item['type'] ?? '') === 'group') {
                foreach ($item['children'] ?? [] as $child) {
                    if (! is_array($child)) {
                        continue;
                    }

                    $active = $child['active'] ?? null;
                    if (is_string($active) && $active !== '' && request()->routeIs($active)) {
                        $icon = $child['icon'] ?? null;

                        return is_string($icon) && $icon !== '' ? $icon : null;
                    }
                }
            }
        }

        return null;
    }
}
