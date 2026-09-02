<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.2em] text-slate-500">Human resources</p>
            <h2 class="mt-1 text-2xl font-bold">Teachers</h2>
            <p class="mt-1 text-sm text-slate-600">Maintain teacher employment profiles and teaching assignments.</p>
        </div>
        @can('create', App\Models\Teacher::class)
            <div class="flex flex-wrap gap-3">
                <x-button wire:click="exportTeachers('csv')" variant="ghost" target="exportTeachers('csv')" :loading="true">Export CSV</x-button>
                <x-button wire:click="exportTeachers('xlsx')" variant="ghost" target="exportTeachers('xlsx')" :loading="true">Export Excel</x-button>
                <x-button wire:click="openImport" variant="secondary" target="openImport" :loading="true">Import teachers</x-button>
                <x-button wire:click="create" target="create" :loading="true" icon="plus">Add teacher</x-button>
            </div>
        @endcan
    </div>

    @if ($showImportForm)
        <section class="overflow-hidden rounded-2xl border border-blue-200 bg-white shadow-sm" aria-labelledby="teacher-import-title">
            <div class="flex flex-col gap-4 border-b border-blue-100 bg-blue-50/70 px-5 py-4 sm:flex-row sm:items-start sm:justify-between sm:px-6">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Bulk onboarding</p>
                    <h3 id="teacher-import-title" class="mt-1 text-lg font-bold text-slate-900">Import teachers</h3>
                    <p class="mt-1 text-sm text-slate-600">Upload a CSV or standard Excel (.xlsx) file. Every row is validated before any teachers are added.</p>
                </div>

                <div class="flex shrink-0 gap-3">
                    <x-button wire:click="downloadImportTemplate" variant="ghost" size="sm" target="downloadImportTemplate" :loading="true">Download CSV template</x-button>
                    <x-button wire:click="closeImport" variant="ghost" size="sm" target="closeImport" :loading="true">Cancel</x-button>
                </div>
            </div>

            <form wire:submit="importTeachers" class="space-y-5 p-5 sm:p-6">
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                    <label for="teacher-import-file" class="block text-sm font-semibold text-slate-700">Teacher import file</label>
                    <input
                        id="teacher-import-file"
                        wire:model="importFile"
                        type="file"
                        accept=".csv,.txt,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                        class="mt-2 block w-full cursor-pointer rounded-lg border border-slate-300 bg-white text-sm text-slate-600 file:mr-4 file:cursor-pointer file:border-0 file:bg-blue-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-800"
                    >
                    <p class="mt-2 text-xs text-slate-500">CSV, TXT, or XLSX up to 10 MB; up to 500 teachers per file.</p>
                    <p wire:loading.flex wire:target="importFile" class="mt-2 items-center gap-2 text-sm font-medium text-blue-700">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M20 12a8 8 0 1 1-2.34-5.66" stroke-linecap="round"/></svg>
                        Uploading file…
                    </p>
                    @error('importFile')
                        <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="min-w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-slate-700">
                            <tr>
                                <th class="px-3 py-2 font-semibold">Required columns</th>
                                <th class="px-3 py-2 font-semibold">Optional columns</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-3 py-3 font-mono">employee_id, first_name, last_name, email, temporary_password</td>
                                <td class="px-3 py-3 font-mono">middle_name, phone, employment_date, status, gender, date_of_birth, nationality, postal_address, residential_address, gps_address, marital_status, religion, emergency_contact_name, emergency_contact_phone, ssnit_number, ghana_card_number</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-slate-500">Use dates in YYYY-MM-DD format. Teacher passwords must contain at least 10 characters. Gender is male, female, or other; marital status is single, married, divorced, or widowed. Dependants, qualifications, work experience, and referees can be added afterwards from the teacher's edit form.</p>

                @if ($importErrors)
                    <section class="rounded-xl border border-amber-200 bg-amber-50 p-4" aria-labelledby="teacher-import-errors-title">
                        <h4 id="teacher-import-errors-title" class="font-semibold text-amber-950">The file was not imported</h4>
                        <p class="mt-1 text-sm text-amber-800">Correct these issues, then upload the file again.</p>
                        <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-amber-900">
                            @foreach ($importErrors as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                    <x-button wire:click="closeImport" type="button" variant="ghost" target="closeImport" :loading="true">Cancel</x-button>
                    <x-button type="submit" icon="save" target="importTeachers" :loading="true">Import teachers</x-button>
                </div>
            </form>
        </section>
    @endif

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
                                    @can('viewAny', App\Models\EmailCampaign::class)
                                        <x-ui.icon-link :href="route('lms.emails.index', ['recipient_type' => 'staff', 'recipient_id' => $teacher->id])" icon="mail"
                                            label="Email {{ $teacher->first_name }} {{ $teacher->last_name }}" />
                                    @endcan
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

    <x-modal :show="$showFormModal" :title="$editingId ? 'Edit teacher' : 'Add teacher'" close-action="closeModals" max-width="3xl">
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
            <div class="rounded-xl border border-blue-100 bg-blue-50/70 p-4">
                <p class="text-sm font-semibold text-blue-950">Portal login</p>
                <p class="mt-1 text-xs text-blue-800">The email above is the teacher's username. {{ $editingId ? 'Leave the password blank to keep the current password.' : 'Set an initial password of at least 10 characters.' }}</p>
                <label for="teacher-password" class="mt-3 block text-sm font-medium text-slate-800">
                    {{ $editingId ? 'New password (optional)' : 'Initial password' }}
                </label>
                <input wire:model.blur="password" id="teacher-password" type="password" autocomplete="new-password"
                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('password')
                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>
            <div><label for="teacher-status" class="block text-sm font-medium">Status</label><select
                    wire:model.blur="status" id="teacher-status"
                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="retired">Retired</option>
                </select></div>

            <x-lms.teacher-staff-fields
                prefix="teacher-form"
                :dependants="$dependants"
                :qualifications="$qualifications"
                :work-experiences="$workExperiences"
                :referees="$referees"
            />

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
