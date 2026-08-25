<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Assessment workflow</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Assessments</h2>
            <p class="mt-1 max-w-2xl text-sm text-slate-600">Create class assessments, attach them to weighted components, and record student scores.</p>
        </div>

        <div class="flex flex-wrap justify-end gap-3">
            @can('viewAny', App\Models\AssessmentComponent::class)
                <a href="{{ route('lms.assessment-components.index') }}" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M6 3h12v18H6z" stroke-linejoin="round"></path>
                        <path d="M9 8h6M9 12h6M9 16h3"></path>
                    </svg>
                    Components
                </a>
            @endcan
            @can('create', App\Models\Assessment::class)
                <x-button wire:click="create" variant="primary" icon="plus" target="create" :loading="true">New assessment</x-button>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <article class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-emerald-800">Published</p>
            <p class="mt-2 text-3xl font-bold text-emerald-900">{{ $publishedCount }}</p>
            <p class="mt-1 text-xs text-emerald-700">Included in student-result calculation</p>
        </article>
        <article class="rounded-2xl border border-amber-100 bg-amber-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-amber-800">Drafts</p>
            <p class="mt-2 text-3xl font-bold text-amber-900">{{ $draftCount }}</p>
            <p class="mt-1 text-xs text-amber-700">Not yet used in results</p>
        </article>
        <article class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-blue-800">No scores entered</p>
            <p class="mt-2 text-3xl font-bold text-blue-900">{{ $unscoredCount }}</p>
            <p class="mt-1 text-xs text-blue-700">Ready for score entry</p>
        </article>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        @php
            $filterTermIds = $terms
                ->when(filled($filterAcademicYearId), fn ($items) => $items->where('academic_year_id', (int) $filterAcademicYearId))
                ->pluck('id');
        @endphp
        <div class="grid gap-3 2xl:grid-cols-[minmax(0,1fr)_repeat(5,minmax(0,170px))_auto] 2xl:items-center">
            <div class="relative">
                <label for="assessment-search" class="sr-only">Search assessments</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
                <input id="assessment-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search title, class, subject, component, or teacher" autocomplete="off" class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-24 text-sm shadow-sm transition focus:border-blue-700 focus:ring-blue-700">
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
                @foreach ($terms->whereIn('id', $filterTermIds) as $term)
                    <option value="{{ $term->id }}">{{ $term->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterClassSubjectId" aria-label="Filter by class subject" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All class subjects</option>
                @foreach ($classSubjects->when(filled($filterAcademicYearId), fn ($items) => $items->filter(fn ($item) => (int) $item->schoolClass->academic_year_id === (int) $filterAcademicYearId)) as $classSubject)
                    <option value="{{ $classSubject->id }}">{{ $classSubject->schoolClass->name }} · {{ $classSubject->subject->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterComponentId" aria-label="Filter by component" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All components</option>
                <option value="unassigned">No component</option>
                @foreach ($components->when(filled($filterTermId), fn ($items) => $items->where('term_id', (int) $filterTermId))->when(filled($filterAcademicYearId) && blank($filterTermId), fn ($items) => $items->whereIn('term_id', $filterTermIds)) as $component)
                    <option value="{{ $component->id }}">{{ $component->name }} ({{ $component->weight }}%)</option>
                @endforeach
            </select>

            <select wire:model.live="filterStatus" aria-label="Filter by status" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All statuses</option>
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                <option value="locked">Locked</option>
            </select>

            @if (filled($search) || filled($filterAcademicYearId) || filled($filterTermId) || filled($filterClassSubjectId) || filled($filterComponentId) || filled($filterStatus))
                <x-button wire:click="clearFilters" variant="ghost" size="sm" target="clearFilters" :loading="true">Clear filters</x-button>
            @endif
        </div>
    </section>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Assessment</th>
                        <th class="px-5 py-3">Class subject</th>
                        <th class="px-5 py-3">Component</th>
                        <th class="px-5 py-3">Score progress</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($assessments as $assessment)
                        @php
                            $statusClasses = match ($assessment->status) {
                                'published' => 'bg-emerald-100 text-emerald-800',
                                'locked' => 'bg-slate-200 text-slate-700',
                                default => 'bg-amber-100 text-amber-800',
                            };
                            $enrollmentCount = $assessment->classSubject->schoolClass->enrollments_count;
                        @endphp
                        <tr wire:key="assessment-{{ $assessment->id }}" class="hover:bg-slate-50/80">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-900">{{ $assessment->title }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $assessment->assessed_at->format('d M Y') }} · /{{ rtrim(rtrim((string) $assessment->max_score, '0'), '.') }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-900">{{ $assessment->classSubject->schoolClass->name }} · {{ $assessment->classSubject->subject->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $assessment->term->academicYear->name }} · {{ $assessment->term->name }}</p>
                            </td>
                            <td class="px-5 py-4">
                                @if ($assessment->component)
                                    <a href="{{ route('lms.assessment-components.index') }}" class="font-medium text-blue-700 hover:text-blue-900 hover:underline">
                                        {{ $assessment->component->name }}
                                    </a>
                                    <p class="mt-1 text-xs text-slate-500">{{ $assessment->component->weight }}% of {{ $assessment->term->name }}</p>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">No component</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-800">{{ $assessment->entered_scores_count }} / {{ $enrollmentCount }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ \Illuminate\Support\Str::plural('score', $assessment->entered_scores_count) }} entered</p>
                            </td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">{{ ucfirst($assessment->status) }}</span></td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    @can('update', $assessment)
                                        <a href="{{ route($scoreRouteName, $assessment) }}" class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition-colors hover:bg-blue-100" title="Enter scores for {{ $assessment->title }}">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="M4 19V5M9 19v-7M14 19V9M19 19V3"></path>
                                            </svg>
                                            Scores
                                        </a>
                                        <x-ui.icon-button wire:click="edit({{ $assessment->id }})" icon="edit" label="Edit {{ $assessment->title }}" target="edit({{ $assessment->id }})" />
                                    @endcan
                                    @can('delete', $assessment)
                                        <x-ui.icon-button wire:click="confirmDelete({{ $assessment->id }})" icon="trash" label="Delete {{ $assessment->title }}" variant="danger" target="confirmDelete({{ $assessment->id }})" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center text-slate-500">
                                @if (filled($search) || filled($filterAcademicYearId) || filled($filterTermId) || filled($filterClassSubjectId) || filled($filterComponentId) || filled($filterStatus))
                                    No assessments match the current search and filters.
                                @else
                                    No assessments have been created yet.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-pagination :paginator="$assessments" />

    @php
        $formTerms = $terms->where('academic_year_id', (int) $formAcademicYearId);
        $formComponents = $components->where('term_id', (int) $termId);
    @endphp

    <x-modal :show="$showFormModal" :title="$editingId ? 'Edit assessment' : 'New assessment'" close-action="closeModals" max-width="2xl">
        <form wire:submit="save" class="space-y-5">
                    <p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-500">Assessment workflow</p>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="assessment-title" class="block text-sm font-medium text-slate-700">Assessment title</label>
                            <input wire:model.blur="title" id="assessment-title" type="text" placeholder="Fractions class exercise" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                            @error('title') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="assessment-status" class="block text-sm font-medium text-slate-700">Status</label>
                            <select wire:model.blur="status" id="assessment-status" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                                <option value="draft">Draft — scores stay internal</option>
                                <option value="published">Published — included in results</option>
                                <option value="locked">Locked — retain as a final record</option>
                            </select>
                            @error('status') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="assessment-class-subject" class="block text-sm font-medium text-slate-700">Class subject</label>
                            <select wire:model.live="classSubjectId" id="assessment-class-subject" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                                <option value="">Choose a class subject</option>
                                @foreach ($classSubjects as $classSubject)
                                    <option value="{{ $classSubject->id }}">{{ $classSubject->schoolClass->name }} · {{ $classSubject->subject->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">This determines the available term and usually pre-fills the responsible teacher.</p>
                            @error('classSubjectId') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="assessment-term" class="block text-sm font-medium text-slate-700">Term</label>
                            <select wire:model.live="termId" id="assessment-term" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                                <option value="">Choose a term</option>
                                @foreach ($formTerms as $term)
                                    <option value="{{ $term->id }}">{{ $term->name }}</option>
                                @endforeach
                            </select>
                            @error('termId') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="assessment-component" class="block text-sm font-medium text-slate-700">Assessment component <span class="font-normal text-slate-400">(optional)</span></label>
                            <select wire:model.blur="componentId" id="assessment-component" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                                <option value="">No component</option>
                                @foreach ($formComponents as $component)
                                    <option value="{{ $component->id }}">{{ $component->name }} ({{ $component->weight }}%)</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Components define the percentage contribution to the term result.</p>
                            @error('componentId') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="assessment-teacher" class="block text-sm font-medium text-slate-700">Responsible teacher</label>
                            <select wire:model.blur="teacherId" id="assessment-teacher" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                                <option value="">Use the class-subject teacher</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->first_name }} {{ $teacher->last_name }}</option>
                                @endforeach
                            </select>
                            @error('teacherId') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="assessment-date" class="block text-sm font-medium text-slate-700">Assessment date</label>
                            <input wire:model.blur="assessedAt" id="assessment-date" type="date" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                            @error('assessedAt') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="assessment-max-score" class="block text-sm font-medium text-slate-700">Maximum score</label>
                            <input wire:model.blur="maxScore" id="assessment-max-score" type="number" min="0.01" step="0.01" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                            @error('maxScore') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">
                        <p class="font-semibold">Next step: enter scores</p>
                        <p class="mt-1 text-blue-800">After saving, use the Scores action in the assessment list to record each enrolled student’s score and comment.</p>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <x-button type="button" wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button>
                        <x-button type="submit" variant="primary" icon="save" target="save" :loading="true">{{ $editingId ? 'Save changes' : 'Save assessment' }}</x-button>
                    </div>
        </form>
    </x-modal>

    <x-modal :show="$showDeleteModal" title="Delete assessment?" close-action="closeModals" max-width="md">
        <p class="text-sm leading-6 text-slate-600">All entered student scores for this assessment are permanently removed as well. Lock the assessment instead if it should remain part of the record.</p>
        <x-slot:footer>
            <div class="flex justify-end gap-3">
                <x-button wire:click="closeModals" variant="secondary" icon="close" target="closeModals" :loading="true">Cancel</x-button>
                <x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Delete assessment</x-button>
            </div>
        </x-slot:footer>
    </x-modal>
</div>
