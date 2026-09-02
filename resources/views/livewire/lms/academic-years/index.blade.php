<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Administration</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Academic years</h2>
            <p class="mt-1 text-sm text-slate-600">Create and manage the school years that preserve academic records.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if($rolloverCandidate)
                @can('create', App\Models\AcademicYear::class)
                    @can('update', $rolloverCandidate)
                        <x-button wire:click="prepareRollover({{ $rolloverCandidate->id }})" variant="secondary" icon="sparkles" target="prepareRollover({{ $rolloverCandidate->id }})" :loading="true">Prepare next year</x-button>
                    @endcan
                @endcan
            @endif
            @can('create', App\Models\AcademicYear::class)
                <x-button wire:click="create" icon="plus" target="create" :loading="true">New year</x-button>
            @endcan
        </div>
    </div>

    @if ($showForm)
        <section class="overflow-hidden rounded-2xl border border-blue-200 bg-white shadow-sm" aria-labelledby="academic-year-form-title">
            <div class="flex items-start justify-between gap-4 border-b border-blue-100 bg-blue-50/70 px-5 py-4 sm:px-6">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Academic year form</p>
                    <h3 id="academic-year-form-title" class="mt-1 text-lg font-bold text-slate-900">
                        {{ $editingId ? 'Edit academic year' : 'New academic year' }}
                    </h3>
                </div>
                <x-button wire:click="closeForm" variant="ghost" size="sm" target="closeForm" :loading="true">Cancel</x-button>
            </div>

            <form wire:submit="save" class="space-y-5 p-5 sm:p-6">
                <div>
                    <label for="year-name" class="block text-sm font-medium text-slate-700">Academic year</label>
                    <input wire:model.blur="name" id="year-name" type="text" placeholder="2026/2027"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                    @error('name')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="starts-at" class="block text-sm font-medium text-slate-700">Start date</label>
                        <input wire:model.blur="startsAt" id="starts-at" type="date"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                        @error('startsAt')
                            <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="ends-at" class="block text-sm font-medium text-slate-700">End date</label>
                        <input wire:model.blur="endsAt" id="ends-at" type="date"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                        @error('endsAt')
                            <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <label class="flex items-center gap-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                    <input wire:model="isActive" type="checkbox" class="rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                    Set as the active academic year
                </label>

                <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                    <x-button wire:click="closeForm" type="button" variant="ghost" target="closeForm" :loading="true">Cancel</x-button>
                    <x-button type="submit" icon="save" target="save" :loading="true">Save academic year</x-button>
                </div>
            </form>
        </section>
    @endif

    @if ($showDeleteConfirmation)
        <section class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm" aria-labelledby="delete-academic-year-title">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 id="delete-academic-year-title" class="font-semibold text-rose-950">Delete this academic year?</h3>
                    <p class="mt-1 text-sm text-rose-800">Only inactive years with no terms or classes can be deleted.</p>
                    @error('delete')
                        <p class="mt-2 text-sm font-medium text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex shrink-0 gap-3">
                    <x-button wire:click="cancelDelete" variant="ghost" target="cancelDelete" :loading="true">Cancel</x-button>
                    <x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Delete year</x-button>
                </div>
            </div>
        </section>
    @endif

    <x-modal :show="$showRolloverModal" title="Prepare the next academic year" close-action="closeRollover" max-width="3xl">
        @if ($rolloverSource)
            <form id="academic-year-rollover-form" wire:submit="runRollover" class="space-y-6">
                <fieldset wire:loading.attr="disabled" wire:target="runRollover" class="space-y-6 disabled:opacity-70">
                    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-950">
                        <p class="font-semibold">Using {{ $rolloverSource->name }} as the source</p>
                        <p class="mt-1 leading-6 text-blue-800">The source year and all its historical records remain unchanged. Only reusable structure is copied; lessons, assessments, scores, attendance, reports, and timetables are never duplicated.</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Terms</p>
                            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $rolloverSource->terms->count() }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Active classes</p>
                            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $rolloverSource->classes->count() }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Subject allocations</p>
                            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $rolloverSource->classes->sum('class_subjects_count') }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Active students</p>
                            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $rolloverSource->classes->sum('active_enrollments_count') }}</p>
                        </div>
                    </div>

                    <section aria-labelledby="target-year-heading">
                        <div class="mb-4">
                            <h4 id="target-year-heading" class="font-semibold text-slate-900">New academic year</h4>
                            <p class="mt-1 text-sm text-slate-500">Review the suggested identity and dates before continuing.</p>
                        </div>
                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <label for="rollover-name" class="block text-sm font-medium text-slate-700">Academic year</label>
                                <input id="rollover-name" wire:model.blur="rolloverName" type="text" placeholder="2027/2028"
                                    class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
                                @error('rolloverName') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="rollover-starts-at" class="block text-sm font-medium text-slate-700">Start date</label>
                                <input id="rollover-starts-at" wire:model.blur="rolloverStartsAt" type="date"
                                    class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
                                @error('rolloverStartsAt') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="rollover-ends-at" class="block text-sm font-medium text-slate-700">End date</label>
                                <input id="rollover-ends-at" wire:model.blur="rolloverEndsAt" type="date"
                                    class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
                                @error('rolloverEndsAt') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 p-4 sm:p-5" aria-labelledby="copy-options-heading">
                        <h4 id="copy-options-heading" class="font-semibold text-slate-900">Reusable structure</h4>
                        <p class="mt-1 text-sm text-slate-500">Active classes and their streams are always recreated for the new year.</p>
                        <div class="mt-4 grid gap-3 md:grid-cols-3">
                            <label class="flex items-start gap-3 rounded-xl bg-slate-50 p-3 text-sm text-slate-700">
                                <input wire:model.live="rolloverCopyTerms" type="checkbox" class="mt-0.5 rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                                <span><span class="block font-medium text-slate-900">Copy terms</span><span class="mt-0.5 block text-xs leading-5 text-slate-500">Copies term names, relative dates, and assessment components.</span></span>
                            </label>
                            <label class="flex items-start gap-3 rounded-xl bg-slate-50 p-3 text-sm text-slate-700">
                                <input wire:model.live="rolloverCopySubjects" type="checkbox" class="mt-0.5 rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                                <span><span class="block font-medium text-slate-900">Copy subject allocations</span><span class="mt-0.5 block text-xs leading-5 text-slate-500">Reuses subjects without copying teaching content or results.</span></span>
                            </label>
                            <label class="flex items-start gap-3 rounded-xl bg-slate-50 p-3 text-sm text-slate-700">
                                <input wire:model="rolloverCopyTeachers" type="checkbox" class="mt-0.5 rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                                <span><span class="block font-medium text-slate-900">Retain teacher assignments</span><span class="mt-0.5 block text-xs leading-5 text-slate-500">Copies only active teachers; leave off to review staffing afresh.</span></span>
                            </label>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 p-4 sm:p-5" aria-labelledby="student-rollover-heading">
                        <label class="flex items-start gap-3">
                            <input wire:model.live="rolloverPrepareStudents" type="checkbox" class="mt-1 rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                            <span>
                                <span id="student-rollover-heading" class="block font-semibold text-slate-900">Prepare bulk student placements</span>
                                <span class="mt-1 block text-sm leading-6 text-slate-500">Class placements remain pending until the new year is activated. Choose the same class for repeaters, another class for promotion, or activate now to record graduation or transfer.</span>
                            </span>
                        </label>
                        @error('rolloverPrepareStudents') <p class="mt-2 text-sm text-rose-700">{{ $message }}</p> @enderror
                        @error('rolloverPromotions') <p class="mt-2 text-sm text-rose-700">{{ $message }}</p> @enderror

                        @if ($rolloverPrepareStudents)
                            <div class="mt-4 overflow-hidden rounded-xl border border-slate-200">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                                        <thead class="bg-slate-50 text-slate-600">
                                            <tr>
                                                <th class="px-4 py-3 font-semibold">Source class</th>
                                                <th class="px-4 py-3 font-semibold">Students</th>
                                                <th class="px-4 py-3 font-semibold">Next-year placement</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200">
                                            @forelse ($rolloverSource->classes as $sourceClass)
                                                <tr wire:key="rollover-class-{{ $sourceClass->id }}">
                                                    <td class="px-4 py-3 font-medium text-slate-900">
                                                        {{ $sourceClass->name }}
                                                        @if($sourceClass->stream) <span class="text-slate-500">— {{ $sourceClass->stream->name }}</span> @endif
                                                    </td>
                                                    <td class="px-4 py-3 text-slate-600">{{ $sourceClass->active_enrollments_count }}</td>
                                                    <td class="min-w-72 px-4 py-3">
                                                        <label for="rollover-promotion-{{ $sourceClass->id }}" class="sr-only">Placement for {{ $sourceClass->name }}</label>
                                                        <select id="rollover-promotion-{{ $sourceClass->id }}" wire:model="rolloverPromotions.{{ $sourceClass->id }}"
                                                            class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600"
                                                            @disabled($sourceClass->active_enrollments_count === 0)>
                                                            <option value="">{{ $sourceClass->active_enrollments_count ? 'Choose a placement...' : 'No active students' }}</option>
                                                            @foreach ($rolloverSource->classes as $destinationClass)
                                                                <option value="{{ $destinationClass->id }}">
                                                                    {{ $destinationClass->id === $sourceClass->id ? 'Repeat in' : 'Move to' }} {{ $destinationClass->name }}{{ $destinationClass->stream ? ' — '.$destinationClass->stream->name : '' }}
                                                                </option>
                                                            @endforeach
                                                            <option value="graduate" @disabled(! $rolloverActivate)>Graduate all learners in this class</option>
                                                            <option value="transfer" @disabled(! $rolloverActivate)>Mark all learners as transferred</option>
                                                        </select>
                                                        @error("rolloverPromotions.{$sourceClass->id}") <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">There are no active classes to roll over.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </section>

                    <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:p-5" aria-labelledby="activation-heading">
                        <label class="flex items-start gap-3">
                            <input wire:model.live="rolloverActivate" type="checkbox" class="mt-1 rounded border-amber-300 text-blue-700 focus:ring-blue-600">
                            <span>
                                <span id="activation-heading" class="block font-semibold text-amber-950">Activate the new academic year now</span>
                                <span class="mt-1 block text-sm leading-6 text-amber-800">Other academic years and their active terms will be deactivated. Pending placements become active and previous enrolments are completed.</span>
                            </span>
                        </label>
                        @if ($rolloverActivate && $rolloverCopyTerms)
                            <label class="mt-3 flex items-center gap-3 rounded-xl border border-amber-200 bg-white/70 p-3 text-sm text-amber-950">
                                <input wire:model="rolloverActivateFirstTerm" type="checkbox" class="rounded border-amber-300 text-blue-700 focus:ring-blue-600">
                                Activate the first copied term as well
                            </label>
                        @endif
                        @error('rolloverActivate') <p class="mt-2 text-sm text-rose-700">{{ $message }}</p> @enderror
                        @error('rolloverActivateFirstTerm') <p class="mt-2 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </section>

                    @error('rollover')
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $message }}</div>
                    @enderror
                </fieldset>
            </form>

            <x-slot:footer>
                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <x-button wire:click="closeRollover" variant="ghost" target="closeRollover" :loading="true">Cancel</x-button>
                    <x-button type="submit" form="academic-year-rollover-form" icon="sparkles" target="runRollover" :loading="true">Prepare academic year</x-button>
                </div>
            </x-slot:footer>
        @endif
    </x-modal>

    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="relative w-full sm:max-w-sm">
            <label for="academic-year-search" class="sr-only">Search academic years</label>
            <input id="academic-year-search" type="search" wire:model.live.debounce.300ms="search"
                placeholder="Search academic years..."
                class="w-full rounded-xl border-slate-300 py-2.5 pl-4 pr-10 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
            <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">Searching...</span>
        </div>
        <p class="text-sm text-slate-500">
            <span wire:loading.remove wire:target="search">{{ $years->total() }} {{ \Illuminate\Support\Str::plural('year', $years->total()) }}</span>
            <span wire:loading wire:target="search">Updating results...</span>
        </p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Year</th>
                        <th class="px-5 py-3 font-semibold">Status</th>
                        <th class="px-5 py-3 font-semibold">Terms</th>
                        <th class="px-5 py-3 font-semibold">Classes</th>
                        <th class="px-5 py-3 font-semibold">Date range</th>
                        <th class="px-5 py-3 text-right font-semibold"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($years as $year)
                        <tr wire:key="academic-year-{{ $year->id }}" class="transition hover:bg-slate-50/70">
                            <td class="px-5 py-4 font-medium text-slate-900">{{ $year->name }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $year->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $year->is_active ? 'Active' : 'Inactive' }}</span>
                                @if ($year->is_locked)
                                    <span class="ml-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Locked</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $year->terms_count }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $year->classes_count }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $year->starts_at->format('M d, Y') }} – {{ $year->ends_at->format('M d, Y') }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    @can('create', App\Models\AcademicYear::class)
                                        @can('update', $year)
                                            <x-ui.icon-button wire:click="prepareRollover({{ $year->id }})" icon="sparkles" label="Prepare next year from {{ $year->name }}" target="prepareRollover({{ $year->id }})" />
                                        @endcan
                                    @endcan
                                    @can('update', $year)
                                        <x-ui.icon-button wire:click="edit({{ $year->id }})" icon="edit" label="Edit {{ $year->name }}" target="edit({{ $year->id }})" />
                                    @endcan
                                    @can('delete', $year)
                                        <x-ui.icon-button wire:click="confirmDelete({{ $year->id }})" icon="trash" variant="danger" label="Delete {{ $year->name }}" target="confirmDelete({{ $year->id }})" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="font-medium text-slate-700">{{ $search ? 'No academic years match your search.' : 'No academic years yet.' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $search ? 'Try a different year name.' : 'Create your first academic year to get started.' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-pagination :paginator="$years" />
</div>
