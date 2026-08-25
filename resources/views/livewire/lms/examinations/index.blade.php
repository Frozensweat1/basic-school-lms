<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Assessment schedule</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Examinations</h2>
            <p class="mt-1 max-w-2xl text-sm text-slate-600">Schedule, track, and communicate formal examinations for each class subject.</p>
        </div>

        @can('create', App\Models\Examination::class)
            <x-button wire:click="create" variant="primary" icon="plus" target="create" :loading="true">Schedule examination</x-button>
        @endcan
    </div>

    <div class="grid grid-cols-3 gap-4">
        <article class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-blue-800">Learner-visible</p>
            <p class="mt-2 text-3xl font-bold text-blue-900">{{ $scheduledCount }}</p>
            <p class="mt-1 text-xs text-blue-700">Scheduled or completed examinations</p>
        </article>
        <article class="rounded-2xl border border-amber-100 bg-amber-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-amber-800">Drafts</p>
            <p class="mt-2 text-3xl font-bold text-amber-900">{{ $draftCount }}</p>
            <p class="mt-1 text-xs text-amber-700">Still hidden from learners</p>
        </article>
        <article class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-emerald-800">Upcoming</p>
            <p class="mt-2 text-3xl font-bold text-emerald-900">{{ $upcomingCount }}</p>
            <p class="mt-1 text-xs text-emerald-700">Scheduled from today onward</p>
        </article>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 2xl:grid-cols-[minmax(0,1fr)_repeat(4,minmax(0,180px))_auto] 2xl:items-center">
            <div class="relative">
                <label for="examination-search" class="sr-only">Search examinations</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
                <input
                    id="examination-search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search title, class, subject, or teacher"
                    autocomplete="off"
                    class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-24 text-sm shadow-sm transition focus:border-blue-700 focus:ring-blue-700"
                >
                <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching…</span>
            </div>

            <select wire:model.live="filterAcademicYearId" aria-label="Filter by academic year" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All years</option>
                @foreach ($years as $year)
                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterTermId" aria-label="Filter by term" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All terms</option>
                @foreach ($terms->when(filled($filterAcademicYearId), fn ($items) => $items->where('academic_year_id', (int) $filterAcademicYearId)) as $term)
                    <option value="{{ $term->id }}">{{ $term->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterClassSubjectId" aria-label="Filter by class subject" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All class subjects</option>
                @foreach ($classSubjects->when(filled($filterAcademicYearId), fn ($items) => $items->filter(fn ($item) => (int) $item->schoolClass->academic_year_id === (int) $filterAcademicYearId)) as $classSubject)
                    <option value="{{ $classSubject->id }}">{{ $classSubject->schoolClass->name }} · {{ $classSubject->subject->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterStatus" aria-label="Filter by examination status" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All statuses</option>
                <option value="draft">Draft</option>
                <option value="scheduled">Scheduled</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>

            @if (filled($search) || filled($filterAcademicYearId) || filled($filterTermId) || filled($filterClassSubjectId) || filled($filterStatus))
                <x-button wire:click="clearFilters" variant="ghost" size="sm" target="clearFilters" :loading="true">Clear filters</x-button>
            @endif
        </div>
    </section>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Examination</th>
                        <th class="px-5 py-3">Class subject</th>
                        <th class="px-5 py-3">Schedule</th>
                        <th class="px-5 py-3">Teacher</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($examinations as $examination)
                        @php
                            $statusClasses = match ($examination->status) {
                                'scheduled', 'published' => 'bg-blue-100 text-blue-800',
                                'completed' => 'bg-emerald-100 text-emerald-800',
                                'cancelled' => 'bg-rose-100 text-rose-800',
                                default => 'bg-amber-100 text-amber-800',
                            };
                        @endphp
                        <tr wire:key="examination-{{ $examination->id }}" class="hover:bg-slate-50/80">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-900">{{ $examination->title }}</p>
                                <p class="mt-1 max-w-xs truncate text-xs text-slate-500">{{ $examination->description ?: 'No examination notes' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-900">{{ $examination->classSubject->schoolClass->name }} · {{ $examination->classSubject->subject->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $examination->academicYear->name }} · {{ $examination->term->name }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                <p class="font-medium">{{ $examination->exam_date->format('d M Y') }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $examination->duration_minutes ? $examination->duration_minutes.' minutes' : 'Duration not set' }} · /{{ rtrim(rtrim((string) $examination->max_score, '0'), '.') }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-700">{{ $examination->teacher->first_name }} {{ $examination->teacher->last_name }}</td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">{{ $examination->status === 'published' ? 'Scheduled' : ucfirst($examination->status) }}</span></td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    @can('update', $examination)
                                        <x-ui.icon-button wire:click="edit({{ $examination->id }})" icon="edit" label="Edit {{ $examination->title }}" target="edit({{ $examination->id }})" />
                                    @endcan
                                    @can('delete', $examination)
                                        <x-ui.icon-button wire:click="confirmDelete({{ $examination->id }})" icon="trash" label="Delete {{ $examination->title }}" variant="danger" target="confirmDelete({{ $examination->id }})" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center text-slate-500">
                                @if (filled($search) || filled($filterAcademicYearId) || filled($filterTermId) || filled($filterClassSubjectId) || filled($filterStatus))
                                    No examinations match the current search and filters.
                                @else
                                    No examinations have been scheduled yet.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-pagination :paginator="$examinations" />

    <x-modal :show="$showFormModal" :title="$editingId ? 'Edit examination' : 'Schedule examination'" close-action="closeModals" max-width="2xl">
        <form wire:submit="save" class="space-y-5">
                    <p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-500">Assessment schedule</p>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="exam-title" class="block text-sm font-medium text-slate-700">Examination title</label>
                            <input wire:model.blur="title" id="exam-title" type="text" placeholder="End of term examination" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                            @error('title') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="exam-status" class="block text-sm font-medium text-slate-700">Status</label>
                            <select wire:model.blur="status" id="exam-status" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                                <option value="draft">Draft — only staff can see it</option>
                                <option value="scheduled">Scheduled — visible to learners and parents</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            @error('status') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="exam-year" class="block text-sm font-medium text-slate-700">Academic year</label>
                            <select wire:model.live="academicYearId" id="exam-year" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                                <option value="">Choose an academic year</option>
                                @foreach ($years as $year)
                                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                                @endforeach
                            </select>
                            @error('academicYearId') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="exam-term" class="block text-sm font-medium text-slate-700">Term</label>
                            <select wire:model.blur="termId" id="exam-term" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                                <option value="">Choose a term</option>
                                @foreach ($terms->where('academic_year_id', (int) $academicYearId) as $term)
                                    <option value="{{ $term->id }}">{{ $term->name }}</option>
                                @endforeach
                            </select>
                            @error('termId') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="exam-class-subject" class="block text-sm font-medium text-slate-700">Class subject</label>
                            <select wire:model.live="classSubjectId" id="exam-class-subject" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                                <option value="">Choose a class subject</option>
                                @foreach ($formClassSubjects as $classSubject)
                                    <option value="{{ $classSubject->id }}">{{ $classSubject->schoolClass->name }} · {{ $classSubject->subject->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Only subjects assigned to the selected academic year are shown.</p>
                            @error('classSubjectId') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="exam-teacher" class="block text-sm font-medium text-slate-700">Responsible teacher</label>
                            <select wire:model.blur="teacherId" id="exam-teacher" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                                <option value="">Choose a teacher</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->first_name }} {{ $teacher->last_name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Selecting a class subject pre-fills its assigned teacher.</p>
                            @error('teacherId') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-3">
                        <div>
                            <label for="exam-date" class="block text-sm font-medium text-slate-700">Examination date</label>
                            <input wire:model.blur="examDate" id="exam-date" type="date" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                            @error('examDate') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="exam-duration" class="block text-sm font-medium text-slate-700">Duration <span class="font-normal text-slate-400">(minutes)</span></label>
                            <input wire:model.blur="durationMinutes" id="exam-duration" type="number" min="1" max="600" placeholder="90" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                            @error('durationMinutes') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="exam-max-score" class="block text-sm font-medium text-slate-700">Maximum score</label>
                            <input wire:model.blur="maxScore" id="exam-max-score" type="number" min="0.01" step="0.01" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                            @error('maxScore') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="exam-description" class="block text-sm font-medium text-slate-700">Notes <span class="font-normal text-slate-400">(optional)</span></label>
                        <textarea wire:model.blur="description" id="exam-description" rows="4" maxlength="5000" placeholder="Add preparation instructions or a note for learners and parents." class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700"></textarea>
                        @error('description') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <x-button type="button" wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button>
                        <x-button type="submit" variant="primary" icon="save" target="save" :loading="true">{{ $editingId ? 'Save changes' : 'Schedule examination' }}</x-button>
                    </div>
        </form>
    </x-modal>

    <x-modal :show="$showDeleteModal" title="Delete examination?" close-action="closeModals" max-width="md">
        <p class="text-sm leading-6 text-slate-600">This removes the examination schedule. Use a cancelled status instead when the record should remain visible for reference.</p>
        <x-slot:footer>
            <div class="flex justify-end gap-3">
                <x-button wire:click="closeModals" variant="secondary" icon="close" target="closeModals" :loading="true">Cancel</x-button>
                <x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Delete examination</x-button>
            </div>
        </x-slot:footer>
    </x-modal>
</div>
