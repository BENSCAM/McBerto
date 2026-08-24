<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

@php
    $user = auth()->user();

    $managerGroups = [
        'Pilotage' => [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard'],
            ['label' => 'Rapport', 'route' => 'reports.daily', 'active' => 'reports.daily'],
        ],
        'Catalogue' => [
            ['label' => 'Catégories', 'route' => 'categories.index', 'active' => 'categories.index'],
            ['label' => 'Produits', 'route' => 'products.index', 'active' => 'products.index'],
            ['label' => 'Recettes', 'route' => 'product-recipes.index', 'active' => 'product-recipes.*'],
        ],
        'Stocks' => [
            ['label' => 'Matières premières', 'route' => 'raw-materials.index', 'active' => 'raw-materials.*'],
            ['label' => 'Achats matières', 'route' => 'raw-material-purchases.index', 'active' => 'raw-material-purchases.*'],
            ['label' => 'Mouvements stock', 'route' => 'stock-movements.index', 'active' => 'stock-movements.*'],
        ],
        'Finance' => [
            ['label' => 'Dépenses', 'route' => 'expenses.index', 'active' => 'expenses.index'],
            ['label' => 'Clôture', 'route' => 'pos.closing', 'active' => 'pos.closing'],
        ],
        'Système' => [
            ['label' => 'Utilisateurs', 'route' => 'users.index', 'active' => 'users.index'],
            ['label' => 'Historique', 'route' => 'system.history', 'active' => 'system.history'],
            ['label' => 'Bugs', 'route' => 'system.bugs', 'active' => 'system.bugs'],
        ],
    ];

    if ($user->isOwner()) {
        $managerGroups['Système'][] = ['label' => 'Reset', 'route' => 'system.reset', 'active' => 'system.reset'];
    }

    $operationLinks = [
        ['label' => 'Caisse', 'route' => 'pos.terminal', 'active' => 'pos.terminal'],
        ['label' => 'Clôture', 'route' => 'pos.closing', 'active' => 'pos.closing'],
        ['label' => 'Rapport', 'route' => 'reports.daily', 'active' => 'reports.daily'],
    ];

    $groups = $user->isAtLeastManager()
        ? [...$managerGroups, 'Opérations' => [['label' => 'Caisse', 'route' => 'pos.terminal', 'active' => 'pos.terminal']]]
        : ['Opérations' => $operationLinks];
@endphp

<nav x-data="{ open: false }">
    <div class="lg:hidden bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
        <div class="px-4 sm:px-6">
            <div class="flex h-16 items-center justify-between gap-4">
                <a href="{{ $user->isAtLeastManager() ? route('dashboard') : route('pos.terminal') }}" class="shrink-0">
                    <x-application-logo class="block h-9 w-auto rounded" />
                </a>

                <div class="flex min-w-0 items-center gap-3">
                    <div class="truncate text-sm font-medium text-gray-700 dark:text-gray-200">{{ $user->name }}</div>
                    <button @click="open = ! open" class="inline-flex h-10 w-10 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-900">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-gray-100 dark:border-gray-700">
            <div class="max-h-[calc(100vh-4rem)] overflow-y-auto py-3">
                @foreach ($groups as $groupLabel => $links)
                    <div class="px-4 pb-2 pt-3 text-xs font-semibold uppercase text-gray-400 dark:text-gray-500">{{ $groupLabel }}</div>
                    <div class="space-y-1">
                        @foreach ($links as $link)
                            <x-responsive-nav-link :href="route($link['route'])" :active="request()->routeIs($link['active'])">
                                {{ $link['label'] }}
                            </x-responsive-nav-link>
                        @endforeach
                    </div>
                @endforeach
            </div>

            <div class="border-t border-gray-200 px-4 py-4 dark:border-gray-700">
                <div class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $user->name }}</div>
                <div class="text-xs text-gray-500">{{ $user->email }}</div>
                <div class="mt-3 flex gap-3">
                    <a href="{{ route('profile') }}" wire:navigate class="text-sm text-gray-600 underline dark:text-gray-300">Profile</a>
                    <button wire:click="logout" class="text-sm text-red-600 underline dark:text-red-300">Log Out</button>
                </div>
            </div>
        </div>
    </div>

    <aside class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-40 lg:flex lg:w-72 lg:flex-col lg:border-r lg:border-gray-200 lg:bg-white lg:dark:border-gray-700 lg:dark:bg-gray-800">
        <div class="flex h-16 shrink-0 items-center border-b border-gray-100 px-6 dark:border-gray-700">
            <a href="{{ $user->isAtLeastManager() ? route('dashboard') : route('pos.terminal') }}" class="flex items-center gap-3">
                <x-application-logo class="block h-9 w-auto rounded" />
                <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">McBerto</span>
            </a>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-5">
            @foreach ($groups as $groupLabel => $links)
                <div class="{{ $loop->first ? '' : 'mt-6' }}">
                    <div class="px-3 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ $groupLabel }}</div>
                    <div class="mt-2 space-y-1">
                        @foreach ($links as $link)
                            @php($active = request()->routeIs($link['active']))
                            <a
                                href="{{ route($link['route']) }}"
                                wire:navigate
                                class="flex min-h-10 items-center rounded-md px-3 py-2 text-sm font-medium transition {{ $active ? 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-200' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white' }}"
                            >
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="border-t border-gray-100 p-4 dark:border-gray-700">
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700">
                        <span class="min-w-0">
                            <span class="block truncate font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</span>
                            <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</span>
                        </span>
                        <svg class="ms-3 h-4 w-4 shrink-0 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <x-dropdown-link :href="route('profile')" wire:navigate>
                        {{ __('Profile') }}
                    </x-dropdown-link>

                    <button wire:click="logout" class="w-full text-start">
                        <x-dropdown-link>
                            {{ __('Log Out') }}
                        </x-dropdown-link>
                    </button>
                </x-slot>
            </x-dropdown>
        </div>
    </aside>
</nav>
