<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Academic work</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ $heading }}</h2>
            <p class="mt-1 text-sm text-slate-600">{{ $description }}</p>
        </div>
        <x-button wire:click="create" variant="primary" icon="plus" target="create">New assignment</x-button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Assignment</th><th class="px-5 py-3">Class subject</th><th class="px-5 py-3">Due</th><th class="px-5 py-3">Submissions</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($assignments as $assignment)
                        <tr wire:key="assignment-{{ $assignment->id }}">
                            <td class="px-5 py-4"><p class="font-medium text-slate-900">{{ $assignment->title }}</p><p class="mt-1 text-xs text-slate-500">Maximum score: {{ $assignment->max_score }}</p></td>
                            <td class="px-5 py-4 text-slate-700">{{ $assignment->classSubject->schoolClass->name }} · {{ $assignment->classSubject->subject->name }}@if ($assignment->topic)<span class="block text-xs text-slate-500">{{ $assignment->topic->title }}</span>@endif</td>
                            <td class="px-5 py-4 text-slate-700">{{ $assignment->due_at->format('d M Y, H:i') }}</td><td class="px-5 py-4 text-slate-700">{{ $assignment->submissions_count }}</td>
                            <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $assignment->status === 'published' ? 'bg-emerald-100 text-emerald-700' : ($assignment->status === 'closed' ? 'bg-slate-100 text-slate-600' : 'bg-amber-100 text-amber-700') }}">{{ ucfirst($assignment->status) }}</span></td>
                            <td class="px-5 py-4"><div class="flex justify-end gap-2"><x-ui.icon-button wire:click="edit({{ $assignment->id }})" icon="edit" label="Edit assignment" target="edit({{ $assignment->id }})" /><x-ui.icon-button wire:click="confirmDelete({{ $assignment->id }})" icon="trash" label="Archive assignment" variant="danger" target="confirmDelete({{ $assignment->id }})" /></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">No assignments yet. Create one for a class subject to get started.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-modal :show="$showFormModal" :title="$editingId ? 'Edit assignment' : 'New assignment'" close-action="closeModals" max-width="xl">
        <form wire:submit="save" class="space-y-5">
            <div class="grid gap-5 sm:grid-cols-2"><div><label for="classSubjectId" class="block text-sm font-medium text-slate-700">Class subject</label><select wire:model.blur="classSubjectId" id="classSubjectId" class="mt-1 block w-full rounded-lg border-slate-300"><option value="">Choose a class subject</option>@foreach ($classSubjects as $classSubject)<option value="{{ $classSubject->id }}">{{ $classSubject->schoolClass->name }} · {{ $classSubject->subject->name }}</option>@endforeach</select>@error('classSubjectId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div><div><label for="topicId" class="block text-sm font-medium text-slate-700">Topic <span class="text-slate-400">(optional)</span></label><select wire:model.blur="topicId" id="topicId" class="mt-1 block w-full rounded-lg border-slate-300"><option value="">No topic</option>@foreach ($topics as $topic)<option value="{{ $topic->id }}">{{ $topic->title }}</option>@endforeach</select>@error('topicId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div></div>
            <div class="grid gap-5 sm:grid-cols-2"><div><label for="lessonId" class="block text-sm font-medium text-slate-700">Lesson <span class="text-slate-400">(optional)</span></label><select wire:model.blur="lessonId" id="lessonId" class="mt-1 block w-full rounded-lg border-slate-300"><option value="">No lesson</option>@foreach ($lessons as $lesson)<option value="{{ $lesson->id }}">{{ $lesson->title }}</option>@endforeach</select>@error('lessonId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>@if ($canChooseTeacher)<div><label for="teacherId" class="block text-sm font-medium text-slate-700">Teacher</label><select wire:model.blur="teacherId" id="teacherId" class="mt-1 block w-full rounded-lg border-slate-300"><option value="">Use class subject teacher</option>@foreach ($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->first_name }} {{ $teacher->last_name }}</option>@endforeach</select>@error('teacherId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>@endif</div>
            <div class="grid gap-5 sm:grid-cols-2"><div><label for="title" class="block text-sm font-medium text-slate-700">Title</label><input wire:model.blur="title" id="title" class="mt-1 block w-full rounded-lg border-slate-300">@error('title')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div><div><label for="maxScore" class="block text-sm font-medium text-slate-700">Maximum score</label><input wire:model.blur="maxScore" id="maxScore" type="number" step="0.01" min="0.01" class="mt-1 block w-full rounded-lg border-slate-300">@error('maxScore')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div></div>
            <div><label for="instructions" class="block text-sm font-medium text-slate-700">Instructions</label><x-ui.rich-text-editor wire:model="instructions" id="instructions" class="mt-1" placeholder="Explain the task, expectations, and submission requirements…" />@error('instructions')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
            <div class="grid gap-5 sm:grid-cols-2"><div><label for="opensAt" class="block text-sm font-medium text-slate-700">Opens at <span class="text-slate-400">(optional)</span></label><input wire:model.blur="opensAt" id="opensAt" type="datetime-local" class="mt-1 block w-full rounded-lg border-slate-300">@error('opensAt')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div><div><label for="dueAt" class="block text-sm font-medium text-slate-700">Due at</label><input wire:model.blur="dueAt" id="dueAt" type="datetime-local" class="mt-1 block w-full rounded-lg border-slate-300">@error('dueAt')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div></div>
            <div class="grid gap-5 sm:grid-cols-2"><div><label for="status" class="block text-sm font-medium text-slate-700">Status</label><select wire:model.blur="status" id="status" class="mt-1 block w-full rounded-lg border-slate-300"><option value="draft">Draft</option><option value="published">Published</option><option value="closed">Closed</option></select>@error('status')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div><label class="mt-7 flex items-center gap-3 rounded-lg bg-slate-50 px-3 py-3 text-sm text-slate-700"><input wire:model="allowLateSubmission" type="checkbox" class="rounded border-slate-300 text-blue-700">Allow late submissions</label></div>
            <div class="flex justify-end gap-3"><x-button type="button" wire:click="closeModals" variant="secondary" icon="close" target="closeModals">Cancel</x-button><x-button type="submit" variant="primary" icon="save" target="save">Save assignment</x-button></div>
        </form>
    </x-modal>

    <x-modal :show="$showDeleteModal" title="Archive assignment?" close-action="closeModals" max-width="md"><p class="text-sm text-slate-600">The assignment will be archived and hidden from normal lists. Existing submissions are retained.</p><x-slot:footer><div class="flex justify-end gap-3"><x-button wire:click="closeModals" variant="secondary" icon="close" target="closeModals">Cancel</x-button><x-button wire:click="delete" variant="danger" icon="trash" target="delete">Archive assignment</x-button></div></x-slot:footer></x-modal>
</div>
