<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">School records</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Students</h2>
            <p class="mt-1 text-sm text-slate-600">Manage student profiles, enrolments, and student intake.</p>
        </div>

        @can('create', App\Models\Student::class)
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('lms.students.promotions.index') }}" wire:navigate class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M5 19h14M7 16l5-5 5 5M12 11V4" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                    Promotions
                </a>
                <x-button wire:click="exportStudents('csv')" variant="ghost" target="exportStudents('csv')" :loading="true">Export CSV</x-button>
                <x-button wire:click="exportStudents('xlsx')" variant="ghost" target="exportStudents('xlsx')" :loading="true">Export Excel</x-button>
                <x-button wire:click="openImport" variant="secondary" target="openImport" :loading="true">Import students</x-button>
                <x-button wire:click="create" target="create" :loading="true" icon="plus">Admit student</x-button>
            </div>
        @endcan
    </div>

    @if ($showImportForm)
        <section class="overflow-hidden rounded-2xl border border-blue-200 bg-white shadow-sm" aria-labelledby="student-import-title">
            <div class="flex flex-col gap-4 border-b border-blue-100 bg-blue-50/70 px-5 py-4 sm:flex-row sm:items-start sm:justify-between sm:px-6">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Bulk enrolment</p>
                    <h3 id="student-import-title" class="mt-1 text-lg font-bold text-slate-900">Import students</h3>
                    <p class="mt-1 text-sm text-slate-600">Upload a CSV or standard Excel (.xlsx) file. Every row is validated before any students are added.</p>
                </div>

                <div class="flex shrink-0 gap-3">
                    <x-button wire:click="downloadImportTemplate" variant="ghost" size="sm" target="downloadImportTemplate" :loading="true">Download CSV template</x-button>
                    <x-button wire:click="closeImport" variant="ghost" size="sm" target="closeImport" :loading="true">Cancel</x-button>
                </div>
            </div>

            <form wire:submit="importStudents" class="space-y-5 p-5 sm:p-6">
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                    <label for="student-import-file" class="block text-sm font-semibold text-slate-700">Student import file</label>
                    <input
                        id="student-import-file"
                        wire:model="importFile"
                        type="file"
                        accept=".csv,.txt,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                        class="mt-2 block w-full cursor-pointer rounded-lg border border-slate-300 bg-white text-sm text-slate-600 file:mr-4 file:cursor-pointer file:border-0 file:bg-blue-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-800"
                    >
                    <p class="mt-2 text-xs text-slate-500">CSV, TXT, or XLSX up to 10 MB; up to 500 students per file.</p>
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
                                <td class="px-3 py-3 font-mono">student_id, admission_number, first_name, last_name, email, temporary_password, date_of_birth, gender, home_town, region, nationality, admission_date, class_name, enrollment_type, guardian_first_name, guardian_last_name, guardian_email, guardian_phone, guardian_information_date, guardian_gps_address, guardian_city, has_allergies</td>
                                <td class="px-3 py-3 font-mono">middle_name, status, denomination, health_insurance_id, previous_school_name, previous_school_city, previous_school_country, previous_school_gps_address, previous_school_phone, previous_school_last_class, guardian_relationship, guardian_workplace, guardian_ghana_card_number, allergy_details</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-slate-500">Use dates in YYYY-MM-DD format. Student passwords must contain at least 10 characters. Gender is male, female, or other; enrollment type is day or boarding; and <span class="font-mono">class_name</span> must exactly match one active class. A guardian is reused when the email or phone matches; a new guardian signs in with their email and uses their normalized phone number as the initial password.</p>

                @if ($importErrors)
                    <section class="rounded-xl border border-amber-200 bg-amber-50 p-4" aria-labelledby="import-errors-title">
                        <h4 id="import-errors-title" class="font-semibold text-amber-950">The file was not imported</h4>
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
                    <x-button type="submit" icon="save" target="importStudents" :loading="true">Import students</x-button>
                </div>
            </form>
        </section>
    @endif

    @php
        $filtersActive = filled($search) || filled($filterStatus) || filled($filterGender) || filled($filterClassId) || $sortBy !== 'latest' || (int) $perPage !== 15;
    @endphp
    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm xl:flex-row xl:items-center xl:justify-between">
        <div class="grid w-full gap-3 xl:max-w-6xl xl:grid-cols-[minmax(16rem,1fr)_10rem_10rem_13rem_11rem_8rem] xl:items-center">
            <div class="relative">
                <label for="student-search" class="sr-only">Search students</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
                <input
                    id="student-search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search name, ID, admission no., or class"
                    autocomplete="off"
                    class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-20 text-sm shadow-sm transition focus:border-blue-700 focus:ring-blue-700"
                >
                <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching…</span>
            </div>

            <select wire:model.live="filterStatus" aria-label="Filter by student status" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="graduated">Graduated</option>
                <option value="transferred">Transferred</option>
                <option value="withdrawn">Withdrawn</option>
                <option value="suspended">Suspended</option>
            </select>

            <select wire:model.live="filterGender" aria-label="Filter by gender" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All genders</option>
                <option value="female">Female</option>
                <option value="male">Male</option>
                <option value="other">Other</option>
            </select>

            <select wire:model.live="filterClassId" aria-label="Filter by active class" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All active classes</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}">{{ $class->name }}{{ $class->stream ? ' — ' . $class->stream->name : '' }}</option>
                @endforeach
            </select>

            <select wire:model.live="sortBy" aria-label="Sort students" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="latest">Sort: Latest added</option>
                <option value="name_asc">Sort: Name (A-Z)</option>
                <option value="name_desc">Sort: Name (Z-A)</option>
                <option value="admission_latest">Sort: Admission date (newest)</option>
                <option value="admission_oldest">Sort: Admission date (oldest)</option>
            </select>

            <select wire:model.live="perPage" aria-label="Students per page" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="10">10 per page</option>
                <option value="15">15 per page</option>
                <option value="25">25 per page</option>
                <option value="50">50 per page</option>
            </select>
        </div>

        <div class="flex shrink-0 items-center gap-3">
            @if ($filtersActive)
                <x-button wire:click="clearFilters" variant="ghost" size="sm" target="clearFilters" :loading="true">Clear filters</x-button>
            @endif
            <p class="whitespace-nowrap text-sm text-slate-500" aria-live="polite">
                <span wire:loading.remove wire:target="search,filterStatus,filterGender,filterClassId,sortBy,perPage">{{ $students->total() }} {{ \Illuminate\Support\Str::plural('student', $students->total()) }}</span>
                <span wire:loading wire:target="search,filterStatus,filterGender,filterClassId,sortBy,perPage">Updating…</span>
            </p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Student</th>
                        <th class="px-5 py-3 font-semibold">Admission</th>
                        <th class="px-5 py-3 font-semibold">Class</th>
                        <th class="px-5 py-3 font-semibold">Status</th>
                        <th class="px-5 py-3 text-right font-semibold"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($students as $student)
                        @php
                            $activeEnrollment = $student->enrollments->first();
                            $statusStyle = match ($student->status) {
                                'active' => 'bg-emerald-100 text-emerald-700',
                                'graduated' => 'bg-blue-100 text-blue-700',
                                'transferred' => 'bg-amber-100 text-amber-700',
                                'suspended' => 'bg-rose-100 text-rose-700',
                                default => 'bg-slate-100 text-slate-600',
                            };
                        @endphp
                        <tr wire:key="student-{{ $student->id }}" class="transition hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <div class="font-medium text-slate-900">{{ trim($student->first_name . ' ' . $student->middle_name . ' ' . $student->last_name) }}</div>
                                <div class="mt-0.5 text-xs text-slate-500">{{ $student->student_id }}</div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $student->admission_number }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $activeEnrollment?->schoolClass?->name ?? 'Unassigned' }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusStyle }}">{{ ucfirst($student->status) }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    @can('viewAny', App\Models\EmailCampaign::class)
                                        <x-ui.icon-link :href="route('lms.emails.index', ['recipient_type' => 'student', 'recipient_id' => $student->id])" icon="mail" label="Email {{ $student->first_name }} {{ $student->last_name }}" />
                                    @endcan
                                    @can('update', $student)
                                        <x-ui.icon-button wire:click="edit({{ $student->id }})" icon="edit" label="Edit {{ $student->first_name }} {{ $student->last_name }}" target="edit({{ $student->id }})" />
                                    @endcan
                                    @can('delete', $student)
                                        <x-ui.icon-button wire:click="confirmDelete({{ $student->id }})" icon="trash" variant="danger" label="Archive {{ $student->first_name }} {{ $student->last_name }}" target="confirmDelete({{ $student->id }})" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <p class="font-medium text-slate-700">{{ $filtersActive ? 'No students match the current search or filters.' : 'No students have been enrolled yet.' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $filtersActive ? 'Clear a filter or try a different search term.' : 'Add one student manually or import a CSV/XLSX file to get started.' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-pagination :paginator="$students" />

    <x-modal :show="$showFormModal" :title="$editingId ? 'Edit admission record' : 'New student admission'" close-action="closeModals" max-width="3xl">
        <form wire:submit="save" class="space-y-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="student-id" class="block text-sm font-medium">Student ID</label>
                    <input wire:model.blur="studentId" id="student-id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    @error('studentId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="admission-number" class="block text-sm font-medium">Admission number</label>
                    <input wire:model.blur="admissionNumber" id="admission-number" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    @error('admissionNumber')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <label for="first-name" class="block text-sm font-medium">First name</label>
                    <input wire:model.blur="firstName" id="first-name" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    @error('firstName')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="middle-name" class="block text-sm font-medium">Middle name</label>
                    <input wire:model.blur="middleName" id="middle-name" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                </div>
                <div>
                    <label for="last-name" class="block text-sm font-medium">Last name</label>
                    <input wire:model.blur="lastName" id="last-name" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    @error('lastName')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="rounded-xl border border-blue-100 bg-blue-50/70 p-4">
                <p class="text-sm font-semibold text-blue-950">Student portal login</p>
                <p class="mt-1 text-xs text-blue-800">{{ $editingId ? 'Update the email if needed and leave the password blank to keep the current password.' : 'Create the credentials the student will use to access lessons and assignments.' }}</p>
                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="student-email" class="block text-sm font-medium text-slate-800">Login email</label>
                        <input wire:model.blur="email" id="student-email" type="email" autocomplete="email"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                        @error('email')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="student-password" class="block text-sm font-medium text-slate-800">{{ $editingId ? 'New password (optional)' : 'Initial password' }}</label>
                        <input wire:model.blur="password" id="student-password" type="password" autocomplete="new-password"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                        @error('password')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <x-lms.student-admission-fields :classes="$classes" :has-allergies="$hasAllergies" prefix="student-form" />

            <div class="flex justify-end gap-3">
                <x-button wire:click="closeModals" type="button" variant="ghost" target="closeModals" :loading="true">Cancel</x-button>
                <x-button type="submit" icon="save" target="save" :loading="true">Save student</x-button>
            </div>
        </form>
    </x-modal>

    <x-modal :show="$showDeleteModal" title="Archive student?" close-action="closeModals" max-width="md">
        <p class="text-sm text-slate-600">Student records are archived, never permanently removed, to preserve school history.</p>
        <x-slot:footer>
            <div class="flex justify-end gap-3">
                <x-button wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button>
                <x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Archive student</x-button>
            </div>
        </x-slot:footer>
    </x-modal>
</div>
