<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">School records</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Attendance register</h2>
            <p class="mt-1 max-w-2xl text-sm text-slate-600">Choose a class and date, mark every active learner, then save the whole register in one operation.</p>
        </div>

        @if ($registerLoaded)
            <div class="flex flex-wrap justify-end gap-2">
                <x-button wire:click="markAll('present')" variant="success" size="sm" target="markAll('present')" :loading="true">Mark all present</x-button>
                <x-button wire:click="markAll('absent')" variant="ghost" size="sm" target="markAll('absent')" :loading="true">Mark all absent</x-button>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-4 gap-4">
        <article class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-blue-800">Learners</p>
            <p class="mt-2 text-3xl font-bold text-blue-900">{{ $registerSummary['total'] }}</p>
            <p class="mt-1 text-xs text-blue-700">In the loaded register</p>
        </article>
        <article class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-emerald-800">Present</p>
            <p class="mt-2 text-3xl font-bold text-emerald-900">{{ $registerSummary['present'] }}</p>
            <p class="mt-1 text-xs text-emerald-700">Marked present today</p>
        </article>
        <article class="rounded-2xl border border-rose-100 bg-rose-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-rose-800">Absent</p>
            <p class="mt-2 text-3xl font-bold text-rose-900">{{ $registerSummary['absent'] }}</p>
            <p class="mt-1 text-xs text-rose-700">Requires follow-up</p>
        </article>
        <article class="rounded-2xl border border-amber-100 bg-amber-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-amber-800">Late or excused</p>
            <p class="mt-2 text-3xl font-bold text-amber-900">{{ $registerSummary['late'] + $registerSummary['excused'] }}</p>
            <p class="mt-1 text-xs text-amber-700">{{ $registerSummary['late'] }} late · {{ $registerSummary['excused'] }} excused</p>
        </article>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form wire:submit="loadRegister" class="space-y-4">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div>
                    <h3 class="font-semibold text-slate-900">Attendance context</h3>
                    <p class="mt-1 text-sm text-slate-600">Changing a selection clears an open register to prevent recording against the wrong class or term.</p>
                </div>
                <span wire:loading wire:target="academicYearId,termId,classId,attendanceDate" class="text-xs font-medium text-slate-500">Refreshing options…</span>
            </div>

            <div class="grid gap-4 xl:grid-cols-4">
                <div>
                    <label for="attendance-academic-year" class="block text-sm font-medium text-slate-700">Academic year</label>
                    <select wire:model.live="academicYearId" id="attendance-academic-year" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                        <option value="">Choose an academic year</option>
                        @foreach ($years as $year)
                            <option value="{{ $year->id }}">{{ $year->name }}</option>
                        @endforeach
                    </select>
                    @error('academicYearId') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="attendance-term" class="block text-sm font-medium text-slate-700">Term</label>
                    <select wire:model.live="termId" id="attendance-term" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                        <option value="">Choose a term</option>
                        @foreach ($terms->when(filled($academicYearId), fn ($items) => $items->where('academic_year_id', (int) $academicYearId)) as $term)
                            <option value="{{ $term->id }}">{{ $term->name }}</option>
                        @endforeach
                    </select>
                    @error('termId') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="attendance-class" class="block text-sm font-medium text-slate-700">Class</label>
                    <select wire:model.live="classId" id="attendance-class" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                        <option value="">Choose a class</option>
                        @foreach ($classes->when(filled($academicYearId), fn ($items) => $items->where('academic_year_id', (int) $academicYearId)) as $class)
                            <option value="{{ $class->id }}">{{ $class->name }} ({{ $class->active_students_count }} active)</option>
                        @endforeach
                    </select>
                    @error('classId') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="attendance-date" class="block text-sm font-medium text-slate-700">Attendance date</label>
                    <input wire:model.live="attendanceDate" id="attendance-date" type="date" max="{{ now()->toDateString() }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                    @error('attendanceDate') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end border-t border-slate-100 pt-4">
                <x-button type="submit" variant="primary" icon="sparkles" target="loadRegister" :loading="true">Load register</x-button>
            </div>
        </form>
    </section>

    @if ($registerLoaded)
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col justify-between gap-4 border-b border-slate-200 px-5 py-4 lg:flex-row lg:items-center">
                <div>
                    <h3 class="font-semibold text-slate-900">Class register</h3>
                    <p class="mt-1 text-sm text-slate-600">Search filters the rows shown below; saving still records every learner in this loaded class.</p>
                </div>
                <div class="relative w-full lg:max-w-sm">
                    <label for="attendance-student-search" class="sr-only">Search learners in the register</label>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m20 20-3.5-3.5"></path>
                    </svg>
                    <input wire:model.live.debounce.300ms="studentSearch" id="attendance-student-search" type="search" autocomplete="off" placeholder="Search learner or admission number" class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-24 text-sm shadow-sm transition focus:border-blue-700 focus:ring-blue-700">
                    <span wire:loading wire:target="studentSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching…</span>
                </div>
            </div>

            <form wire:submit="save">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Learner</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($students as $enrollment)
                                <tr wire:key="attendance-{{ $enrollment->student_id }}" class="hover:bg-slate-50/80">
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-slate-900">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $enrollment->student->admission_number ?: $enrollment->student->student_id }}</p>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-2">
                                            <select wire:model.live="statuses.{{ $enrollment->student_id }}" class="min-w-32 rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                                                <option value="present">Present</option>
                                                <option value="absent">Absent</option>
                                                <option value="late">Late</option>
                                                <option value="excused">Excused</option>
                                            </select>
                                            <span wire:loading wire:target="statuses.{{ $enrollment->student_id }}" class="text-xs text-slate-500">Updating…</span>
                                        </div>
                                        @error("statuses.{$enrollment->student_id}") <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="px-5 py-4">
                                        <input wire:model.blur="remarks.{{ $enrollment->student_id }}" type="text" maxlength="1000" placeholder="Optional note" class="block w-full min-w-56 rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                                        @error("remarks.{$enrollment->student_id}") <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-5 py-14 text-center text-slate-500">
                                        @if (filled($studentSearch))
                                            No active learners match “{{ $studentSearch }}”.
                                            <x-button wire:click="clearStudentSearch" variant="ghost" size="xs" target="clearStudentSearch" :loading="true" class="ml-2">Clear search</x-button>
                                        @else
                                            No active learners are enrolled in this class.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @error('statuses') <p class="mx-5 mt-4 rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $message }}</p> @enderror

                <div class="flex justify-end gap-3 border-t border-slate-200 px-5 py-4">
                    <x-button type="submit" variant="primary" icon="save" target="save" :loading="true">Save attendance</x-button>
                </div>
            </form>
        </section>

        @if (method_exists($students, 'hasPages'))
            <x-pagination :paginator="$students" />
        @endif
    @endif
</div>
