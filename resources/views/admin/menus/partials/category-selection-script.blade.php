<script>
    (function () {
        function categoryAllowsMultiple(input) {
            return String(input.getAttribute('data-allow-multiple') || '0') === '1';
        }

        document.addEventListener('change', function (e) {
            const changed = e.target.closest('[data-menu-category-checkbox]');
            if (!changed || !changed.checked) {
                return;
            }

            const form = changed.closest('form');
            if (!form) {
                return;
            }

            const checks = Array.from(form.querySelectorAll('[data-menu-category-checkbox]'));
            const hasMultiAllow = checks.some(function (input) {
                return input.checked && categoryAllowsMultiple(input);
            });
            if (hasMultiAllow || categoryAllowsMultiple(changed)) {
                return;
            }

            checks.forEach(function (input) {
                if (input !== changed && input.checked && !categoryAllowsMultiple(input)) {
                    input.checked = false;
                }
            });
        });
    })();
</script>
