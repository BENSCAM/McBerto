@props(['percent'])

@if ($percent === null)
    <span class="text-xs text-gray-400 dark:text-gray-500">Nouveau</span>
@elseif ($percent > 0)
    <span class="text-xs text-green-600 dark:text-green-400">▲ +{{ $percent }}% vs hier</span>
@elseif ($percent < 0)
    <span class="text-xs text-red-600 dark:text-red-400">▼ {{ $percent }}% vs hier</span>
@else
    <span class="text-xs text-gray-400 dark:text-gray-500">= vs hier</span>
@endif
