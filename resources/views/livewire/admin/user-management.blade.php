<div class="py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Gestion des utilisateurs</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Création des comptes et gestion des accès à l'application.</p>
        </div>

        @if ($error)
            <div class="rounded-md bg-red-50 dark:bg-red-900 p-3 text-red-800 dark:text-red-200 text-sm">
                {{ $error }}
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-medium text-gray-900 dark:text-gray-100">Créer un compte</h3>
            </div>

            <form wire:submit="createUser" class="p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <x-primary-button>Créer le compte</x-primary-button>
                </div>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
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
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $user->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-100' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $user->is_active ? 'Actif' : 'Désactivé' }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <button x-on:click="$store.confirmModal.open('Confirmer le changement de statut de ce compte ?', () => $wire.toggleActive({{ $user->id }}))" class="text-sm text-brand-600 dark:text-brand-400">
                                    {{ $user->is_active ? 'Désactiver' : 'Réactiver' }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>

        {{ $this->users()->links() }}
    </div>
</div>
