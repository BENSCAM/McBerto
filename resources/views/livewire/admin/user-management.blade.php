<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Gestion des utilisateurs</h2>

        @if ($error)
            <div class="rounded-md bg-red-50 dark:bg-red-900 p-3 text-red-800 dark:text-red-200 text-sm">
                {{ $error }}
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form wire:submit="createUser" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                <div>
                    <x-input-label for="name" value="Nom" />
                    <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="role" value="Rôle" />
                    <select wire:model="role" id="role" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm mt-1 block w-full">
                        <option value="cashier">Caissier</option>
                        <option value="manager">Gestionnaire</option>
                        <option value="owner">Propriétaire</option>
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="password" value="Mot de passe" />
                    <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" required />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
                <div class="sm:col-span-4">
                    <x-primary-button>Créer le compte</x-primary-button>
                </div>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 dark:bg-gray-700 text-sm text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-6 py-3">Nom</th>
                        <th class="px-6 py-3">Email</th>
                        <th class="px-6 py-3">Rôle</th>
                        <th class="px-6 py-3">Statut</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($this->users() as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td class="px-6 py-3 text-gray-900 dark:text-gray-100">{{ $user->name }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $user->email }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $user->role->label() }}</td>
                            <td class="px-6 py-3">
                                <span class="text-sm {{ $user->is_active ? 'text-green-600' : 'text-gray-400' }}">
                                    {{ $user->is_active ? 'Actif' : 'Désactivé' }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <button wire:click="toggleActive({{ $user->id }})" wire:confirm="Confirmer le changement de statut de ce compte ?" class="text-sm text-brand-600 dark:text-brand-400">
                                    {{ $user->is_active ? 'Désactiver' : 'Réactiver' }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
