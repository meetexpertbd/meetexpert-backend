@props([
    'all' => false,
    'id' => null,
    'disabled' => false,
])

@php
    $class = 'h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900';
@endphp

@if ($all)
    <th class="no-sort w-12 px-5 py-3">
        <input type="checkbox" class="js-dt-check-all {{ $class }}" aria-label="Select all">
    </th>
@else
    <td class="w-12 px-5 py-4">
        <input type="checkbox" class="js-dt-row-check {{ $class }}" value="{{ $id }}"
            @disabled($disabled) aria-label="Select row">
    </td>
@endif
