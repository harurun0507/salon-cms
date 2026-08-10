@props([
    'name',
    'inputId',
    'selectedId' => '',
    'staffMembers',
    'label' => '担当スタッフ',
])

@include('admin.galleries.partials.staff-picker-assets')

@php
    $selected = filled($selectedId) ? (string) $selectedId : '';
    $labelId = $inputId.'_label';
@endphp

<div>
    <span class="admin-label" id="{{ $labelId }}">{{ $label }}</span>
    <input
        type="hidden"
        name="{{ $name }}"
        id="{{ $inputId }}"
        value="{{ $selected }}"
        data-gallery-staff-input
    >
    @if ($staffMembers->isEmpty())
        <p class="mt-1 text-xs text-admin-muted">スタッフが登録されていません。</p>
    @else
        <div
            class="gallery-staff-choices mt-1"
            role="group"
            aria-labelledby="{{ $labelId }}"
            data-gallery-staff-choices
        >
            @foreach ($staffMembers as $member)
                @php
                    $isSelected = $selected !== '' && $selected === (string) $member->id;
                @endphp
                <button
                    type="button"
                    class="gallery-staff-chip{{ $isSelected ? ' is-selected' : '' }}"
                    data-gallery-staff-option
                    data-staff-id="{{ $member->id }}"
                    aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                >
                    @if (filled($member->photo_path))
                        <img
                            src="{{ asset('storage/'.$member->photo_path) }}"
                            alt=""
                            class="gallery-staff-chip-avatar"
                        >
                    @endif
                    <span class="gallery-staff-chip-name">{{ $member->name }}</span>
                </button>
            @endforeach
        </div>
    @endif
</div>
