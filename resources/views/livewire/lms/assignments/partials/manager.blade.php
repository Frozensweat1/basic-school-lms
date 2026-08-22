<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Academic work</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ $heading }}</h2>
            <p class="mt-1 text-sm text-slate-600">{{ $description }}</p>
        </div>

        <x-button wire:click="create" variant="primary" icon="plus" target="create" :loading="true">New assignment</x-button>
    </div>

    @php
        $filtersActive = filled($search) || filled($filterClassSubjectId) || filled($filterStatus) || filled($filterDueState);
    @endphp
    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm xl:flex-row xl:items-center xl:justify-between">
        <div class="grid w-full gap-3 sm:grid-cols-2 xl:max-w-6xl xl:grid-cols-[minmax(17rem,1fr)_minmax(13rem,1fr)_9rem_9rem]">
            <div class="relative sm:col-span-2 xl:col-span-1">
                <label for="assignment-search" class="sr-only">Search assignments</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
                <input
                    id="assignment-search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search title, topic, class, or teacher"
                    autocomplete="off"
                    class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-20 text-sm shadow-sm transition focus:border-blue-700 focus:ring-blue-700"
                >
                <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching&hellip;</span>
            </div>

            <select wire:model.live="filterClassSubjectId" aria-label="Filter by class subject" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All class subjects</option>
                @foreach ($classSubjects as $classSubject)
                    <option value="{{ $classSubject->id }}">{{ $classSubject->schoolClass->name }} &middot; {{ $classSubject->subject->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterStatus" aria-label="Filter by assignment status" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All statuses</option>
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                <option value="closed">Closed</option>
            </select>

            <select wire:model.live="filterDueState" aria-label="Filter by assignment schedule" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All schedules</option>
                <option value="upcoming">Opens later</option>
                <option value="open">Open now</option>
                <option value="overdue">Past due</option>
            </select>
        </div>

        <div class="flex shrink-0 items-center gap-3">
            @if ($filtersActive)
                <x-button wire:click="clearFilters" variant="ghost" size="sm" target="clearFilters" :loading="true">Clear filters</x-button>
            @endif
            <p class="whitespace-nowrap text-sm text-slate-500" aria-live="polite">
                <span wire:loading.remove wire:target="search,filterClassSubjectId,filterStatus,filterDueState">{{ $assignments->total() }} {{ \Illuminate\Support\Str::plural('assignment', $assignments->total()) }}</span>
                <span wire:loading wire:target="search,filterClassSubjectId,filterStatus,filterDueState">Updating&hellip;</span>
            </p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Assignment</th>
                        <th class="px-5 py-3">Class subject</th>
                        <th class="px-5 py-3">Due</th>
                        <th class="px-5 py-3">Work</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($assignments as $assignment)
                        <tr wire:key="assignment-{{ $assignment->id }}" class="transition hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-900">{{ $assignment->title }}</p>
                                <p class="mt-1 text-xs text-slate-500">Maximum score: {{ $assignment->max_score }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                {{ $assignment->classSubject->schoolClass->name }} &middot; {{ $assignment->classSubject->subject->name }}
                                @if ($assignment->topic)
                                    <span class="block text-xs text-slate-500">{{ $assignment->topic->title }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                {{ $assignment->due_at->format('d M Y, H:i') }}
                                @if ($assignment->opens_at?->isFuture())
                                    <span class="block text-xs text-amber-700">Opens {{ $assignment->opens_at->format('d M, H:i') }}</span>
                                @elseif ($assignment->due_at->isPast())
                                    <span class="block text-xs text-rose-700">Past due</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                <p>{{ $assignment->submissions_count }} {{ \Illuminate\Support\Str::plural('submission', $assignment->submissions_count) }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $assignment->attachments_count }} {{ \Illuminate\Support\Str::plural('attachment', $assignment->attachments_count) }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $assignment->status === 'published' ? 'bg-emerald-100 text-emerald-700' : ($assignment->status === 'closed' ? 'bg-slate-100 text-slate-600' : 'bg-amber-100 text-amber-700') }}">{{ ucfirst($assignment->status) }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('lms.assignments.submissions', $assignment) }}" class="inline-flex cursor-pointer items-center rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-200" title="View submissions">
                                        View submissions
                                    </a>
                                    @can('update', $assignment)
                                        <x-ui.icon-button wire:click="edit({{ $assignment->id }})" icon="edit" label="Edit {{ $assignment->title }}" target="edit({{ $assignment->id }})" />
                                    @endcan
                                    @can('delete', $assignment)
                                        <x-ui.icon-button wire:click="confirmDelete({{ $assignment->id }})" icon="trash" label="Archive {{ $assignment->title }}" variant="danger" target="confirmDelete({{ $assignment->id }})" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="font-medium text-slate-700">{{ $filtersActive ? 'No assignments match the current search or filters.' : 'No assignments yet.' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $filtersActive ? 'Clear a filter or try another search term.' : 'Create your first assignment to give learners work.' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-pagination :paginator="$assignments" />

    <x-modal :show="$showFormModal" :title="$editingId ? 'Edit assignment' : 'New assignment'" close-action="closeModals" max-width="xl">
        <form wire:submit="save" class="space-y-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="classSubjectId" class="block text-sm font-medium text-slate-700">Class subject</label>
                    <select wire:model.blur="classSubjectId" id="classSubjectId" class="mt-1 block w-full rounded-lg border-slate-300">
                        <option value="">Choose a class subject</option>
                        @foreach ($classSubjects as $classSubject)
                            <option value="{{ $classSubject->id }}">{{ $classSubject->schoolClass->name }} &middot; {{ $classSubject->subject->name }}</option>
                        @endforeach
                    </select>
                    @error('classSubjectId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="topicId" class="block text-sm font-medium text-slate-700">Topic <span class="text-slate-400">(optional)</span></label>
                    <select wire:model.blur="topicId" id="topicId" class="mt-1 block w-full rounded-lg border-slate-300">
                        <option value="">No topic</option>
                        @foreach ($topics as $topic)
                            <option value="{{ $topic->id }}">{{ $topic->title }}</option>
                        @endforeach
                    </select>
                    @error('topicId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="lessonId" class="block text-sm font-medium text-slate-700">Lesson <span class="text-slate-400">(optional)</span></label>
                    <select wire:model.blur="lessonId" id="lessonId" class="mt-1 block w-full rounded-lg border-slate-300">
                        <option value="">No lesson</option>
                        @foreach ($lessons as $lesson)
                            <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
                        @endforeach
                    </select>
                    @error('lessonId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                @if ($canChooseTeacher)
                    <div>
                        <label for="teacherId" class="block text-sm font-medium text-slate-700">Teacher</label>
                        <select wire:model.blur="teacherId" id="teacherId" class="mt-1 block w-full rounded-lg border-slate-300">
                            <option value="">Use class subject teacher</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->first_name }} {{ $teacher->last_name }}</option>
                            @endforeach
                        </select>
                        @error('teacherId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                @endif
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="title" class="block text-sm font-medium text-slate-700">Title</label>
                    <input wire:model.blur="title" id="title" class="mt-1 block w-full rounded-lg border-slate-300">
                    @error('title')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="maxScore" class="block text-sm font-medium text-slate-700">Maximum score</label>
                    <input wire:model.blur="maxScore" id="maxScore" type="number" step="0.01" min="0.01" class="mt-1 block w-full rounded-lg border-slate-300">
                    @error('maxScore')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="instructions" class="block text-sm font-medium text-slate-700">Instructions</label>
                <x-ui.rich-text-editor wire:model="instructions" id="instructions" class="mt-1" placeholder="Explain the task, expectations, and submission requirements..." />
                @error('instructions')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="attachmentFiles" class="block text-sm font-medium text-slate-700">Assignment attachments <span class="font-normal text-slate-400">(optional, up to 10 MB each)</span></label>
                <input wire:model="attachmentFiles" id="attachmentFiles" type="file" multiple class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                <p wire:loading.flex wire:target="attachmentFiles" class="mt-2 items-center gap-2 text-sm font-medium text-blue-700">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M20 12a8 8 0 1 1-2.34-5.66" stroke-linecap="round"/></svg>
                    Uploading attachment files...
                </p>
                <p class="mt-1 text-xs text-slate-500">PDF, Word, PowerPoint, spreadsheet, image, or ZIP files are supported.</p>
                @error('attachmentFiles.*')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror

                @if ($assignmentAttachments->isNotEmpty())
                    <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Current attachments</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($assignmentAttachments as $attachment)
                                <x-button type="button" wire:click="downloadAttachment({{ $attachment->id }})" variant="secondary" size="xs" target="downloadAttachment({{ $attachment->id }})" :loading="true">{{ $attachment->name }}</x-button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="opensAt" class="block text-sm font-medium text-slate-700">Opens at <span class="text-slate-400">(optional)</span></label>
                    <input wire:model.blur="opensAt" id="opensAt" type="datetime-local" class="mt-1 block w-full rounded-lg border-slate-300">
                    @error('opensAt')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="dueAt" class="block text-sm font-medium text-slate-700">Due at</label>
                    <input wire:model.blur="dueAt" id="dueAt" type="datetime-local" class="mt-1 block w-full rounded-lg border-slate-300">
                    @error('dueAt')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                    <select wire:model.blur="status" id="status" class="mt-1 block w-full rounded-lg border-slate-300">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="closed">Closed</option>
                    </select>
                    @error('status')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <label class="mt-7 flex items-center gap-3 rounded-lg bg-slate-50 px-3 py-3 text-sm text-slate-700">
                    <input wire:model="allowLateSubmission" type="checkbox" class="rounded border-slate-300 text-blue-700">
                    Allow late submissions
                </label>
            </div>

            <div class="flex justify-end gap-3">
                <x-button type="button" wire:click="closeModals" variant="secondary" icon="close" target="closeModals" :loading="true">Cancel</x-button>
                <x-button type="submit" variant="primary" icon="save" target="save" :loading="true">Save assignment</x-button>
            </div>
        </form>
    </x-modal>

    <x-modal :show="$showDeleteModal" title="Archive assignment?" close-action="closeModals" max-width="md">
        <p class="text-sm text-slate-600">The assignment will be archived and hidden from normal lists. Existing submissions are retained.</p>
        <x-slot:footer>
            <div class="flex justify-end gap-3">
                <x-button wire:click="closeModals" variant="secondary" icon="close" target="closeModals" :loading="true">Cancel</x-button>
                <x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Archive assignment</x-button>
            </div>
        </x-slot:footer>
    </x-modal>
</div>
