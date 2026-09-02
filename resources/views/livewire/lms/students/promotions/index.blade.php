<div class="space-y-6">
    @php
        $selectedCount = count($selectedStudentIds);
        $filtersActive = filled($search) || filled($placementFilter) || (int) $perPage !== 15;
        $contextReady = filled($sourceYearId) && filled($sourceClassId) && filled($targetYearId);
    @endphp

    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <a href="{{ route('lms.students.index') }}" wire:navigate class="inline-flex cursor-pointer items-center gap-2 text-sm font-semibold text-blue-800 transition hover:text-blue-950 focus:outline-none focus:ring-2 focus:ring-blue-200">
                <span aria-hidden="true">&larr;</span>
                Back to students
            </a>
            <p class="mt-4 text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Student management</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Student Promotions</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-600">Prepare learners for a later academic year, complete immediate promotions, or record graduation and transfer outcomes without losing their academic history.</p>
        </div>

        @if ($targetYear)
            <x-badge :variant="$targetYear->is_active ? 'success' : 'warning'" size="md">
                {{ $targetYear->is_active ? 'Changes apply immediately' : 'Placements remain pending' }}
            </x-badge>
        @endif
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="promotion-context-title">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h3 id="promotion-context-title" class="text-lg font-bold text-slate-900">Promotion context</h3>
                <p class="mt-1 text-sm text-slate-600">Choose where learners are moving from and the later academic year they are moving into.</p>
            </div>
            <span wire:loading wire:target="sourceYearId,sourceClassId,targetYearId" class="text-sm font-medium text-blue-700" role="status">Updating context…</span>
        </div>

        <div class="mt-5 grid gap-5 md:grid-cols-3">
            <div>
                <label for="promotion-source-year" class="block text-sm font-semibold text-slate-700">Source academic year</label>
                <select id="promotion-source-year" wire:model.live="sourceYearId" wire:loading.attr="disabled" wire:target="sourceYearId"
                    class="mt-1.5 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                    <option value="">Choose source year</option>
                    @foreach ($sourceYears as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}{{ $year->is_active ? ' · Active' : '' }}</option>
                    @endforeach
                </select>
                @error('sourceYearId') <p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="promotion-source-class" class="block text-sm font-semibold text-slate-700">Source class</label>
                <select id="promotion-source-class" wire:model.live="sourceClassId" wire:loading.attr="disabled" wire:target="sourceYearId,sourceClassId"
                    class="mt-1.5 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                    <option value="">{{ filled($sourceYearId) ? 'Choose source class' : 'Choose a source year first' }}</option>
                    @foreach ($sourceClasses as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}{{ $class->stream ? ' · '.$class->stream->name : '' }}</option>
                    @endforeach
                </select>
                @error('sourceClassId') <p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="promotion-target-year" class="block text-sm font-semibold text-slate-700">Target academic year</label>
                <select id="promotion-target-year" wire:model.live="targetYearId" wire:loading.attr="disabled" wire:target="sourceYearId,targetYearId"
                    class="mt-1.5 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                    <option value="">{{ filled($sourceYearId) ? 'Choose a later academic year' : 'Choose a source year first' }}</option>
                    @foreach ($targetYears as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}{{ $year->is_active ? ' · Active' : ' · Prepared' }}</option>
                    @endforeach
                </select>
                @error('targetYearId') <p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>
        </div>

        @if (filled($sourceYearId) && $targetYears->isEmpty())
            <div class="mt-5 flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 sm:flex-row sm:items-center sm:justify-between">
                <p>No later unlocked academic year with active classes is available. Prepare the next year before promoting learners.</p>
                <a href="{{ route('lms.academic-years.index') }}" wire:navigate class="inline-flex shrink-0 cursor-pointer items-center font-semibold text-amber-950 underline decoration-amber-400 underline-offset-4 hover:decoration-amber-700">Manage academic years</a>
            </div>
        @endif
    </section>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Eligible learners" :value="$eligibleCount" tone="primary" />
        <x-stat-card label="Pending placements" :value="$plannedCount" tone="warning" />
        <x-stat-card label="Placement conflicts" :value="$conflictCount" tone="danger" />
        <x-stat-card label="Selected learners" :value="$selectedCount" tone="success" />
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="promotion-plan-title">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h3 id="promotion-plan-title" class="text-lg font-bold text-slate-900">Transition details</h3>
                <p class="mt-1 text-sm text-slate-600">Set a default destination for bulk work. A learner-specific choice in the table overrides this default.</p>
            </div>
            @if ($targetYear)
                <x-badge :variant="$targetYear->is_active ? 'success' : 'warning'">
                    Target: {{ $targetYear->name }}
                </x-badge>
            @endif
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-[minmax(14rem,1fr)_13rem_minmax(16rem,1.25fr)]">
            <div>
                <label for="promotion-default-destination" class="block text-sm font-semibold text-slate-700">Default destination or outcome</label>
                <select id="promotion-default-destination" wire:model="bulkDestination" @disabled(! $contextReady)
                    class="mt-1.5 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700 disabled:cursor-not-allowed disabled:bg-slate-100">
                    <option value="">Choose a default</option>
                    @foreach ($targetClasses as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}{{ $class->stream ? ' · '.$class->stream->name : '' }}</option>
                    @endforeach
                    @if ($targetYear?->is_active)
                        <option value="graduate">Graduate learners</option>
                        <option value="transfer">Transfer out</option>
                    @endif
                </select>
                @error('bulkDestination') <p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="promotion-effective-date" class="block text-sm font-semibold text-slate-700">Effective date</label>
                <input id="promotion-effective-date" type="date" wire:model.blur="effectiveDate" @disabled(! $contextReady)
                    class="mt-1.5 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700 disabled:cursor-not-allowed disabled:bg-slate-100">
                @error('effectiveDate') <p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="promotion-notes" class="block text-sm font-semibold text-slate-700">Administrative notes <span class="font-normal text-slate-500">(optional)</span></label>
                <textarea id="promotion-notes" wire:model.blur="notes" rows="2" maxlength="1000" @disabled(! $contextReady)
                    placeholder="Reason, approval reference, or other useful context"
                    class="mt-1.5 block w-full resize-y rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700 disabled:cursor-not-allowed disabled:bg-slate-100"></textarea>
                @error('notes') <p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <section class="space-y-4" aria-labelledby="promotion-learners-title">
        <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm xl:flex-row xl:items-center xl:justify-between">
            <div class="grid w-full gap-3 sm:grid-cols-2 xl:max-w-4xl xl:grid-cols-[minmax(17rem,1fr)_12rem_9rem]">
                <div class="relative sm:col-span-2 xl:col-span-1">
                    <label for="promotion-search" class="sr-only">Search learners</label>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m20 20-3.5-3.5"></path>
                    </svg>
                    <input id="promotion-search" type="search" wire:model.live.debounce.300ms="search" autocomplete="off"
                        placeholder="Search learner name, ID, or admission number"
                        class="block w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-20 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                    <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching…</span>
                </div>

                <select wire:model.live="placementFilter" aria-label="Filter learners by target-year placement"
                    class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                    <option value="">All placements</option>
                    <option value="unplanned">Unplanned</option>
                    <option value="planned">Pending placement</option>
                    <option value="conflict">Active or completed</option>
                </select>

                <select wire:model.live="perPage" aria-label="Learners per page"
                    class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                    <option value="10">10 per page</option>
                    <option value="15">15 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                </select>
            </div>

            <div class="flex shrink-0 items-center justify-between gap-3 xl:justify-end">
                @if ($filtersActive)
                    <x-button wire:click="clearFilters" variant="ghost" size="sm" target="clearFilters" :loading="true">Clear filters</x-button>
                @endif
                <p class="whitespace-nowrap text-sm text-slate-500" aria-live="polite">
                    <span wire:loading.remove wire:target="search,placementFilter,perPage">{{ $enrollments->total() }} {{ \Illuminate\Support\Str::plural('learner', $enrollments->total()) }}</span>
                    <span wire:loading wire:target="search,placementFilter,perPage">Updating…</span>
                </p>
            </div>
        </div>

        <div class="flex flex-col gap-3 rounded-2xl border border-blue-100 bg-blue-50/70 p-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap items-center gap-2">
                @if ($enrollments->isEmpty())
                    <x-button variant="ghost" size="sm" icon="check" disabled>Select visible</x-button>
                @else
                    <x-button wire:click="selectVisible" variant="ghost" size="sm" icon="check" target="selectVisible" :loading="true">Select visible</x-button>
                @endif
                @if ($selectedCount === 0)
                    <x-button variant="ghost" size="sm" icon="close" disabled>Clear selection</x-button>
                @else
                    <x-button wire:click="clearSelection" variant="ghost" size="sm" icon="close" target="clearSelection" :loading="true">Clear selection</x-button>
                @endif
                <span class="text-sm font-semibold text-blue-900">{{ $selectedCount }} selected</span>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2">
                @if ($selectedCount === 0 || blank($bulkDestination))
                    <x-button variant="secondary" size="sm" icon="check" disabled>Apply default</x-button>
                @else
                    <x-button wire:click="applyDestinationToSelected" variant="secondary" size="sm" icon="check" target="applyDestinationToSelected" :loading="true">Apply default</x-button>
                @endif
                @if ($selectedCount === 0)
                    <x-button size="sm" icon="sparkles" disabled>Review selected</x-button>
                @else
                    <x-button wire:click="reviewSelected" size="sm" icon="sparkles" target="reviewSelected" :loading="true">Review selected</x-button>
                @endif
            </div>
        </div>

        @error('selectedStudentIds')
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ $message }}</div>
        @enderror
        @error('studentDestinations')
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ $message }}</div>
        @enderror

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 id="promotion-learners-title" class="font-bold text-slate-900">Eligible learners and placement plans</h3>
                <p class="mt-1 text-sm text-slate-500">Review existing target-year records before choosing an override or applying the default destination.</p>
            </div>

            @if ($enrollments->isEmpty())
                <div class="p-5 sm:p-8">
                    <x-empty-state
                        :title="$filtersActive ? 'No learners match these filters' : ($contextReady ? 'No eligible learners found' : 'Choose a promotion context')"
                        :description="$filtersActive ? 'Clear a filter or try a different learner search.' : ($contextReady ? 'This source class has no active eligible learners to promote.' : 'Select a source year, source class, and later target year to begin.')"
                    />
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="w-12 px-4 py-3 font-semibold"><span class="sr-only">Select learner</span></th>
                                <th class="min-w-60 px-4 py-3 font-semibold">Learner</th>
                                <th class="min-w-56 px-4 py-3 font-semibold">Target-year placement</th>
                                <th class="min-w-64 px-4 py-3 font-semibold">Destination override</th>
                                <th class="px-4 py-3 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($enrollments as $enrollment)
                                @php
                                    $student = $enrollment->student;
                                    $placements = $targetPlacements->get($student->id, collect());
                                    $pendingPlacement = $placements->firstWhere('status', App\Models\ClassEnrollment::STATUS_PENDING);
                                @endphp
                                <tr wire:key="promotion-learner-{{ $student->id }}" class="align-top transition hover:bg-slate-50/70">
                                    <td class="px-4 py-4">
                                        <input type="checkbox" wire:model.live="selectedStudentIds" value="{{ $student->id }}"
                                            aria-label="Select {{ $student->first_name }} {{ $student->last_name }}"
                                            class="mt-1 rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                                    </td>
                                    <td class="px-4 py-4">
                                        <p class="font-semibold text-slate-900">{{ $student->last_name }}, {{ $student->first_name }}{{ $student->middle_name ? ' '.$student->middle_name : '' }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $student->student_id }} · {{ $student->admission_number }}</p>
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            <x-badge :variant="$student->status === 'active' ? 'success' : 'warning'">{{ ucfirst($student->status) }}</x-badge>
                                            <x-badge variant="default">Current: {{ $enrollment->schoolClass->name }}{{ $enrollment->schoolClass->stream ? ' · '.$enrollment->schoolClass->stream->name : '' }}</x-badge>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        @forelse ($placements as $placement)
                                            @php
                                                $placementVariant = match ($placement->status) {
                                                    App\Models\ClassEnrollment::STATUS_PENDING => 'warning',
                                                    App\Models\ClassEnrollment::STATUS_ACTIVE => 'success',
                                                    default => 'danger',
                                                };
                                            @endphp
                                            <div class="mb-2 flex flex-wrap items-center gap-2 last:mb-0">
                                                <x-badge :variant="$placementVariant">
                                                    {{ ucfirst($placement->status) }} · {{ $placement->schoolClass->name }}{{ $placement->schoolClass->stream ? ' · '.$placement->schoolClass->stream->name : '' }}
                                                </x-badge>
                                                @if ($placement->status === App\Models\ClassEnrollment::STATUS_PENDING)
                                                    <x-button wire:click="confirmCancelPendingPlacement({{ $placement->id }})" variant="ghost" size="xs" icon="close" target="confirmCancelPendingPlacement({{ $placement->id }})" :loading="true">Cancel</x-button>
                                                @endif
                                            </div>
                                        @empty
                                            <x-badge variant="default">Unplanned</x-badge>
                                        @endforelse
                                    </td>
                                    <td class="px-4 py-4">
                                        <label for="promotion-destination-{{ $student->id }}" class="sr-only">Destination for {{ $student->first_name }} {{ $student->last_name }}</label>
                                        <select id="promotion-destination-{{ $student->id }}" wire:model="studentDestinations.{{ $student->id }}"
                                            class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                                            <option value="">Use default destination</option>
                                            @foreach ($targetClasses as $class)
                                                <option value="{{ $class->id }}">{{ $class->name }}{{ $class->stream ? ' · '.$class->stream->name : '' }}</option>
                                            @endforeach
                                            @if ($targetYear?->is_active)
                                                <option value="graduate">Graduate learner</option>
                                                <option value="transfer">Transfer out</option>
                                            @endif
                                        </select>
                                        @error('studentDestinations.'.$student->id) <p class="mt-1.5 text-xs text-rose-700">{{ $message }}</p> @enderror
                                        @if ($pendingPlacement)
                                            <p class="mt-1.5 text-xs text-slate-500">Current pending destination: {{ $pendingPlacement->schoolClass->name }}. Choose it again to keep it, or select a new destination.</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <x-button wire:click="reviewOne({{ $student->id }})" variant="ghost" size="xs" icon="sparkles" target="reviewOne({{ $student->id }})" :loading="true">Review</x-button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <x-pagination :paginator="$enrollments" />
    </section>

    <x-modal :show="$showConfirmationModal" title="Confirm student transitions" close-action="closeModals" max-width="xl">
        <div class="space-y-5">
            <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
                <p class="text-sm font-semibold text-blue-950">{{ $confirmationSummary['count'] ?? 0 }} {{ \Illuminate\Support\Str::plural('learner', $confirmationSummary['count'] ?? 0) }}</p>
                <p class="mt-1 text-sm text-blue-800">{{ $confirmationSummary['source'] ?? 'Source class' }} → {{ $confirmationSummary['target'] ?? 'Target year' }}</p>
            </div>

            <dl class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Processing mode</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ $confirmationSummary['mode'] ?? 'Review required' }}</dd>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Effective date</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ filled($confirmationSummary['effective_date'] ?? null) ? \Illuminate\Support\Carbon::parse($confirmationSummary['effective_date'])->format('d M Y') : 'Not set' }}</dd>
                </div>
            </dl>

            @if (! empty($confirmationSummary['groups']))
                <div>
                    <p class="text-sm font-semibold text-slate-800">Planned outcomes</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($confirmationSummary['groups'] as $label => $count)
                            <x-badge variant="info" size="md">{{ $label }} · {{ $count }}</x-badge>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (filled($notes))
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Administrative notes</p>
                    <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $notes }}</p>
                </div>
            @endif

            <p class="text-sm leading-6 text-slate-600">This action is transactional. If any learner is no longer eligible or has a conflicting placement, no learner records will be changed.</p>
        </div>

        <x-slot:footer>
            <div class="flex flex-wrap justify-end gap-3">
                <x-button wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Go back</x-button>
                <x-button wire:click="processPromotions" variant="success" icon="check" target="processPromotions" :loading="true">Confirm transitions</x-button>
            </div>
        </x-slot:footer>
    </x-modal>

    <x-modal :show="$showCancelModal" title="Cancel pending placement?" close-action="closeModals" max-width="md">
        <div class="space-y-3">
            <p class="text-sm leading-6 text-slate-600">This removes only the learner's pending target-year placement. Their active source-class enrollment and historical records remain unchanged.</p>
            <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900">You can prepare another destination for this learner at any time before the target year is activated.</p>
        </div>

        <x-slot:footer>
            <div class="flex flex-wrap justify-end gap-3">
                <x-button wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Keep placement</x-button>
                <x-button wire:click="cancelPendingPlacement" variant="danger" icon="trash" target="cancelPendingPlacement" :loading="true">Cancel placement</x-button>
            </div>
        </x-slot:footer>
    </x-modal>
</div>
