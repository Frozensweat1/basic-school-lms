<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Academic setup</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Streams</h2>
            <p class="mt-1 text-sm text-slate-600">Create the class sections used to organise learners, such as Blue, Gold, or Main.</p>
        </div>

        @can('create', App\Models\Stream::class)
            <x-button wire:click="create" icon="plus" target="create" :loading="true">Add stream</x-button>
        @endcan
    </div>

    @if ($showForm)
        <section class="overflow-hidden rounded-2xl border border-blue-200 bg-white shadow-sm" aria-labelledby="stream-form-title">
            <div class="flex items-start justify-between gap-4 border-b border-blue-100 bg-blue-50/70 px-5 py-4 sm:px-6">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Stream form</p>
                    <h3 id="stream-form-title" class="mt-1 text-lg font-bold text-slate-900">
                        {{ $editingId ? 'Edit stream' : 'New stream' }}
                    </h3>
                </div>

                <x-button wire:click="closeForm" variant="ghost" size="sm" target="closeForm" :loading="true">Cancel</x-button>
            </div>

            <form wire:submit="save" class="space-y-5 p-5 sm:p-6">
                <div>
                    <label for="stream-name" class="block text-sm font-medium text-slate-700">Stream name</label>
                    <input
                        wire:model.blur="name"
                        id="stream-name"
                        type="text"
                        placeholder="Blue"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                    >
                    @error('name')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="stream-description" class="block text-sm font-medium text-slate-700">Description <span class="text-slate-400">(optional)</span></label>
                    <textarea
                        wire:model.blur="description"
                        id="stream-description"
                        rows="3"
                        placeholder="A short note about the learners or grouping in this stream."
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                    ></textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                    <input wire:model="isActive" type="checkbox" class="rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                    Keep this stream active and available for class setup
                </label>

                <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                    <x-button wire:click="closeForm" type="button" variant="ghost" target="closeForm" :loading="true">Cancel</x-button>
                    <x-button type="submit" icon="save" target="save" :loading="true">Save stream</x-button>
                </div>
            </form>
        </section>
    @endif

    @if ($showDeleteConfirmation)
        <section class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm" aria-labelledby="delete-stream-title">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 id="delete-stream-title" class="font-semibold text-rose-950">Delete this stream?</h3>
                    <p class="mt-1 text-sm text-rose-800">Streams assigned to classes are retained for historical records. Archive them instead.</p>
                    @error('delete')
                        <p class="mt-2 text-sm font-medium text-rose-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex shrink-0 gap-3">
                    <x-button wire:click="cancelDelete" variant="ghost" target="cancelDelete" :loading="true">Cancel</x-button>
                    <x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Delete stream</x-button>
                </div>
            </div>
        </section>
    @endif

    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="relative w-full sm:max-w-xl">
            <label for="stream-search" class="sr-only">Search streams</label>
            <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.5-3.5"></path>
            </svg>
            <input
                id="stream-search"
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by stream name, description, or status"
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
            <span wire:loading.remove wire:target="search">{{ $streams->total() }} {{ \Illuminate\Support\Str::plural('stream', $streams->total()) }}</span>
            <span wire:loading wire:target="search">Updating results…</span>
        </p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Stream</th>
                        <th class="px-5 py-3 font-semibold">Description</th>
                        <th class="px-5 py-3 font-semibold">Classes</th>
                        <th class="px-5 py-3 font-semibold">Status</th>
                        <th class="px-5 py-3 text-right font-semibold"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($streams as $stream)
                        <tr wire:key="stream-{{ $stream->id }}" class="transition hover:bg-slate-50/70">
                            <td class="px-5 py-4 font-medium text-slate-900">{{ $stream->name }}</td>
                            <td class="max-w-lg px-5 py-4 text-slate-600">
                                <span class="line-clamp-2">{{ $stream->description ?: '—' }}</span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $stream->classes_count }}</td>
                            <td class="px-5 py-4">
                                <x-badge :variant="$stream->is_active ? 'success' : 'default'">
                                    {{ $stream->is_active ? 'Active' : 'Archived' }}
                                </x-badge>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    @can('update', $stream)
                                        <x-ui.icon-button wire:click="edit({{ $stream->id }})" icon="edit" label="Edit {{ $stream->name }}" target="edit({{ $stream->id }})" />
                                    @endcan
                                    @can('delete', $stream)
                                        <x-ui.icon-button wire:click="confirmDelete({{ $stream->id }})" icon="trash" variant="danger" label="Delete {{ $stream->name }}" target="confirmDelete({{ $stream->id }})" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <p class="font-medium text-slate-700">{{ $search ? 'No streams match your search.' : 'No streams yet.' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $search ? 'Try a stream name, description, or status.' : 'Create the first stream to use it when setting up classes.' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-pagination :paginator="$streams" />
</div>
