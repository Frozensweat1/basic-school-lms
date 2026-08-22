<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Curriculum</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Topics</h2>
            <p class="mt-1 text-sm text-slate-600">Organize each class subject into a clear teaching sequence.</p>
        </div>

        @can('create', App\Models\Topic::class)
            <x-button wire:click="create" variant="primary" icon="plus" target="create" :loading="true">New topic</x-button>
        @endcan
    </div>

    @php
        $filtersActive = filled($search) || filled($filterClassSubjectId) || filled($filterLessonState);
    @endphp
    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm xl:flex-row xl:items-center xl:justify-between">
        <div class="grid w-full gap-3 sm:grid-cols-2 xl:max-w-5xl xl:grid-cols-[minmax(16rem,1fr)_minmax(13rem,1fr)_10rem]">
            <div class="relative sm:col-span-2 xl:col-span-1">
                <label for="topic-search" class="sr-only">Search topics</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
                <input
                    id="topic-search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search topic, subject, or class"
                    autocomplete="off"
                    class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-20 text-sm shadow-sm transition focus:border-blue-700 focus:ring-blue-700"
                >
                <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching…</span>
            </div>

            <select wire:model.live="filterClassSubjectId" aria-label="Filter by class subject" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All class subjects</option>
                @foreach ($classSubjects as $classSubject)
                    <option value="{{ $classSubject->id }}">{{ $classSubject->schoolClass->name }} · {{ $classSubject->subject->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterLessonState" aria-label="Filter by lesson state" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All lesson states</option>
                <option value="with_lessons">With lessons</option>
                <option value="without_lessons">Without lessons</option>
            </select>
        </div>

        <div class="flex shrink-0 items-center gap-3">
            @if ($filtersActive)
                <x-button wire:click="clearFilters" variant="ghost" size="sm" target="clearFilters" :loading="true">Clear filters</x-button>
            @endif
            <p class="whitespace-nowrap text-sm text-slate-500" aria-live="polite">
                <span wire:loading.remove wire:target="search,filterClassSubjectId,filterLessonState">{{ $topics->total() }} {{ \Illuminate\Support\Str::plural('topic', $topics->total()) }}</span>
                <span wire:loading wire:target="search,filterClassSubjectId,filterLessonState">Updating…</span>
            </p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Topic</th>
                        <th class="px-5 py-3">Class subject</th>
                        <th class="px-5 py-3">Order</th>
                        <th class="px-5 py-3">Lessons</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($topics as $topic)
                        <tr wire:key="topic-{{ $topic->id }}" class="transition hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-900">{{ $topic->title }}</p>
                                @if ($topic->description)
                                    <p class="mt-1 max-w-lg truncate text-xs text-slate-500">{{ $topic->description }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-700">{{ $topic->classSubject->schoolClass->name }} · {{ $topic->classSubject->subject->name }}</td>
                            <td class="px-5 py-4 text-slate-700">{{ $topic->sequence }}</td>
                            <td class="px-5 py-4 text-slate-700">{{ $topic->lessons_count }}</td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    @can('update', $topic)
                                        <x-ui.icon-button wire:click="edit({{ $topic->id }})" icon="edit" label="Edit {{ $topic->title }}" target="edit({{ $topic->id }})" />
                                    @endcan
                                    @can('delete', $topic)
                                        <x-ui.icon-button wire:click="confirmDelete({{ $topic->id }})" icon="trash" label="Delete {{ $topic->title }}" variant="danger" target="confirmDelete({{ $topic->id }})" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <p class="font-medium text-slate-700">{{ $filtersActive ? 'No topics match the current search or filters.' : 'No topics yet.' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $filtersActive ? 'Clear a filter or try another search term.' : 'Create a class-subject allocation first, then add its teaching topics.' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-pagination :paginator="$topics" />

    <x-modal :show="$showFormModal" :title="$editingId ? 'Edit topic' : 'New topic'" close-action="closeModals">
        <form wire:submit="save" class="space-y-5">
            <div>
                <label for="classSubjectId" class="block text-sm font-medium text-slate-700">Class subject</label>
                <select wire:model.blur="classSubjectId" id="classSubjectId" class="mt-1 block w-full rounded-lg border-slate-300">
                    <option value="">Choose a class subject</option>
                    @foreach ($classSubjects as $classSubject)
                        <option value="{{ $classSubject->id }}">{{ $classSubject->schoolClass->name }} — {{ $classSubject->subject->name }}</option>
                    @endforeach
                </select>
                @error('classSubjectId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="title" class="block text-sm font-medium text-slate-700">Topic title</label>
                <input wire:model.blur="title" id="title" class="mt-1 block w-full rounded-lg border-slate-300">
                @error('title')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-slate-700">Description <span class="text-slate-400">(optional)</span></label>
                <textarea wire:model.blur="description" id="description" rows="4" class="mt-1 block w-full rounded-lg border-slate-300"></textarea>
                @error('description')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="sequence" class="block text-sm font-medium text-slate-700">Teaching order</label>
                <input wire:model.blur="sequence" id="sequence" type="number" min="0" class="mt-1 block w-full rounded-lg border-slate-300">
                @error('sequence')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end gap-3">
                <x-button type="button" wire:click="closeModals" variant="secondary" icon="close" target="closeModals" :loading="true">Cancel</x-button>
                <x-button type="submit" variant="primary" icon="save" target="save" :loading="true">Save topic</x-button>
            </div>
        </form>
    </x-modal>

    <x-modal :show="$showDeleteModal" title="Delete topic?" close-action="closeModals" max-width="md">
        <p class="text-sm text-slate-600">Deleting this topic also removes its lessons and resources. This cannot be undone.</p>
        <x-slot:footer>
            <div class="flex justify-end gap-3">
                <x-button wire:click="closeModals" variant="secondary" icon="close" target="closeModals" :loading="true">Cancel</x-button>
                <x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Delete topic</x-button>
            </div>
        </x-slot:footer>
    </x-modal>
</div>
