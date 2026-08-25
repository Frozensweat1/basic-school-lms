<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Curriculum setup</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Class subjects</h2>
            <p class="mt-1 max-w-2xl text-sm text-slate-600">Assign the subjects taught by each class and identify the responsible teacher.</p>
        </div>

        @can('create', App\Models\ClassSubject::class)
            <x-button wire:click="create" variant="primary" icon="plus" target="create" :loading="true">Allocate subject</x-button>
        @endcan
    </div>

    <div class="grid grid-cols-3 gap-4">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Subject allocations</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $allocationCount }}</p>
            <p class="mt-1 text-xs text-slate-500">Across the accessible classes</p>
        </article>
        <article class="rounded-2xl border border-amber-100 bg-amber-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-amber-800">Teacher still needed</p>
            <p class="mt-2 text-3xl font-bold text-amber-900">{{ $unassignedCount }}</p>
            <p class="mt-1 text-xs text-amber-700">Allocations without a responsible teacher</p>
        </article>
        <article class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-blue-800">Showing</p>
            <p class="mt-2 text-3xl font-bold text-blue-900">{{ $classSubjects->total() }}</p>
            <p class="mt-1 text-xs text-blue-700">Matching current search and filters</p>
        </article>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_repeat(4,minmax(0,180px))_auto] xl:items-center">
            <div class="relative">
                <label for="class-subject-search" class="sr-only">Search class subjects</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
                <input
                    id="class-subject-search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by class, subject, code, year, or teacher"
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

            <select wire:model.live="filterClassId" aria-label="Filter by class" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All classes</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}">{{ $class->name }} · {{ $class->academicYear->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterSubjectId" aria-label="Filter by subject" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All subjects</option>
                @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterTeacherId" aria-label="Filter by teacher" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All teachers</option>
                <option value="unassigned">Unassigned</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}">{{ $teacher->first_name }} {{ $teacher->last_name }}</option>
                @endforeach
            </select>

            @if (filled($search) || filled($filterAcademicYearId) || filled($filterClassId) || filled($filterSubjectId) || filled($filterTeacherId))
                <x-button wire:click="clearFilters" variant="ghost" size="sm" target="clearFilters" :loading="true">Clear filters</x-button>
            @endif
        </div>
    </section>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Class</th>
                        <th class="px-5 py-3">Subject</th>
                        <th class="px-5 py-3">Responsible teacher</th>
                        <th class="px-5 py-3">Linked records</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($classSubjects as $classSubject)
                        <tr wire:key="class-subject-{{ $classSubject->id }}" class="hover:bg-slate-50/80">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-900">{{ $classSubject->schoolClass->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $classSubject->schoolClass->academicYear->name }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-900">{{ $classSubject->subject->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $classSubject->subject->code ?: 'No subject code' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                @if ($classSubject->teacher)
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ $classSubject->teacher->first_name }} {{ $classSubject->teacher->last_name }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Not assigned</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-600">
                                <span>{{ $classSubject->topics_count }} {{ \Illuminate\Support\Str::plural('topic', $classSubject->topics_count) }}</span>
                                <span class="px-1 text-slate-300">·</span>
                                <span>{{ $classSubject->assignments_count + $classSubject->quizzes_count + $classSubject->assessments_count + $classSubject->examinations_count }} assessments</span>
                                @if ($classSubject->timetable_entries_count)
                                    <span class="px-1 text-slate-300">·</span>
                                    <span>{{ $classSubject->timetable_entries_count }} timetable {{ \Illuminate\Support\Str::plural('entry', $classSubject->timetable_entries_count) }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    @can('update', $classSubject)
                                        <x-ui.icon-button wire:click="edit({{ $classSubject->id }})" icon="edit" label="Edit {{ $classSubject->subject->name }} allocation" target="edit({{ $classSubject->id }})" />
                                    @endcan
                                    @can('delete', $classSubject)
                                        <x-ui.icon-button wire:click="confirmDelete({{ $classSubject->id }})" icon="trash" label="Remove {{ $classSubject->subject->name }} allocation" variant="danger" target="confirmDelete({{ $classSubject->id }})" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-14 text-center text-slate-500">
                                @if (filled($search) || filled($filterAcademicYearId) || filled($filterClassId) || filled($filterSubjectId) || filled($filterTeacherId))
                                    No class subject allocations match the current filters.
                                @else
                                    No subjects have been allocated to classes yet.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-pagination :paginator="$classSubjects" />

    @if ($showFormModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center overflow-y-auto bg-slate-950/70 p-4 backdrop-blur-sm" style="background-color:rgba(2,6,23,.72)" role="dialog" aria-modal="true" aria-labelledby="class-subject-form-title">
            <div class="w-full max-w-xl rounded-2xl bg-white shadow-2xl ring-1 ring-black/20">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-500">Curriculum setup</p>
                        <h3 id="class-subject-form-title" class="mt-1 text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit class subject' : 'Allocate subject' }}</h3>
                    </div>
                    <x-ui.icon-button wire:click="closeModals" icon="close" label="Close form" target="closeModals" />
                </div>

                <form wire:submit="save" class="space-y-5 p-6">
                    <div>
                        <label for="class-subject-class" class="block text-sm font-medium text-slate-700">Class</label>
                        <select wire:model.blur="schoolClassId" id="class-subject-class" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                            <option value="">Choose a class</option>
                            @foreach ($formClasses as $class)
                                <option value="{{ $class->id }}">{{ $class->name }} · {{ $class->academicYear->name }}</option>
                            @endforeach
                        </select>
                        @error('schoolClassId') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="class-subject-subject" class="block text-sm font-medium text-slate-700">Subject</label>
                        <select wire:model.blur="subjectId" id="class-subject-subject" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                            <option value="">Choose a subject</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}{{ $subject->code ? ' · '.$subject->code : '' }}</option>
                            @endforeach
                        </select>
                        @error('subjectId') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="class-subject-teacher" class="block text-sm font-medium text-slate-700">Responsible teacher <span class="font-normal text-slate-400">(optional)</span></label>
                        <select wire:model.blur="teacherId" id="class-subject-teacher" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                            <option value="">Assign later</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->first_name }} {{ $teacher->last_name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Assigning a teacher enables that teacher to manage lessons, assessments, and examinations for this class subject.</p>
                        @error('teacherId') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <x-button type="button" wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button>
                        <x-button type="submit" variant="primary" icon="save" target="save" :loading="true">{{ $editingId ? 'Save changes' : 'Allocate subject' }}</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm" style="background-color:rgba(2,6,23,.72)" role="dialog" aria-modal="true" aria-labelledby="delete-class-subject-title">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-black/20">
                <h3 id="delete-class-subject-title" class="text-lg font-semibold text-slate-900">Remove class subject?</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">This is only available while the allocation has no linked teaching, timetable, assessment, or examination records.</p>
                @error('delete')
                    <p class="mt-3 rounded-xl bg-rose-50 p-3 text-sm text-rose-700">{{ $message }}</p>
                @enderror
                <div class="mt-6 flex justify-end gap-3">
                    <x-button wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button>
                    <x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Remove allocation</x-button>
                </div>
            </div>
        </div>
    @endif
</div>
