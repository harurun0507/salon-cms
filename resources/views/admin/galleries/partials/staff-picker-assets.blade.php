@once
    <style>
        .gallery-staff-choices {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            width: 100%;
            max-width: 100%;
        }
        .gallery-staff-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            min-height: 2.5rem;
            max-width: 100%;
            margin: 0;
            padding: 0.375rem 0.75rem 0.375rem 0.5rem;
            border: 1px solid #E5E0D7;
            border-radius: 0.5rem;
            background-color: #ffffff;
            color: #3D3833;
            font-size: 0.875rem;
            line-height: 1.25;
            text-align: left;
            cursor: pointer;
            transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .gallery-staff-chip:hover {
            background-color: #F7F5F0;
            border-color: #D8D2C7;
        }
        .gallery-staff-chip.is-selected {
            background-color: #E5EADD;
            border-color: #697A55;
            color: #556344;
            font-weight: 600;
        }
        .gallery-staff-chip:hover.is-selected {
            background-color: #DDE5D2;
        }
        .gallery-staff-chip:focus {
            outline: none;
        }
        .gallery-staff-chip:focus-visible {
            box-shadow: 0 0 0 3px rgba(105, 122, 85, 0.25);
        }
        .gallery-staff-chip-avatar {
            width: 1.75rem;
            height: 1.75rem;
            flex-shrink: 0;
            border-radius: 9999px;
            object-fit: cover;
            background-color: #F1ECE3;
        }
        .gallery-staff-chip-name {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
    <script>
        (function () {
            if (window.__galleryStaffPickerBound) {
                return;
            }
            window.__galleryStaffPickerBound = true;

            window.bindGalleryStaffPicker = function (root) {
                const scope = root && root.querySelector ? root : document;
                const groups = scope.matches && scope.matches('[data-gallery-staff-choices]')
                    ? [scope]
                    : Array.prototype.slice.call(scope.querySelectorAll('[data-gallery-staff-choices]'));

                groups.forEach(function (choices) {
                    if (choices.getAttribute('data-gallery-staff-bound') === '1') {
                        return;
                    }
                    choices.setAttribute('data-gallery-staff-bound', '1');

                    const wrap = choices.parentElement;
                    const input = wrap
                        ? wrap.querySelector('[data-gallery-staff-input]')
                        : null;
                    if (!input) {
                        return;
                    }

                    choices.addEventListener('click', function (e) {
                        const btn = e.target.closest('[data-gallery-staff-option]');
                        if (!btn || !choices.contains(btn)) {
                            return;
                        }

                        const id = String(btn.getAttribute('data-staff-id') || '');
                        const nextValue = String(input.value) === id ? '' : id;
                        input.value = nextValue;

                        choices.querySelectorAll('[data-gallery-staff-option]').forEach(function (el) {
                            const selected = nextValue !== '' && String(el.getAttribute('data-staff-id')) === nextValue;
                            el.classList.toggle('is-selected', selected);
                            el.setAttribute('aria-pressed', selected ? 'true' : 'false');
                        });
                    });
                });
            };

            document.addEventListener('DOMContentLoaded', function () {
                window.bindGalleryStaffPicker(document);
            });
        })();
    </script>
@endonce
