<div class="py-8">
    <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Présence du personnel</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pointage, retards, absences et abandons de poste.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('hr.discipline') }}" wire:navigate class="inline-flex items-center rounded-md border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">Sanctions</a>
                <a href="{{ route('hr.report') }}" wire:navigate class="inline-flex items-center rounded-md border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">Rapport RH</a>
            </div>
        </div>

        @if ($notice)
            <div class="rounded-md bg-blue-50 dark:bg-blue-900 p-3 text-blue-800 dark:text-blue-100 text-sm">{{ $notice }}</div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-medium text-gray-900 dark:text-gray-100">Enregistrer un pointage</h3>
            </div>

            <form wire:submit="saveAttendance" class="p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-12 gap-4">
                    <div class="xl:col-span-2">
                        <x-input-label for="workDate" value="Date" />
                        <x-text-input wire:model.live="workDate" id="workDate" class="block mt-1 w-full" type="date" />
                        <x-input-error :messages="$errors->get('workDate')" class="mt-2" />
                    </div>

                    <div class="xl:col-span-4">
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
                        <x-input-label for="status" value="Statut" />
                        <select wire:model.live="status" id="status" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm mt-1 block w-full">
                            <option value="present">Présent</option>
                            <option value="late">Retard</option>
                            <option value="absent">Absent</option>
                            <option value="abandoned">Abandon de poste</option>
                            <option value="rest">Repos</option>
                            <option value="leave">Congé</option>
                        </select>
                    </div>

                    <div class="xl:col-span-2">
                        <x-input-label for="scheduledStart" value="Arrivée prévue" />
                        <x-text-input wire:model="scheduledStart" id="scheduledStart" class="block mt-1 w-full" type="time" />
                    </div>

                    <div class="xl:col-span-2">
                        <x-input-label for="actualStart" value="Arrivée réelle" />
                        <x-text-input wire:model="actualStart" id="actualStart" class="block mt-1 w-full" type="time" />
                    </div>

                    <div class="xl:col-span-2">
                        <x-input-label for="scheduledEnd" value="Départ prévu" />
                        <x-text-input wire:model="scheduledEnd" id="scheduledEnd" class="block mt-1 w-full" type="time" />
                    </div>

                    <div class="xl:col-span-2">
                        <x-input-label for="actualEnd" value="Départ réel" />
                        <x-text-input wire:model="actualEnd" id="actualEnd" class="block mt-1 w-full" type="time" />
                    </div>

                    @if ($status === 'absent')
                        <label class="xl:col-span-2 flex items-center gap-2 pt-7 text-sm text-gray-700 dark:text-gray-300">
                            <input wire:model="absenceJustified" type="checkbox" class="rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500">
                            Absence justifiée
                        </label>
                    @endif

                    @if ($status === 'abandoned')
                        <div class="xl:col-span-2">
                            <x-input-label for="departureTime" value="Heure abandon" />
                            <x-text-input wire:model="departureTime" id="departureTime" class="block mt-1 w-full" type="time" />
                        </div>
                        <div class="xl:col-span-3">
                            <x-input-label for="abandonedPost" value="Poste abandonné" />
                            <x-text-input wire:model="abandonedPost" id="abandonedPost" class="block mt-1 w-full" type="text" />
                        </div>
                        <div class="xl:col-span-2">
                            <x-input-label for="abandonmentSeverity" value="Gravité" />
                            <select wire:model="abandonmentSeverity" id="abandonmentSeverity" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm mt-1 block w-full">
                                <option value="faible">Faible</option>
                                <option value="moyenne">Moyenne</option>
                                <option value="grave">Grave</option>
                            </select>
                        </div>
                    @endif
                </div>

                @if ($status === 'abandoned')
                    <div>
                        <x-input-label for="abandonmentExplanation" value="Explication donnée" />
                        <textarea wire:model="abandonmentExplanation" id="abandonmentExplanation" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"></textarea>
                    </div>
                @endif

                <div>
                    <x-input-label for="comment" value="Commentaire" />
                    <textarea wire:model="comment" id="comment" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"></textarea>
                </div>

                <x-primary-button>Enregistrer présence</x-primary-button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-medium text-gray-900 dark:text-gray-100">Présences du {{ \Illuminate\Support\Carbon::parse($workDate)->format('d/m/Y') }}</h3>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $attendances->count() }} ligne(s)</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-sm text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-6 py-3">Employé</th>
                            <th class="px-6 py-3">Horaires</th>
                            <th class="px-6 py-3">Statut</th>
                            <th class="px-6 py-3">Retard</th>
                            <th class="px-6 py-3">Retenue estimée</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($attendances as $attendance)
                            <tr wire:key="attendance-{{ $attendance->id }}">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $attendance->employeeName() }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ substr((string) $attendance->scheduled_start, 0, 5) ?: '--' }} - {{ substr((string) $attendance->scheduled_end, 0, 5) ?: '--' }}
                                    <div class="text-xs text-gray-500">{{ substr((string) $attendance->actual_start, 0, 5) ?: '--' }} - {{ substr((string) $attendance->actual_end, 0, 5) ?: '--' }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $this->statusLabel($attendance->status) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    @if ($attendance->late_minutes > 0)
                                        {{ $attendance->late_minutes }} min
                                        <div class="text-xs text-gray-500">{{ $this->lateSeverity($attendance->late_minutes) }}</div>
                                    @else
                                        --
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ number_format($this->suggestedDeduction($attendance), 0, ',', ' ') }} FCFA</td>
                                <td class="px-6 py-4 text-right">
                                    @if (in_array($attendance->status, ['late', 'absent', 'abandoned'], true))
                                        <button type="button" wire:click="createSanctionFromAttendance({{ $attendance->id }})" class="inline-flex items-center rounded-md border border-red-200 px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-gray-700">Créer sanction</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Aucune présence enregistrée pour cette date.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
