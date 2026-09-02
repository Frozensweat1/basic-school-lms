@props([
    'prefix' => 'staff',
    'dependants' => [],
    'qualifications' => [],
    'workExperiences' => [],
    'referees' => [],
])

<div class="space-y-6">
    <section class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 sm:p-5" aria-labelledby="{{ $prefix }}-employment-heading">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Employment information</p>
            <h3 id="{{ $prefix }}-employment-heading" class="mt-1 font-semibold text-slate-900">Personal and contact details</h3>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
                <label for="{{ $prefix }}-dob" class="block text-sm font-medium">Date of birth</label>
                <input wire:model.blur="dateOfBirth" id="{{ $prefix }}-dob" type="date" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('dateOfBirth')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-nationality" class="block text-sm font-medium">Nationality</label>
                <input wire:model.blur="nationality" id="{{ $prefix }}-nationality" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('nationality')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-postal-address" class="block text-sm font-medium">Postal address</label>
                <input wire:model.blur="postalAddress" id="{{ $prefix }}-postal-address" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('postalAddress')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-residential-address" class="block text-sm font-medium">Residential address</label>
                <input wire:model.blur="residentialAddress" id="{{ $prefix }}-residential-address" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('residentialAddress')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-gps" class="block text-sm font-medium">GPS address</label>
                <input wire:model.blur="gpsAddress" id="{{ $prefix }}-gps" placeholder="GA-000-0000" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('gpsAddress')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-marital-status" class="block text-sm font-medium">Marital status</label>
                <select wire:model.blur="maritalStatus" id="{{ $prefix }}-marital-status" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    <option value="">Select status</option>
                    <option value="single">Single</option>
                    <option value="married">Married</option>
                    <option value="divorced">Divorced</option>
                    <option value="widowed">Widowed</option>
                </select>
                @error('maritalStatus')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-religion" class="block text-sm font-medium">Religion</label>
                <input wire:model.blur="religion" id="{{ $prefix }}-religion" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('religion')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-emergency-name" class="block text-sm font-medium">Emergency contact name</label>
                <input wire:model.blur="emergencyContactName" id="{{ $prefix }}-emergency-name" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('emergencyContactName')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-emergency-phone" class="block text-sm font-medium">Emergency contact phone</label>
                <input wire:model.blur="emergencyContactPhone" id="{{ $prefix }}-emergency-phone" type="tel" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('emergencyContactPhone')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-ssnit" class="block text-sm font-medium">SSNIT number</label>
                <input wire:model.blur="ssnitNumber" id="{{ $prefix }}-ssnit" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('ssnitNumber')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="{{ $prefix }}-ghana-card" class="block text-sm font-medium">Ghana Card number</label>
                <input wire:model.blur="ghanaCardNumber" id="{{ $prefix }}-ghana-card" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('ghanaCardNumber')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-amber-200 bg-amber-50/60 p-4 sm:p-5" aria-labelledby="{{ $prefix }}-dependants-heading">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-800">Dependant information</p>
                <h3 id="{{ $prefix }}-dependants-heading" class="mt-1 font-semibold text-slate-900">Dependants and next of kin</h3>
            </div>
            <x-button type="button" wire:click="addDependant" variant="ghost" size="sm" icon="plus" target="addDependant" :loading="true">Add dependant</x-button>
        </div>

        <div class="mt-4 space-y-3">
            @forelse ($dependants as $index => $dependant)
                <div wire:key="{{ $prefix }}-dependant-{{ $index }}" class="rounded-xl border border-amber-200 bg-white p-3">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_11rem_9rem]">
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Relation</label>
                            <input wire:model.blur="dependants.{{ $index }}.relation" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                            @error('dependants.'.$index.'.relation')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Name</label>
                            <input wire:model.blur="dependants.{{ $index }}.name" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                            @error('dependants.'.$index.'.name')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Date of birth</label>
                            <input wire:model.blur="dependants.{{ $index }}.dateOfBirth" type="date" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                            @error('dependants.'.$index.'.dateOfBirth')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex items-end justify-between gap-2">
                            <label class="flex cursor-pointer items-center gap-2 pb-2 text-xs font-medium text-slate-700">
                                <input wire:model.blur="dependants.{{ $index }}.isNextOfKin" type="checkbox" class="rounded border-slate-300 text-amber-700 focus:ring-amber-600">
                                Next of kin
                            </label>
                            <x-ui.icon-button wire:click="removeDependant({{ $index }})" icon="trash" variant="danger" label="Remove dependant {{ $index + 1 }}" target="removeDependant({{ $index }})" />
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-amber-300 px-4 py-6 text-center text-sm text-slate-500">No dependants added yet.</div>
            @endforelse
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 p-4 sm:p-5" aria-labelledby="{{ $prefix }}-qualifications-heading">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Qualification information</p>
                <h3 id="{{ $prefix }}-qualifications-heading" class="mt-1 font-semibold text-slate-900">Academic and professional qualifications</h3>
            </div>
            <x-button type="button" wire:click="addQualification" variant="ghost" size="sm" icon="plus" target="addQualification" :loading="true">Add qualification</x-button>
        </div>

        <div class="mt-4 space-y-3">
            @forelse ($qualifications as $index => $qualification)
                <div wire:key="{{ $prefix }}-qualification-{{ $index }}" class="rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_9rem_2.5rem]">
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Qualification</label>
                            <input wire:model.blur="qualifications.{{ $index }}.qualification" class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm">
                            @error('qualifications.'.$index.'.qualification')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Institution</label>
                            <input wire:model.blur="qualifications.{{ $index }}.institution" class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm">
                            @error('qualifications.'.$index.'.institution')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Program of study</label>
                            <input wire:model.blur="qualifications.{{ $index }}.programOfStudy" class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm">
                            @error('qualifications.'.$index.'.programOfStudy')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Year of graduation</label>
                            <input wire:model.blur="qualifications.{{ $index }}.yearOfGraduation" class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm">
                            @error('qualifications.'.$index.'.yearOfGraduation')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex items-end justify-end">
                            <x-ui.icon-button wire:click="removeQualification({{ $index }})" icon="trash" variant="danger" label="Remove qualification {{ $index + 1 }}" target="removeQualification({{ $index }})" />
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">No qualifications added yet.</div>
            @endforelse
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 p-4 sm:p-5" aria-labelledby="{{ $prefix }}-experience-heading">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Previous work experience</p>
                <h3 id="{{ $prefix }}-experience-heading" class="mt-1 font-semibold text-slate-900">Employment history</h3>
            </div>
            <x-button type="button" wire:click="addWorkExperience" variant="ghost" size="sm" icon="plus" target="addWorkExperience" :loading="true">Add experience</x-button>
        </div>

        <div class="mt-4 space-y-3">
            @forelse ($workExperiences as $index => $experience)
                <div wire:key="{{ $prefix }}-experience-{{ $index }}" class="rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Institution</label>
                            <input wire:model.blur="workExperiences.{{ $index }}.institution" class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm">
                            @error('workExperiences.'.$index.'.institution')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Country</label>
                            <input wire:model.blur="workExperiences.{{ $index }}.country" class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm">
                            @error('workExperiences.'.$index.'.country')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Position</label>
                            <input wire:model.blur="workExperiences.{{ $index }}.position" class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm">
                            @error('workExperiences.'.$index.'.position')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2 lg:col-span-3">
                            <label class="block text-xs font-medium text-slate-600">Address</label>
                            <input wire:model.blur="workExperiences.{{ $index }}.address" class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm">
                            @error('workExperiences.'.$index.'.address')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Start date</label>
                            <input wire:model.blur="workExperiences.{{ $index }}.startDate" type="date" class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm">
                            @error('workExperiences.'.$index.'.startDate')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">End date</label>
                            <input wire:model.blur="workExperiences.{{ $index }}.endDate" type="date" class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm">
                            @error('workExperiences.'.$index.'.endDate')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex items-end justify-end">
                            <x-ui.icon-button wire:click="removeWorkExperience({{ $index }})" icon="trash" variant="danger" label="Remove experience {{ $index + 1 }}" target="removeWorkExperience({{ $index }})" />
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">No previous work experience added yet.</div>
            @endforelse
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 p-4 sm:p-5" aria-labelledby="{{ $prefix }}-referees-heading">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Referees information</p>
                <h3 id="{{ $prefix }}-referees-heading" class="mt-1 font-semibold text-slate-900">Professional referees</h3>
            </div>
            <x-button type="button" wire:click="addReferee" variant="ghost" size="sm" icon="plus" target="addReferee" :loading="true">Add referee</x-button>
        </div>

        <div class="mt-4 space-y-3">
            @forelse ($referees as $index => $referee)
                <div wire:key="{{ $prefix }}-referee-{{ $index }}" class="rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_1fr_2.5rem]">
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Name</label>
                            <input wire:model.blur="referees.{{ $index }}.name" class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm">
                            @error('referees.'.$index.'.name')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Contact</label>
                            <input wire:model.blur="referees.{{ $index }}.contact" class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm">
                            @error('referees.'.$index.'.contact')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Place of work</label>
                            <input wire:model.blur="referees.{{ $index }}.placeOfWork" class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm">
                            @error('referees.'.$index.'.placeOfWork')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Position at work</label>
                            <input wire:model.blur="referees.{{ $index }}.position" class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm">
                            @error('referees.'.$index.'.position')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex items-end justify-end">
                            <x-ui.icon-button wire:click="removeReferee({{ $index }})" icon="trash" variant="danger" label="Remove referee {{ $index + 1 }}" target="removeReferee({{ $index }})" />
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">No referees added yet.</div>
            @endforelse
        </div>
    </section>
</div>
