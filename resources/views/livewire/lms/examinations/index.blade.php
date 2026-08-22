<div class="space-y-6">
    <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Academic</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">Examinations</h2>
        </div>
        <x-button wire:click="create" variant="primary" size="md" icon="plus" target="create">New exam</x-button>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @if($examinations->isNotEmpty())<div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Examination</th><th class="px-5 py-3">Subject</th><th class="px-5 py-3">Date</th><th class="px-5 py-3">Status</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach($examinations as $examination)<tr><td class="px-5 py-4 font-medium">{{ $examination->title }}</td><td class="px-5 py-4">{{ $examination->classSubject->subject->name }}</td><td class="px-5 py-4">{{ $examination->exam_date->format('d M Y') }}</td><td class="px-5 py-4">{{ ucfirst($examination->status) }}</td></tr>@endforeach</tbody></table></div>@else
        <x-empty-state
            title="No examinations scheduled"
            description="Create exams, assign schedules, and track results for each class."
            :action="'<x-button variant=\'primary\'>Schedule exam</x-button>'"
        />
        @endif
    </div>
</div>
<x-modal :show="$showFormModal" :title="$editingId ? 'Edit examination' : 'Schedule examination'" close-action="closeModals" max-width="xl"><form wire:submit="save" class="space-y-4"><input wire:model.blur="title" placeholder="Examination title" class="w-full rounded-lg border-slate-300"><select wire:model.blur="academicYearId" class="w-full rounded-lg border-slate-300"><option value="">Academic year</option>@foreach($years as $year)<option value="{{ $year->id }}">{{ $year->name }}</option>@endforeach</select><select wire:model.blur="termId" class="w-full rounded-lg border-slate-300"><option value="">Term</option>@foreach($terms as $term)<option value="{{ $term->id }}">{{ $term->name }}</option>@endforeach</select><select wire:model.blur="classSubjectId" class="w-full rounded-lg border-slate-300"><option value="">Class subject</option>@foreach($classSubjects as $classSubject)<option value="{{ $classSubject->id }}">{{ $classSubject->schoolClass->name }} · {{ $classSubject->subject->name }}</option>@endforeach</select><select wire:model.blur="teacherId" class="w-full rounded-lg border-slate-300"><option value="">Teacher</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->first_name }} {{ $teacher->last_name }}</option>@endforeach</select><input wire:model.blur="examDate" type="date" class="w-full rounded-lg border-slate-300"><input wire:model.blur="maxScore" type="number" step=".01" class="w-full rounded-lg border-slate-300"><select wire:model.blur="status" class="w-full rounded-lg border-slate-300"><option value="draft">Draft</option><option value="scheduled">Scheduled</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select><div class="flex justify-end gap-3"><x-button type="button" wire:click="closeModals" variant="secondary">Cancel</x-button><x-button type="submit" icon="save" target="save">Save examination</x-button><x-pagination :paginator="$examinations" /></div></form></x-modal>
