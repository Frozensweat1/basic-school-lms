@props([
    'classes' => collect(),
    'prefix' => 'student-admission',
    'hasAllergies' => false,
])

<div class="space-y-6">
    <section class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 sm:p-5" aria-labelledby="{{ $prefix }}-personal-heading">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Student's personal information</p>
            <h3 id="{{ $prefix }}-personal-heading" class="mt-1 font-semibold text-slate-900">Personal and identity details</h3>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label for="{{ $prefix }}-dob" class="block text-sm font-medium">Date of birth</label>
                <input wire:model.blur="dateOfBirth" id="{{ $prefix }}-dob" type="date" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('dateOfBirth')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-gender" class="block text-sm font-medium">Gender</label>
                <select wire:model.blur="gender" id="{{ $prefix }}-gender" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    <option value="">Select gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
                @error('gender')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-home-town" class="block text-sm font-medium">Home town</label>
                <input wire:model.blur="homeTown" id="{{ $prefix }}-home-town" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('homeTown')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-region" class="block text-sm font-medium">Region</label>
                <input wire:model.blur="region" id="{{ $prefix }}-region" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('region')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-nationality" class="block text-sm font-medium">Nationality</label>
                <input wire:model.blur="nationality" id="{{ $prefix }}-nationality" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('nationality')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-denomination" class="block text-sm font-medium">Denomination</label>
                <input wire:model.blur="denomination" id="{{ $prefix }}-denomination" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('denomination')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <label for="{{ $prefix }}-health-insurance" class="block text-sm font-medium">Health Insurance ID</label>
                <input wire:model.blur="healthInsuranceId" id="{{ $prefix }}-health-insurance" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm sm:max-w-md">
                @error('healthInsuranceId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 p-4 sm:p-5" aria-labelledby="{{ $prefix }}-enrollment-heading">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Admission</p>
        <h3 id="{{ $prefix }}-enrollment-heading" class="mt-1 font-semibold text-slate-900">Class and enrollment</h3>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="{{ $prefix }}-admission-date" class="block text-sm font-medium">Admission date</label>
                <input wire:model.blur="admissionDate" id="{{ $prefix }}-admission-date" type="date" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('admissionDate')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-class" class="block text-sm font-medium">Class</label>
                <select wire:model.blur="schoolClassId" id="{{ $prefix }}-class" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    <option value="">Select class</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}{{ $class->stream ? ' — '.$class->stream->name : '' }}</option>
                    @endforeach
                </select>
                @error('schoolClassId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-enrollment-type" class="block text-sm font-medium">Enrollment type</label>
                <select wire:model.blur="enrollmentType" id="{{ $prefix }}-enrollment-type" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    <option value="day">Day</option>
                    <option value="boarding">Boarding</option>
                </select>
                @error('enrollmentType')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-status" class="block text-sm font-medium">Student status</label>
                <select wire:model.blur="status" id="{{ $prefix }}-status" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    <option value="active">Active</option>
                    <option value="graduated">Graduated</option>
                    <option value="transferred">Transferred</option>
                    <option value="withdrawn">Withdrawn</option>
                    <option value="suspended">Suspended</option>
                </select>
                @error('status')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 p-4 sm:p-5" aria-labelledby="{{ $prefix }}-previous-school-heading">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Previous school information</p>
        <h3 id="{{ $prefix }}-previous-school-heading" class="mt-1 font-semibold text-slate-900">Last school attended</h3>
        <p class="mt-1 text-xs text-slate-500">Leave this section blank if the student has not attended another school.</p>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label for="{{ $prefix }}-previous-name" class="block text-sm font-medium">Last school attended</label>
                <input wire:model.blur="previousSchoolName" id="{{ $prefix }}-previous-name" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('previousSchoolName')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-previous-city" class="block text-sm font-medium">Town / city</label>
                <input wire:model.blur="previousSchoolCity" id="{{ $prefix }}-previous-city" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('previousSchoolCity')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-previous-country" class="block text-sm font-medium">Country</label>
                <input wire:model.blur="previousSchoolCountry" id="{{ $prefix }}-previous-country" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('previousSchoolCountry')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-previous-gps" class="block text-sm font-medium">School GPS address</label>
                <input wire:model.blur="previousSchoolGpsAddress" id="{{ $prefix }}-previous-gps" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('previousSchoolGpsAddress')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-previous-phone" class="block text-sm font-medium">School phone number</label>
                <input wire:model.blur="previousSchoolPhone" id="{{ $prefix }}-previous-phone" type="tel" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('previousSchoolPhone')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-previous-class" class="block text-sm font-medium">Last class</label>
                <input wire:model.blur="previousSchoolLastClass" id="{{ $prefix }}-previous-class" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('previousSchoolLastClass')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-amber-200 bg-amber-50/60 p-4 sm:p-5" aria-labelledby="{{ $prefix }}-guardian-heading">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-800">Guardian / parent information</p>
        <h3 id="{{ $prefix }}-guardian-heading" class="mt-1 font-semibold text-slate-900">Primary contact and portal access</h3>
        <p class="mt-1 text-xs text-slate-600">An existing guardian is reused when the email or phone matches. For a new guardian, the email is the username and the phone number is the initial password.</p>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label for="{{ $prefix }}-guardian-first" class="block text-sm font-medium">First name</label>
                <input wire:model.blur="guardianFirstName" id="{{ $prefix }}-guardian-first" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('guardianFirstName')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-guardian-last" class="block text-sm font-medium">Last name</label>
                <input wire:model.blur="guardianLastName" id="{{ $prefix }}-guardian-last" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('guardianLastName')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-guardian-relationship" class="block text-sm font-medium">Relationship</label>
                <input wire:model.blur="guardianRelationship" id="{{ $prefix }}-guardian-relationship" placeholder="Mother, Father, Guardian" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('guardianRelationship')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-guardian-email" class="block text-sm font-medium">Email</label>
                <input wire:model.blur="guardianEmail" id="{{ $prefix }}-guardian-email" type="email" autocomplete="email" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('guardianEmail')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-guardian-phone" class="block text-sm font-medium">Phone number</label>
                <input wire:model.blur="guardianPhone" id="{{ $prefix }}-guardian-phone" type="tel" autocomplete="tel" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('guardianPhone')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-guardian-date" class="block text-sm font-medium">Information date</label>
                <input wire:model.blur="guardianInformationDate" id="{{ $prefix }}-guardian-date" type="date" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('guardianInformationDate')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-guardian-gps" class="block text-sm font-medium">GPS address</label>
                <input wire:model.blur="guardianGpsAddress" id="{{ $prefix }}-guardian-gps" placeholder="GA-000-0000" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('guardianGpsAddress')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-guardian-city" class="block text-sm font-medium">Town / city</label>
                <input wire:model.blur="guardianCity" id="{{ $prefix }}-guardian-city" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('guardianCity')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-guardian-workplace" class="block text-sm font-medium">Workplace / company</label>
                <input wire:model.blur="guardianWorkplace" id="{{ $prefix }}-guardian-workplace" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('guardianWorkplace')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <label for="{{ $prefix }}-guardian-ghana-card" class="block text-sm font-medium">Ghana Card number</label>
                <input wire:model.blur="guardianGhanaCardNumber" id="{{ $prefix }}-guardian-ghana-card" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm sm:max-w-md">
                @error('guardianGhanaCardNumber')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-rose-200 bg-rose-50/50 p-4 sm:p-5" aria-labelledby="{{ $prefix }}-medical-heading">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Medical information</p>
        <h3 id="{{ $prefix }}-medical-heading" class="mt-1 font-semibold text-slate-900">Allergies and health notes</h3>

        <label class="mt-4 flex cursor-pointer items-center gap-3 rounded-xl border border-rose-200 bg-white p-3 text-sm font-medium text-slate-800">
            <input wire:model.live="hasAllergies" type="checkbox" class="rounded border-slate-300 text-rose-700 focus:ring-rose-600">
            The student has a known allergy
        </label>
        @error('hasAllergies')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror

        @if ($hasAllergies)
            <div class="mt-4">
                <label for="{{ $prefix }}-allergy-details" class="block text-sm font-medium">Allergy details</label>
                <textarea wire:model.blur="allergyDetails" id="{{ $prefix }}-allergy-details" rows="3" placeholder="Describe the allergy, known triggers, severity, and emergency response." class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm"></textarea>
                @error('allergyDetails')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
        @endif
    </section>
</div>
