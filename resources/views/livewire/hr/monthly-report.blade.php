<div class="py-8">
    <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Rapport RH mensuel</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Présences, retards, absences, sanctions et salaire net estimé.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('hr.attendance') }}" wire:navigate class="inline-flex items-center rounded-md border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">Présence</a>
                <a href="{{ route('hr.discipline') }}" wire:navigate class="inline-flex items-center rounded-md border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">Sanctions</a>
            </div>
        </div>

        @if ($notice)
            <div class="rounded-md bg-blue-50 dark:bg-blue-900 p-3 text-blue-800 dark:text-blue-100 text-sm">{{ $notice }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700 p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Retenues du mois</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($dashboard['total_deductions'], 0, ',', ' ') }} FCFA</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700 p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Retards</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $dashboard['late_total'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700 p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Absences non justifiées</div>
                <div class="mt-1 text-2xl font-semibold text-red-600 dark:text-red-300">{{ $dashboard['unjustified_absences'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700 p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Abandons de poste</div>
                <div class="mt-1 text-2xl font-semibold text-red-600 dark:text-red-300">{{ $dashboard['abandonments'] }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-medium text-gray-900 dark:text-gray-100">Paramètres et période</h3>
                <x-text-input wire:model.live="month" type="month" class="w-44" />
            </div>

            <form wire:submit="saveSettings" class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 items-end">
                <div>
                    <x-input-label for="plannedWorkingDays" value="Jours/mois" />
                    <x-text-input wire:model="plannedWorkingDays" id="plannedWorkingDays" class="block mt-1 w-full" type="number" min="1" max="31" />
                </div>
                <div>
                    <x-input-label for="plannedWorkingHours" value="Heures/jour" />
                    <x-text-input wire:model="plannedWorkingHours" id="plannedWorkingHours" class="block mt-1 w-full" type="number" min="1" max="24" />
                </div>
                <div>
                    <x-input-label for="simpleLateThreshold" value="Seuil observation" />
                    <x-text-input wire:model="simpleLateThreshold" id="simpleLateThreshold" class="block mt-1 w-full" type="number" min="1" />
                </div>
                <div>
                    <x-input-label for="sanctionableLateThreshold" value="Seuil sanction" />
                    <x-text-input wire:model="sanctionableLateThreshold" id="sanctionableLateThreshold" class="block mt-1 w-full" type="number" min="1" />
                </div>
                <x-primary-button>Enregistrer</x-primary-button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-sm text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-6 py-3">Employé</th>
                            <th class="px-6 py-3">Présences</th>
                            <th class="px-6 py-3">Retards</th>
                            <th class="px-6 py-3">Absences</th>
                            <th class="px-6 py-3">Abandons</th>
                            <th class="px-6 py-3">Retenues</th>
                            <th class="px-6 py-3">Salaire net estimé</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($rows as $row)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $row['name'] }}
                                    <div class="text-xs font-normal text-gray-500">{{ $row['job_title'] }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $row['present_days'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $row['late_count'] }} / {{ $row['late_minutes'] }} min</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $row['justified_absences'] }} just. / {{ $row['unjustified_absences'] }} non just.</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $row['abandonments'] }}</td>
                                <td class="px-6 py-4 text-sm text-red-600 dark:text-red-300">{{ number_format($row['deductions'], 0, ',', ' ') }} FCFA</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ number_format($row['net_salary'], 0, ',', ' ') }} FCFA
                                    <div class="text-xs font-normal text-gray-500">Brut {{ number_format($row['gross_salary'], 0, ',', ' ') }} FCFA</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Aucun employé actif.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 font-medium text-gray-900 dark:text-gray-100">Employés les plus ponctuels</div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($dashboard['most_punctual'] as $row)
                        <div class="px-6 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $row['name'] }} <span class="text-gray-500">- {{ $row['present_days'] }} présence(s)</span></div>
                    @empty
                        <div class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">Aucune donnée.</div>
                    @endforelse
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 font-medium text-gray-900 dark:text-gray-100">Plus de retards</div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($dashboard['most_late'] as $row)
                        <div class="px-6 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $row['name'] }} <span class="text-gray-500">- {{ $row['late_count'] }} retard(s)</span></div>
                    @empty
                        <div class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">Aucune donnée.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
