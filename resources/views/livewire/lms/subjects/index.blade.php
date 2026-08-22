<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Curriculum</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Subjects</h2>
            <p class="mt-1 text-sm text-slate-600">Manage the subjects available for class allocation and teaching.</p>
        </div>

        @can('create', App\Models\Subject::class)
            <x-button wire:click="create" icon="plus" target="create" :loading="true">Add subject</x-button>
        @endcan
    </div>

    @if ($showForm)
        <section class="overflow-hidden rounded-2xl border border-blue-200 bg-white shadow-sm" aria-labelledby="subject-form-title">
            <div class="flex items-start justify-between gap-4 border-b border-blue-100 bg-blue-50/70 px-5 py-4 sm:px-6">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Subject form</p>
                    <h3 id="subject-form-title" class="mt-1 text-lg font-bold text-slate-900">
                        {{ $editingId ? 'Edit subject' : 'New subject' }}
                    </h3>
                </div>

                <x-button wire:click="closeForm" variant="ghost" size="sm" target="closeForm" :loading="true">Cancel</x-button>
            </div>

            <form wire:submit="save" class="space-y-5 p-5 sm:p-6">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="subject-name" class="block text-sm font-medium text-slate-700">Subject name</label>
                        <input
                            wire:model.blur="name"
                            id="subject-name"
                            type="text"
                            placeholder="Mathematics"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="subject-code" class="block text-sm font-medium text-slate-700">Code <span class="text-slate-400">(optional)</span></label>
                        <input
                            wire:model.blur="code"
                            id="subject-code"
                            type="text"
                            placeholder="MATH"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                        >
                        @error('code')
                            <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="subject-description" class="block text-sm font-medium text-slate-700">Description <span class="text-slate-400">(optional)</span></label>
                    <textarea
                        wire:model.blur="description"
                        id="subject-description"
                        rows="4"
                        placeholder="A short outline of what this subject covers."
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                    ></textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                    <input wire:model="isActive" type="checkbox" class="rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                    Keep this subject active and available for class allocation
                </label>

                <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                    <x-button wire:click="closeForm" type="button" variant="ghost" target="closeForm" :loading="true">Cancel</x-button>
                    <x-button type="submit" icon="save" target="save" :loading="true">Save subject</x-button>
                </div>
            </form>
        </section>
    @endif

    @if ($showDeleteConfirmation)
        <section class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm" aria-labelledby="delete-subject-title">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 id="delete-subject-title" class="font-semibold text-rose-950">Delete this subject?</h3>
                    <p class="mt-1 text-sm text-rose-800">Subjects assigned to classes are retained for historical records. Archive them instead.</p>
                    @error('delete')
                        <p class="mt-2 text-sm font-medium text-rose-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex shrink-0 gap-3">
                    <x-button wire:click="cancelDelete" variant="ghost" target="cancelDelete" :loading="true">Cancel</x-button>
                    <x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Delete subject</x-button>
                </div>
            </div>
        </section>
    @endif

    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="relative w-full sm:max-w-xl">
            <label for="subject-search" class="sr-only">Search subjects</label>
            <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.5-3.5"></path>
            </svg>
            <input
                id="subject-search"
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by subject, code, description, or status"
                autocomplete="off"
                class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-24 text-sm shadow-sm transition focus:border-blue-700 focus:ring-blue-700"
            >

            <span wire:loading wire:target="search" class="absolute right-20 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">
                Searching…
            </span>

            @if (filled($search))
                <x-button
                    type="button"
                    wire:click="clearSearch"
                    variant="ghost"
                    size="sm"
                    class="absolute right-1.5 top-1/2 -translate-y-1/2 !px-2.5 !py-1.5"
                    target="clearSearch"
                    :loading="true"
                >
                    Clear
                </x-button>
            @endif
        </div>

        <p class="shrink-0 text-sm text-slate-500" aria-live="polite">
            <span wire:loading.remove wire:target="search">{{ $subjects->total() }} {{ \Illuminate\Support\Str::plural('subject', $subjects->total()) }}</span>
            <span wire:loading wire:target="search">Updating results…</span>
        </p>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($subjects as $subject)
            <article wire:key="subject-{{ $subject->id }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-slate-300 hover:shadow-md">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">{{ $subject->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $subject->code ?: 'No code' }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $subject->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                        {{ $subject->is_active ? 'Active' : 'Archived' }}
                    </span>
                </div>

                <p class="mt-4 min-h-10 text-sm text-slate-600">{{ $subject->description ?: 'No description provided.' }}</p>
                <p class="mt-3 text-xs text-slate-500">Assigned to {{ $subject->class_subjects_count }} {{ \Illuminate\Support\Str::plural('class', $subject->class_subjects_count) }}</p>

                <div class="mt-4 flex justify-end gap-2 border-t border-slate-100 pt-4">
                    @can('update', $subject)
                        <x-ui.icon-button wire:click="edit({{ $subject->id }})" icon="edit" label="Edit {{ $subject->name }}" target="edit({{ $subject->id }})" />
                    @endcan
                    @can('delete', $subject)
                        <x-ui.icon-button wire:click="confirmDelete({{ $subject->id }})" icon="trash" variant="danger" label="Delete {{ $subject->name }}" target="confirmDelete({{ $subject->id }})" />
                    @endcan
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center md:col-span-2 xl:col-span-3">
                <p class="font-medium text-slate-700">{{ $search ? 'No subjects match your search.' : 'No subjects yet.' }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $search ? 'Try a subject name, code, description, or status.' : 'Create the first subject to make it available for class allocation.' }}</p>
            </div>
        @endforelse
    </div>

    <x-pagination :paginator="$subjects" />
</div>
