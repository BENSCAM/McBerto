<div class="py-8">
    <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Gestion des utilisateurs</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                @if (auth()->user()->isOwner())
                    Création des comptes et gestion des accès à l'application.
                @else
                    Suivi comptable des comptes, postes et salaires.
                @endif
            </p>
        </div>

        @if ($error)
            <div class="rounded-md bg-red-50 dark:bg-red-900 p-3 text-red-800 dark:text-red-200 text-sm">
                {{ $error }}
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ $editingStaffId ? 'Modifier un membre du personnel' : 'Ajouter du personnel sans accès système' }}</h3>
            </div>

            <form wire:submit="saveStaffMember" class="p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <x-input-label for="staff_name" value="Nom" />
                        <x-text-input wire:model="staff_name" id="staff_name" class="block mt-1 w-full" type="text" required />
                        <x-input-error :messages="$errors->get('staff_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="staff_job_title" value="Poste occupé" />
                        <x-text-input wire:model="staff_job_title" id="staff_job_title" class="block mt-1 w-full" type="text" placeholder="Serveuse, cuisinier..." />
                        <x-input-error :messages="$errors->get('staff_job_title')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="staff_monthly_salary" value="Salaire mensuel (FCFA)" />
                        <x-text-input wire:model="staff_monthly_salary" id="staff_monthly_salary" class="block mt-1 w-full" type="number" min="0" required />
                        <x-input-error :messages="$errors->get('staff_monthly_salary')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="staff_note" value="Note" />
                        <x-text-input wire:model="staff_note" id="staff_note" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('staff_note')" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <x-primary-button>{{ $editingStaffId ? 'Mettre à jour' : 'Ajouter au personnel' }}</x-primary-button>
                    @if ($editingStaffId)
                        <button type="button" wire:click="cancelStaffEdit" class="text-sm text-gray-600 dark:text-gray-400 underline">Annuler</button>
                    @endif
                </div>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-medium text-gray-900 dark:text-gray-100">Personnel sans accès système</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-sm text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-6 py-3">Nom</th>
                            <th class="px-6 py-3">Poste</th>
                            <th class="px-6 py-3">Salaire mensuel</th>
                            <th class="px-6 py-3">Statut</th>
                            <th class="px-6 py-3">Note</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($this->staffMembers() as $staffMember)
                            <tr wire:key="staff-member-{{ $staffMember->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-6 py-3 text-gray-900 dark:text-gray-100">{{ $staffMember->name }}</td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $staffMember->job_title ?: 'Poste non renseigné' }}</td>
                                <td class="px-6 py-3 text-gray-900 dark:text-gray-100">{{ number_format($staffMember->monthly_salary, 0, ',', ' ') }} FCFA</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $staffMember->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-100' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $staffMember->is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $staffMember->note }}</td>
                                <td class="px-6 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" wire:click="editStaffMember({{ $staffMember->id }})" class="inline-flex items-center rounded-md border border-brand-200 dark:border-brand-800 px-2.5 py-1 text-xs font-medium text-brand-700 dark:text-brand-300 hover:bg-brand-50 dark:hover:bg-gray-700">Modifier</button>
                                        <button type="button" wire:click="toggleStaffActive({{ $staffMember->id }})" class="inline-flex items-center rounded-md border border-gray-200 dark:border-gray-700 px-2.5 py-1 text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                            {{ $staffMember->is_active ? 'Désactiver' : 'Réactiver' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center">
                                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Aucun personnel sans accès enregistré</div>
                                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ajoutez les serveuses, cuisiniers, communicateurs et autres postes sans compte de connexion.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

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
                        @if (auth()->user()->isOwner())
                            <select wire:model="role" id="role" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm mt-1 block w-full">
                                <option value="cashier">Caissier</option>
                                <option value="manager">Gestionnaire</option>
                                <option value="owner">Propriétaire</option>
                            </select>
                        @else
                            <input type="hidden" wire:model="role" value="cashier">
                            <div class="mt-1 rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-3 py-2 text-sm text-gray-700 dark:text-gray-300">
                                Caissier
                            </div>
                        @endif
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="job_title" value="Poste occupé" />
                        <x-text-input wire:model="job_title" id="job_title" class="block mt-1 w-full" type="text" placeholder="Caissier, cuisinier, serveur..." />
                        <x-input-error :messages="$errors->get('job_title')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="monthly_salary" value="Salaire mensuel (FCFA)" />
                        <x-text-input wire:model="monthly_salary" id="monthly_salary" class="block mt-1 w-full" type="number" min="0" required />
                        <x-input-error :messages="$errors->get('monthly_salary')" class="mt-2" />
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
                        <th class="px-6 py-3">Poste / salaire</th>
                        <th class="px-6 py-3">Statut</th>
                        <th class="px-6 py-3">Date autorisée</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($this->users() as $user)
                        <tr wire:key="user-{{ $user->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-6 py-3 text-gray-900 dark:text-gray-100">{{ $user->name }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $user->email }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $user->role->label() }}</td>
                            <td class="px-6 py-3 min-w-64">
                                @if (isset($employment[$user->id]))
                                    <div class="space-y-2">
                                        <x-text-input wire:model="employment.{{ $user->id }}.job_title" class="block w-full text-sm" type="text" placeholder="Poste occupé" />
                                        <x-input-error :messages="$errors->get('employment.'.$user->id.'.job_title')" class="mt-1" />
                                        <x-text-input wire:model="employment.{{ $user->id }}.monthly_salary" class="block w-full text-sm" type="number" min="0" placeholder="Salaire mensuel" />
                                        <x-input-error :messages="$errors->get('employment.'.$user->id.'.monthly_salary')" class="mt-1" />
                                        <div class="flex gap-2">
                                            <button type="button" wire:click="saveEmployment({{ $user->id }})" class="inline-flex items-center rounded-md border border-brand-200 dark:border-brand-800 px-2.5 py-1 text-xs font-medium text-brand-700 dark:text-brand-300 hover:bg-brand-50 dark:hover:bg-gray-700">Enregistrer</button>
                                            <button type="button" wire:click="cancelEmploymentEdit({{ $user->id }})" class="inline-flex items-center rounded-md border border-gray-200 dark:border-gray-700 px-2.5 py-1 text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Annuler</button>
                                        </div>
                                    </div>
                                @else
                                    <div class="space-y-1">
                                        <div class="text-sm text-gray-900 dark:text-gray-100">{{ $user->job_title ?: 'Poste non renseigné' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($user->monthly_salary, 0, ',', ' ') }} FCFA / mois</div>
                                        <button type="button" wire:click="editEmployment({{ $user->id }})" class="inline-flex items-center rounded-md border border-gray-200 dark:border-gray-700 px-2.5 py-1 text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Modifier</button>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $user->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-100' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $user->is_active ? 'Actif' : 'Désactivé' }}
                                </span>
                            </td>
                            <td class="px-6 py-3">
                                @if ($user->isCashier())
                                    <div class="space-y-2 min-w-56">
                                        @if ($user->can_backdate_sales && $user->backdate_sales_date)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100">
                                                {{ $user->backdate_sales_date->format('d/m/Y') }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-500 dark:text-gray-400">Aucune</span>
                                        @endif

                                        <div class="flex flex-wrap items-start gap-2">
                                            <div>
                                                <input
                                                    type="date"
                                                    wire:model="backdateSaleDates.{{ $user->id }}"
                                                    max="{{ now()->toDateString() }}"
                                                    class="w-36 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-xs focus:border-brand-500 focus:ring-brand-500"
                                                >
                                                <x-input-error :messages="$errors->get('backdateSaleDates.'.$user->id)" class="mt-1" />
                                            </div>
                                            <button
                                                type="button"
                                                wire:click="authorizeBackdatedSales({{ $user->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="authorizeBackdatedSales({{ $user->id }})"
                                                class="inline-flex items-center rounded-md border border-amber-200 dark:border-amber-800 px-2.5 py-1 text-xs font-medium text-amber-800 dark:text-amber-200 hover:bg-amber-50 dark:hover:bg-gray-700 disabled:opacity-50"
                                            >Autoriser</button>
                                            @if ($user->can_backdate_sales)
                                                <button
                                                    type="button"
                                                    x-on:click="$store.confirmModal.open('Retirer cette autorisation de date ?', () => $wire.revokeBackdatedSales({{ $user->id }}))"
                                                    class="inline-flex items-center rounded-md border border-gray-200 dark:border-gray-700 px-2.5 py-1 text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                                >Retirer</button>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">Non applicable</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right">
                                @if ($this->canManageUser($user))
                                    <button x-on:click="$store.confirmModal.open('Confirmer le changement de statut de ce compte ?', () => $wire.toggleActive({{ $user->id }}))" class="inline-flex items-center rounded-md border border-brand-200 dark:border-brand-800 px-2.5 py-1 text-xs font-medium text-brand-700 dark:text-brand-300 hover:bg-brand-50 dark:hover:bg-gray-700">
                                        {{ $user->is_active ? 'Désactiver' : 'Réactiver' }}
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">Accès protégé</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center">
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Aucun utilisateur enregistré</div>
                                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Créez un compte pour donner accès à l'application.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        {{ $this->users()->links() }}
    </div>
</div>
