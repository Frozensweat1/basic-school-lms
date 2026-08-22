<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Curriculum</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ $heading }}</h2>
            <p class="mt-1 text-sm text-slate-600">{{ $description }}</p>
        </div>
        <x-button wire:click="create" variant="primary" icon="plus" target="create" :loading="true">New lesson</x-button>
    </div>

    @php
        $filtersActive = filled($search) || filled($filterStatus) || filled($filterTopicId);
    @endphp
    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm xl:flex-row xl:items-center xl:justify-between">
        <div class="grid w-full gap-3 sm:grid-cols-2 xl:max-w-5xl xl:grid-cols-[minmax(16rem,1fr)_10rem_15rem]">
            <div class="relative sm:col-span-2 xl:col-span-1">
                <label for="lesson-search" class="sr-only">Search lessons</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
                <input
                    id="lesson-search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search lesson, topic, subject, or class"
                    autocomplete="off"
                    class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-20 text-sm shadow-sm transition focus:border-blue-700 focus:ring-blue-700"
                >
                <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching…</span>
            </div>

            <select wire:model.live="filterStatus" aria-label="Filter by lesson status" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All statuses</option>
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                <option value="archived">Archived</option>
            </select>

            <select wire:model.live="filterTopicId" aria-label="Filter by topic" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All topics</option>
                @foreach ($topics as $topic)
                    <option value="{{ $topic->id }}">{{ $topic->classSubject->schoolClass->name }} · {{ $topic->classSubject->subject->name }} — {{ $topic->title }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex shrink-0 items-center gap-3">
            @if ($filtersActive)
                <x-button wire:click="clearFilters" variant="ghost" size="sm" target="clearFilters" :loading="true">Clear filters</x-button>
            @endif
            <p class="whitespace-nowrap text-sm text-slate-500" aria-live="polite">
                <span wire:loading.remove wire:target="search,filterStatus,filterTopicId">{{ $lessons->total() }} {{ \Illuminate\Support\Str::plural('lesson', $lessons->total()) }}</span>
                <span wire:loading wire:target="search,filterStatus,filterTopicId">Updating…</span>
            </p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Lesson</th>
                        <th class="px-5 py-3">Class subject</th>
                        <th class="px-5 py-3">Teacher</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($lessons as $lesson)
                        <tr wire:key="lesson-{{ $lesson->id }}">
                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-900">{{ $lesson->sequence }}. {{ $lesson->title }}</p>
                                <p class="mt-1 max-w-md truncate text-xs text-slate-500">
                                    {{ $lesson->summary ?: 'No summary' }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-700">{{ $lesson->topic->classSubject->schoolClass->name }} ·
                                {{ $lesson->topic->classSubject->subject->name }}<span
                                    class="block text-xs text-slate-500">{{ $lesson->topic->title }}</span></td>
                            <td class="px-5 py-4 text-slate-700">{{ $lesson->teacher->first_name }}
                                {{ $lesson->teacher->last_name }}</td>
                            <td class="px-5 py-4"><span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $lesson->status === 'published' ? 'bg-emerald-100 text-emerald-700' : ($lesson->status === 'archived' ? 'bg-slate-100 text-slate-600' : 'bg-amber-100 text-amber-700') }}">{{ ucfirst($lesson->status) }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    @can('update', $lesson)
                                        <x-ui.icon-button wire:click="edit({{ $lesson->id }})" icon="edit"
                                            label="Edit lesson" target="edit({{ $lesson->id }})" />
                                        @endcan @can('delete', $lesson)
                                        <x-ui.icon-button wire:click="confirmDelete({{ $lesson->id }})" icon="trash"
                                            label="Archive lesson" variant="danger"
                                            target="confirmDelete({{ $lesson->id }})" />
                                    @endcan
                                </div>
                            </td>
                    </tr>@empty<tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <p class="font-medium text-slate-700">{{ $filtersActive ? 'No lessons match the current search or filters.' : 'No lessons yet.' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $filtersActive ? 'Clear a filter or try another search term.' : 'Create a topic first, then add its lesson plan.' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-modal :show="$showFormModal" :title="$editingId ? 'Edit lesson' : 'New lesson'" close-action="closeModals" max-width="xl">
        <form wire:submit="save" class="space-y-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label for="topicId" class="block text-sm font-medium text-slate-700">Topic</label><select
                        wire:model.blur="topicId" id="topicId" class="mt-1 block w-full rounded-lg border-slate-300">
                        <option value="">Choose a topic</option>
                        @foreach ($topics as $topic)
                            <option value="{{ $topic->id }}">{{ $topic->classSubject->schoolClass->name }} ·
                                {{ $topic->classSubject->subject->name }} — {{ $topic->title }}</option>
                        @endforeach
                    </select>
                    @error('topicId')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
                @if ($canChooseTeacher)
                    <div><label for="teacherId" class="block text-sm font-medium text-slate-700">Teacher <span
                                class="text-slate-400">(uses class subject teacher by default)</span></label><select
                            wire:model.blur="teacherId" id="teacherId"
                            class="mt-1 block w-full rounded-lg border-slate-300">
                            <option value="">Use class subject teacher</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->first_name }}
                                    {{ $teacher->last_name }}</option>
                            @endforeach
                        </select>
                        @error('teacherId')
                            <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label for="title" class="block text-sm font-medium text-slate-700">Lesson title</label><input
                        wire:model.blur="title" id="title" class="mt-1 block w-full rounded-lg border-slate-300">
                    @error('title')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
                <div><label for="sequence" class="block text-sm font-medium text-slate-700">Teaching order</label><input
                        wire:model.blur="sequence" id="sequence" type="number" min="0"
                        class="mt-1 block w-full rounded-lg border-slate-300">
                    @error('sequence')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div><label for="summary" class="block text-sm font-medium text-slate-700">Summary</label>
                <textarea wire:model.blur="summary" id="summary" rows="2" class="mt-1 block w-full rounded-lg border-slate-300"></textarea>
                @error('summary')
                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>
            <div><label for="objectives" class="block text-sm font-medium text-slate-700">Learning objectives <span
                        class="text-slate-400">(one per line)</span></label>
                <textarea wire:model.blur="objectives" id="objectives" rows="3"
                    class="mt-1 block w-full rounded-lg border-slate-300"></textarea>
                @error('objectives')
                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>
            <div><label for="content" class="block text-sm font-medium text-slate-700">Lesson
                    content</label><x-ui.rich-text-editor wire:model="content" id="content" class="mt-1"
                    placeholder="Write the lesson content, examples, and activities…" />
                @error('content')
                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>
            <div><label for="resourceFiles" class="block text-sm font-medium text-slate-700">Learning resources <span
                        class="font-normal text-slate-400">(optional, up to 25 MB each)</span></label><input
                    wire:model="resourceFiles" id="resourceFiles" type="file" multiple
                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                <p wire:loading.flex wire:target="resourceFiles" class="mt-2 items-center gap-2 text-sm font-medium text-blue-700">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M20 12a8 8 0 1 1-2.34-5.66" stroke-linecap="round"/></svg>
                    Uploading resource files…
                </p>
                @error('resourceFiles.*')
                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>
            <div class="grid gap-5 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                <div>
                    <label for="externalResourceUrl" class="block text-sm font-medium text-slate-700">External learning resource URL <span class="font-normal text-slate-400">(optional)</span></label>
                    <input wire:model.blur="externalResourceUrl" id="externalResourceUrl" type="url" placeholder="https://…" class="mt-1 block w-full rounded-lg border-slate-300 bg-white">
                    <p class="mt-1 text-xs text-slate-500">Add a website, video, cloud document, or other HTTPS/HTTP learning link.</p>
                    @error('externalResourceUrl')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="externalResourceTitle" class="block text-sm font-medium text-slate-700">Link title <span class="font-normal text-slate-400">(optional)</span></label>
                    <input wire:model.blur="externalResourceTitle" id="externalResourceTitle" placeholder="e.g. Fractions practice video" class="mt-1 block w-full rounded-lg border-slate-300 bg-white">
                    @error('externalResourceTitle')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div><label for="status" class="block text-sm font-medium text-slate-700">Status</label><select
                    wire:model.blur="status" id="status" class="mt-1 block w-full rounded-lg border-slate-300">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="archived">Archived</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex justify-end gap-3"><x-button type="button" wire:click="closeModals"
                    variant="secondary" icon="close" target="closeModals" :loading="true">Cancel</x-button><x-button
                    type="submit" variant="primary" icon="save" target="save" :loading="true">Save lesson</x-button></div>
        </form>
    </x-modal>
    <x-modal :show="$showDeleteModal" title="Archive lesson?" close-action="closeModals" max-width="md">
        <p class="text-sm text-slate-600">The lesson will be archived and hidden from normal lists. Existing resources
            are retained.</p><x-slot:footer>
            <div class="flex justify-end gap-3"><x-button wire:click="closeModals" variant="secondary"
                    icon="close" target="closeModals" :loading="true">Cancel</x-button><x-button wire:click="delete"
                    variant="danger" icon="trash" target="delete" :loading="true">Archive lesson</x-button></div>
        </x-slot:footer>
    </x-modal>
    <x-pagination :paginator="$lessons" />
</div>
