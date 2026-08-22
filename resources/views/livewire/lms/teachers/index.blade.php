<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.2em] text-slate-500">Human resources</p>
            <h2 class="mt-1 text-2xl font-bold">Teachers</h2>
            <p class="mt-1 text-sm text-slate-600">Maintain teacher employment profiles and teaching assignments.</p>
        </div>
        @can('create', App\Models\Teacher::class)
            <x-button wire:click="create" target="create" :loading="true" icon="plus">Add teacher</x-button>
        @endcan
    </div>

    @php
        $filtersActive = filled($search) || filled($filterStatus) || filled($filterAssignment);
    @endphp
    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:flex-row lg:items-center lg:justify-between">
        <div class="grid w-full gap-3 sm:grid-cols-2 lg:max-w-4xl lg:grid-cols-[minmax(16rem,1fr)_10rem_11rem]">
            <div class="relative sm:col-span-2 lg:col-span-1">
                <label for="teacher-search" class="sr-only">Search teachers</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
                <input
                    id="teacher-search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search name, employee ID, email, or phone"
                    autocomplete="off"
                    class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-20 text-sm shadow-sm transition focus:border-blue-700 focus:ring-blue-700"
                >
                <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching…</span>
            </div>

            <select wire:model.live="filterStatus" aria-label="Filter by teacher status" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="retired">Retired</option>
            </select>

            <select wire:model.live="filterAssignment" aria-label="Filter by assignment status" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All assignment states</option>
                <option value="assigned">Assigned</option>
                <option value="unassigned">Unassigned</option>
            </select>
        </div>

        <div class="flex shrink-0 items-center gap-3">
            @if ($filtersActive)
                <x-button wire:click="clearFilters" variant="ghost" size="sm" target="clearFilters" :loading="true">Clear filters</x-button>
            @endif
            <p class="whitespace-nowrap text-sm text-slate-500" aria-live="polite">
                <span wire:loading.remove wire:target="search,filterStatus,filterAssignment">{{ $teachers->total() }} {{ \Illuminate\Support\Str::plural('teacher', $teachers->total()) }}</span>
                <span wire:loading wire:target="search,filterStatus,filterAssignment">Updating…</span>
            </p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-5 py-3">Teacher</th>
                        <th class="px-5 py-3">Employee ID</th>
                        <th class="px-5 py-3">Contact</th>
                        <th class="px-5 py-3">Assignments</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($teachers as $teacher)
                        <tr wire:key="teacher-{{ $teacher->id }}">
                            <td class="px-5 py-4 font-medium">{{ $teacher->first_name }} {{ $teacher->middle_name }}
                                {{ $teacher->last_name }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $teacher->employee_id }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $teacher->email ?: $teacher->phone ?: '—' }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $teacher->classes_count }}
                                class{{ $teacher->classes_count === 1 ? '' : 'es' }}, {{ $teacher->class_subjects_count }}
                                subject{{ $teacher->class_subjects_count === 1 ? '' : 's' }}</td>
                            <td class="px-5 py-4"><span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $teacher->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($teacher->status) }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    @can('update', $teacher)
                                        <x-ui.icon-button wire:click="edit({{ $teacher->id }})" icon="edit"
                                            label="Edit {{ $teacher->first_name }} {{ $teacher->last_name }}"
                                            target="edit({{ $teacher->id }})" />
                                        @endcan @can('delete', $teacher)
                                        <x-ui.icon-button wire:click="confirmDelete({{ $teacher->id }})" icon="trash"
                                            variant="danger"
                                            label="Archive {{ $teacher->first_name }} {{ $teacher->last_name }}"
                                            target="confirmDelete({{ $teacher->id }})" />
                                    @endcan
                                </div>
                            </td>
                    </tr>@empty<tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="font-medium text-slate-700">{{ $filtersActive ? 'No teachers match the current search or filters.' : 'No teachers added yet.' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $filtersActive ? 'Clear a filter or try another search term.' : 'Add the first teacher profile to get started.' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <x-pagination :paginator="$teachers" />

    <x-modal :show="$showFormModal" :title="$editingId ? 'Edit teacher' : 'Add teacher'" close-action="closeModals" max-width="xl">
        <form wire:submit="save" class="space-y-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label for="employee-id" class="block text-sm font-medium">Employee ID</label><input
                        wire:model.blur="employeeId" id="employee-id"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    @error('employeeId')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
                <div><label for="employment-date" class="block text-sm font-medium">Employment date</label><input
                        wire:model.blur="employmentDate" id="employment-date" type="date"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    @error('employmentDate')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="grid gap-5 sm:grid-cols-3">
                <div><label for="first-name" class="block text-sm font-medium">First name</label><input
                        wire:model.blur="firstName" id="first-name"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    @error('firstName')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
                <div><label for="middle-name" class="block text-sm font-medium">Middle name</label><input
                        wire:model.blur="middleName" id="middle-name"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm"></div>
                <div><label for="last-name" class="block text-sm font-medium">Last name</label><input
                        wire:model.blur="lastName" id="last-name"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    @error('lastName')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label for="email" class="block text-sm font-medium">Email</label><input wire:model.blur="email"
                        id="email" type="email" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    @error('email')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
                <div><label for="phone" class="block text-sm font-medium">Phone</label><input wire:model.blur="phone"
                        id="phone" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    @error('phone')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div><label for="teacher-status" class="block text-sm font-medium">Status</label><select
                    wire:model.blur="status" id="teacher-status"
                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="retired">Retired</option>
                </select></div>
            <div class="flex justify-end gap-3 pt-2"><x-button wire:click="closeModals" type="button" variant="ghost"
                    target="closeModals" :loading="true">Cancel</x-button><x-button type="submit" icon="save"
                    target="save" :loading="true">Save teacher</x-button></div>
        </form>
    </x-modal>
    <x-modal :show="$showDeleteModal" title="Archive teacher?" close-action="closeModals" max-width="md">
        <p class="text-sm text-slate-600">This removes the teacher from active records while preserving history.
            Teachers with class assignments must first be marked inactive.</p>
        @error('delete')
            <p class="mt-3 text-sm text-rose-700">{{ $message }}</p>
        @enderror
        <x-slot:footer>
            <div class="flex justify-end gap-3"><x-button wire:click="closeModals" variant="ghost"
                    target="closeModals" :loading="true">Cancel</x-button><x-button wire:click="delete"
                    variant="danger" icon="trash" target="delete" :loading="true">Archive teacher</x-button>
            </div>
        </x-slot:footer>
    </x-modal>
</div>
