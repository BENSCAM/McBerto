<div class="py-8">
    <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Historique disciplinaire</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sanctions, retenues et fautes du personnel.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('hr.attendance') }}" wire:navigate class="inline-flex items-center rounded-md border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">Présence</a>
                <a href="{{ route('hr.report') }}" wire:navigate class="inline-flex items-center rounded-md border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">Rapport RH</a>
            </div>
        </div>

        @if ($notice)
            <div class="rounded-md bg-blue-50 dark:bg-blue-900 p-3 text-blue-800 dark:text-blue-100 text-sm">{{ $notice }}</div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-medium text-gray-900 dark:text-gray-100">Créer une sanction</h3>
                <select wire:model.live="status" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500">
                    <option value="all">Toutes</option>
                    <option value="draft">Brouillons</option>
                    <option value="validated">Validées</option>
                    <option value="canceled">Annulées</option>
                </select>
            </div>

            <form wire:submit="createSanction" class="p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-12 gap-4">
                    <div class="xl:col-span-3">
                        <x-input-label for="employeeKey" value="Employé" />
                        <select wire:model="employeeKey" id="employeeKey" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm mt-1 block w-full">
                            <option value="">-- Choisir --</option>
                            @foreach ($this->employees() as $employee)
                                <option value="{{ $employee['key'] }}">{{ $employee['name'] }} - {{ $employee['job_title'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('employeeKey')" class="mt-2" />
                    </div>
                    <div class="xl:col-span-2">
                        <x-input-label for="faultType" value="Type de faute" />
                        <select wire:model="faultType" id="faultType" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm mt-1 block w-full">
                            <option value="late">Retard</option>
                            <option value="absence">Absence</option>
                            <option value="abandonment">Abandon de poste</option>
                            <option value="other">Autre</option>
                        </select>
                    </div>
                    <div class="xl:col-span-2">
                        <x-input-label for="faultDate" value="Date faute" />
                        <x-text-input wire:model="faultDate" id="faultDate" class="block mt-1 w-full" type="date" />
                    </div>
                    <div class="xl:col-span-3">
                        <x-input-label for="sanctionType" value="Sanction" />
                        <select wire:model="sanctionType" id="sanctionType" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm mt-1 block w-full">
                            <option value="verbal_reminder">Rappel verbal</option>
                            <option value="written_warning">Avertissement écrit</option>
                            <option value="salary_deduction">Retenue sur salaire</option>
                            <option value="suspension">Suspension</option>
                            <option value="last_warning">Dernier avertissement</option>
                            <option value="end_collaboration">Fin de collaboration</option>
                        </select>
                    </div>
                    <div class="xl:col-span-2">
                        <x-input-label for="deductionAmount" value="Retenue FCFA" />
                        <x-text-input wire:model="deductionAmount" id="deductionAmount" class="block mt-1 w-full" type="number" min="0" />
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="description" value="Description" />
                        <textarea wire:model="description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"></textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="comment" value="Commentaire" />
                        <textarea wire:model="comment" id="comment" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"></textarea>
                    </div>
                </div>

                <x-primary-button>Créer sanction</x-primary-button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-sm text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-6 py-3">Employé</th>
                            <th class="px-6 py-3">Faute</th>
                            <th class="px-6 py-3">Sanction</th>
                            <th class="px-6 py-3">Statut</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($sanctions as $sanction)
                            <tr wire:key="sanction-{{ $sanction->id }}">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $sanction->employeeName() }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $this->faultLabel($sanction->fault_type) }} du {{ $sanction->fault_date->format('d/m/Y') }}
                                    <div class="max-w-xl truncate text-xs text-gray-500">{{ $sanction->description }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $this->sanctionLabel($sanction->sanction_type) }}
                                    <div class="text-xs text-gray-500">{{ number_format($sanction->deduction_amount, 0, ',', ' ') }} FCFA</div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $sanction->status === 'validated' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-100' : ($sanction->status === 'canceled' ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-100') }}">
                                        {{ $sanction->status === 'validated' ? 'Validée' : ($sanction->status === 'canceled' ? 'Annulée' : 'Brouillon') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                    @if ($sanction->status !== 'validated')
                                        <button type="button" wire:click="validateSanction({{ $sanction->id }})" class="inline-flex items-center rounded-md border border-green-200 px-2.5 py-1 text-xs font-medium text-green-700 hover:bg-green-50 dark:border-green-800 dark:text-green-300 dark:hover:bg-gray-700">Valider</button>
                                    @endif
                                    @if ($sanction->status !== 'canceled')
                                        <button type="button" wire:click="cancelSanction({{ $sanction->id }})" class="inline-flex items-center rounded-md border border-red-200 px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-gray-700">Annuler</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Aucune sanction enregistrée.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $sanctions->links() }}
    </div>
</div>
