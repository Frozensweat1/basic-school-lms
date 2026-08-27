<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Website CMS</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">Events</h1>
            <p class="mt-1 text-sm text-slate-600">Create and publish school events for the public website.</p>
        </div>
        <x-button wire:click="create" icon="plus" target="create" :loading="true">New event</x-button>
    </div>

    <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_13rem]">
        <div class="relative">
            <label for="events-search" class="sr-only">Search events</label>
            <input id="events-search" wire:model.live.debounce.300ms="search" type="search" placeholder="Search events..." class="w-full rounded-xl border-slate-300 py-2.5 pl-4 pr-24 text-sm">
            <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-500">Searching...</span>
        </div>
        <div>
            <label for="events-status" class="sr-only">Filter events</label>
            <select id="events-status" wire:model.live="statusFilter" class="w-full rounded-xl border-slate-300 py-2.5 text-sm">
                <option value="all">All events</option>
                <option value="upcoming">Upcoming</option>
                <option value="past">Past</option>
                <option value="draft">Drafts</option>
            </select>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Event</th>
                        <th class="px-5 py-3 font-semibold">Date</th>
                        <th class="px-5 py-3 font-semibold">Visibility</th>
                        <th class="px-5 py-3 text-right font-semibold"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($events as $event)
                        <tr wire:key="website-event-{{ $event->id }}" class="hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <div class="flex min-w-72 items-center gap-3">
                                    @if ($event->featured_image_path)
                                        <img src="{{ Storage::disk('public')->url($event->featured_image_path) }}" alt="" class="h-12 w-16 shrink-0 rounded-lg object-cover" loading="lazy">
                                    @else
                                        <div class="flex h-12 w-16 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-xs font-bold text-blue-700">EVENT</div>
                                    @endif
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $event->title }}</p>
                                        <p class="mt-1 line-clamp-1 max-w-xl text-xs text-slate-500">{{ $event->location ?: 'Location to be announced' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                <p>{{ $event->starts_at?->format('d M Y, H:i') }}</p>
                                @if ($event->ends_at)
                                    <p class="mt-1 text-xs text-slate-500">Until {{ $event->ends_at->format('d M Y, H:i') }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $event->is_published ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">{{ $event->is_published ? 'Published' : 'Draft' }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <x-ui.icon-button wire:click="edit({{ $event->id }})" icon="edit" label="Edit {{ $event->title }}" target="edit({{ $event->id }})" />
                                    <x-ui.icon-button wire:click="confirmDelete({{ $event->id }})" icon="trash" variant="danger" label="Delete {{ $event->title }}" target="confirmDelete({{ $event->id }})" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-12 text-center"><p class="font-semibold text-slate-700">{{ $search || $statusFilter !== 'all' ? 'No events match the current filters.' : 'No events yet.' }}</p><p class="mt-1 text-sm text-slate-500">{{ $search || $statusFilter !== 'all' ? 'Try a different search or status.' : 'Create the first event for your school website.' }}</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-pagination :paginator="$events" />

    <x-modal :show="$showFormModal" :title="$editingId ? 'Edit event' : 'Create event'" close-action="closeModals" maxWidth="2xl">
        <form wire:submit="save" class="space-y-5">
            <div><label for="event-title" class="block text-sm font-medium text-slate-700">Title</label><input id="event-title" wire:model.blur="title" class="mt-1 w-full rounded-lg border-slate-300" placeholder="Event title">@error('title')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
            <div><label for="event-description" class="block text-sm font-medium text-slate-700">Description</label><textarea id="event-description" wire:model.blur="description" rows="4" class="mt-1 w-full rounded-lg border-slate-300" placeholder="Describe the event"></textarea>@error('description')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="event-starts-at" class="block text-sm font-medium text-slate-700">Starts</label><input id="event-starts-at" wire:model.blur="startsAt" type="datetime-local" class="mt-1 w-full rounded-lg border-slate-300">@error('startsAt')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                <div><label for="event-ends-at" class="block text-sm font-medium text-slate-700">Ends <span class="font-normal text-slate-500">(optional)</span></label><input id="event-ends-at" wire:model.blur="endsAt" type="datetime-local" class="mt-1 w-full rounded-lg border-slate-300">@error('endsAt')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
            </div>
            <div><label for="event-location" class="block text-sm font-medium text-slate-700">Location</label><input id="event-location" wire:model.blur="location" class="mt-1 w-full rounded-lg border-slate-300" placeholder="Venue or online location">@error('location')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
            <div class="grid gap-4 sm:grid-cols-[9rem_1fr] sm:items-start">
                <div class="aspect-[4/3] overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200">
                    @if ($featuredImage)<img src="{{ $featuredImage->temporaryUrl() }}" alt="New event preview" class="h-full w-full object-cover">@elseif ($currentFeaturedImagePath)<img src="{{ Storage::disk('public')->url($currentFeaturedImagePath) }}" alt="Current event image" class="h-full w-full object-cover">@else<div class="flex h-full items-center justify-center text-xs text-slate-500">No image</div>@endif
                </div>
                <div><label for="event-featured-image" class="block text-sm font-medium text-slate-700">Featured image</label><input id="event-featured-image" wire:model="featuredImage" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full rounded-lg border border-slate-300 text-sm file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:font-semibold"><p class="mt-1 text-xs text-slate-500">JPG/PNG/WebP, up to 4 MB.</p><p wire:loading wire:target="featuredImage" class="mt-1 text-xs font-semibold text-blue-700">Preparing preview...</p>@error('featuredImage')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror @if ($currentFeaturedImagePath)<label class="mt-2 inline-flex items-center gap-2 text-sm text-slate-600"><input wire:model="removeFeaturedImage" type="checkbox" class="rounded border-slate-300 text-rose-600"> Remove current image</label>@endif</div>
            </div>
            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700"><input wire:model="isPublished" type="checkbox" class="rounded border-slate-300 text-blue-600"> Publish on the public website</label>
            <div class="flex justify-end gap-3 border-t border-slate-200 pt-5"><x-button type="button" wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button><x-button type="submit" icon="save" target="save" :loading="true">Save event</x-button></div>
        </form>
    </x-modal>

    <x-modal :show="$showDeleteModal" title="Delete event?" close-action="closeModals" maxWidth="sm">
        <div class="space-y-5"><p class="text-sm leading-6 text-slate-600">This permanently removes the event and its featured image from the public website.</p><div class="flex justify-end gap-3"><x-button type="button" wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button><x-button type="button" wire:click="delete" icon="trash" variant="danger" target="delete" :loading="true">Delete event</x-button></div></div>
    </x-modal>
</div>
