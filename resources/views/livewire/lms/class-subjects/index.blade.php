<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Curriculum setup</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Class subjects</h2>
            <p class="mt-1 text-sm text-slate-600">Allocate subjects to classes and assign their responsible teacher.</p>
        </div>
        @can('create', App\Models\ClassSubject::class)
            <x-button wire:click="create" variant="primary" icon="plus" target="create">Allocate subject</x-button>
        @endcan
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Class</th>
                        <th class="px-5 py-3">Subject</th>
                        <th class="px-5 py-3">Responsible teacher</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($classSubjects as $classSubject)
                        <tr wire:key="class-subject-{{ $classSubject->id }}">
                            <td class="px-5 py-4 font-medium text-slate-900">
                                {{ $classSubject->schoolClass->name }}
                                <span class="block text-xs font-normal text-slate-500">{{ $classSubject->schoolClass->academicYear->name }}</span>
                            </td>
                            <td class="px-5 py-4 text-slate-700">{{ $classSubject->subject->name }} <span class="text-slate-400">{{ $classSubject->subject->code ? '· '.$classSubject->subject->code : '' }}</span></td>
                            <td class="px-5 py-4 text-slate-700">{{ $classSubject->teacher ? $classSubject->teacher->first_name.' '.$classSubject->teacher->last_name : 'Not assigned' }}</td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    @can('update', $classSubject)
                                        <x-ui.icon-button wire:click="edit({{ $classSubject->id }})" icon="edit" label="Edit allocation" target="edit({{ $classSubject->id }})" />
                                    @endcan
                                    @can('delete', $classSubject)
                                        <x-ui.icon-button wire:click="confirmDelete({{ $classSubject->id }})" icon="trash" label="Remove allocation" variant="danger" target="confirmDelete({{ $classSubject->id }})" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-12 text-center text-slate-500">No class subjects allocated yet. Allocate the first subject to begin planning topics.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-modal :show="$showFormModal" :title="$editingId ? 'Edit class subject' : 'Allocate subject'" close-action="closeModals">
        <form wire:submit="save" class="space-y-5">
            <div>
                <label for="schoolClassId" class="block text-sm font-medium text-slate-700">Class</label>
                <select wire:model.blur="schoolClassId" id="schoolClassId" class="mt-1 block w-full rounded-lg border-slate-300">
                    <option value="">Choose a class</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->name }} — {{ $class->academicYear->name }}</option>
                    @endforeach
                </select>
                @error('schoolClassId') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="subjectId" class="block text-sm font-medium text-slate-700">Subject</label>
                <select wire:model.blur="subjectId" id="subjectId" class="mt-1 block w-full rounded-lg border-slate-300">
                    <option value="">Choose a subject</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}{{ $subject->code ? ' — '.$subject->code : '' }}</option>
                    @endforeach
                </select>
                @error('subjectId') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="teacherId" class="block text-sm font-medium text-slate-700">Responsible teacher <span class="text-slate-400">(optional)</span></label>
                <select wire:model.blur="teacherId" id="teacherId" class="mt-1 block w-full rounded-lg border-slate-300">
                    <option value="">Assign later</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->first_name }} {{ $teacher->last_name }}</option>
                    @endforeach
                </select>
                @error('teacherId') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end gap-3">
                <x-button type="button" wire:click="closeModals" variant="secondary" icon="close" target="closeModals">Cancel</x-button>
                <x-button type="submit" variant="primary" icon="save" target="save">Save allocation</x-button>
            </div>
        </form>
    </x-modal>

    <x-modal :show="$showDeleteModal" title="Remove class subject?" close-action="closeModals" max-width="md">
        <p class="text-sm text-slate-600">Removing this allocation also removes its dependent curriculum records. This cannot be undone.</p>
        <x-slot:footer>
            <div class="flex justify-end gap-3">
                <x-button wire:click="closeModals" variant="secondary" icon="close" target="closeModals">Cancel</x-button>
                <x-button wire:click="delete" variant="danger" icon="trash" target="delete">Remove allocation</x-button>
            </div>
        </x-slot:footer>
    </x-modal>
</div>
