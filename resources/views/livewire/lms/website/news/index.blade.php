<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Website CMS</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">News</h1>
            <p class="mt-1 text-sm text-slate-600">Write, schedule, and publish stories from across the school community.</p>
        </div>
        <x-button wire:click="create" icon="plus" target="create" :loading="true">New post</x-button>
    </div>

    <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_13rem]">
        <div class="relative">
            <label for="news-search" class="sr-only">Search news</label>
            <input id="news-search" wire:model.live.debounce.300ms="search" type="search" placeholder="Search titles and excerpts..." class="w-full rounded-xl border-slate-300 py-2.5 pl-4 pr-24 text-sm">
            <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-500">Searching...</span>
        </div>
        <div>
            <label for="news-status" class="sr-only">Filter by status</label>
            <select id="news-status" wire:model.live="statusFilter" class="w-full rounded-xl border-slate-300 py-2.5 text-sm">
                <option value="all">All statuses</option><option value="published">Published</option><option value="scheduled">Scheduled</option><option value="draft">Drafts</option>
            </select>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600"><tr><th class="px-5 py-3 font-semibold">Story</th><th class="px-5 py-3 font-semibold">Visibility</th><th class="px-5 py-3 font-semibold">Updated</th><th class="px-5 py-3 text-right font-semibold"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($posts as $post)
                        @php
                            $status = !$post->published_at ? ['Draft', 'bg-slate-100 text-slate-700'] : ($post->published_at->isFuture() ? ['Scheduled', 'bg-amber-100 text-amber-800'] : ['Published', 'bg-emerald-100 text-emerald-700']);
                        @endphp
                        <tr wire:key="news-post-{{ $post->id }}" class="hover:bg-slate-50/70">
                            <td class="px-5 py-4"><div class="flex min-w-72 items-center gap-3">@if($post->featured_image_path)<img src="{{ Storage::disk('public')->url($post->featured_image_path) }}" alt="" class="h-12 w-16 shrink-0 rounded-lg object-cover" loading="lazy">@else<div class="flex h-12 w-16 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-xs font-bold text-blue-700">NEWS</div>@endif<div><p class="font-semibold text-slate-900">{{ $post->title }}</p><p class="mt-1 line-clamp-1 max-w-xl text-xs text-slate-500">{{ $post->excerpt ?: str(strip_tags($post->body))->limit(100) }}</p></div></div></td>
                            <td class="whitespace-nowrap px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $status[1] }}">{{ $status[0] }}</span>@if($post->published_at)<p class="mt-1 text-xs text-slate-500">{{ $post->published_at->format('d M Y, H:i') }}</p>@endif</td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $post->updated_at?->diffForHumans() }}</td>
                            <td class="whitespace-nowrap px-5 py-4"><div class="flex justify-end gap-2"><x-ui.icon-button wire:click="edit({{ $post->id }})" icon="edit" label="Edit {{ $post->title }}" target="edit({{ $post->id }})" /><x-ui.icon-button wire:click="confirmDelete({{ $post->id }})" icon="trash" variant="danger" label="Delete {{ $post->title }}" target="confirmDelete({{ $post->id }})" /></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-12 text-center"><p class="font-semibold text-slate-700">{{ $search || $statusFilter !== 'all' ? 'No news matches the current filters.' : 'No news posts yet.' }}</p><p class="mt-1 text-sm text-slate-500">{{ $search || $statusFilter !== 'all' ? 'Try a different search or status.' : 'Publish the first story from your school.' }}</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-pagination :paginator="$posts" />

    <x-modal :show="$showFormModal" :title="$editingId ? 'Edit news post' : 'Create news post'" close-action="closeModals" maxWidth="2xl">
        <form wire:submit="save" class="space-y-5">
            <div><label for="news-title" class="block text-sm font-medium text-slate-700">Title</label><input id="news-title" wire:model.blur="title" class="mt-1 w-full rounded-lg border-slate-300" placeholder="A clear, engaging headline">@error('title')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
            <div><label for="news-excerpt" class="block text-sm font-medium text-slate-700">Short excerpt</label><textarea id="news-excerpt" wire:model.blur="excerpt" rows="2" class="mt-1 w-full rounded-lg border-slate-300" placeholder="A one or two sentence summary for news cards"></textarea><div class="mt-1 flex justify-between text-xs text-slate-500"><span>Shown in listings and search previews.</span><span>{{ mb_strlen($excerpt) }}/500</span></div>@error('excerpt')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
            <div><label class="block text-sm font-medium text-slate-700">Story content</label><div class="mt-1"><x-ui.rich-text-editor wire:model="body" placeholder="Write the full story..." min-height="14rem" /></div>@error('body')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
            <div class="grid gap-4 sm:grid-cols-[9rem_1fr] sm:items-start">
                <div class="aspect-[4/3] overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200">@if($featuredImage)<img src="{{ $featuredImage->temporaryUrl() }}" alt="New feature preview" class="h-full w-full object-cover">@elseif($currentFeaturedImagePath)<img src="{{ Storage::disk('public')->url($currentFeaturedImagePath) }}" alt="Current feature" class="h-full w-full object-cover">@else<div class="flex h-full items-center justify-center text-xs text-slate-500">No image</div>@endif</div>
                <div><label for="news-featured-image" class="block text-sm font-medium text-slate-700">Featured image</label><input id="news-featured-image" wire:model="featuredImage" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full rounded-lg border border-slate-300 text-sm file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:font-semibold"><p class="mt-1 text-xs text-slate-500">Landscape image, JPG/PNG/WebP, up to 4 MB.</p><p wire:loading wire:target="featuredImage" class="mt-1 text-xs font-semibold text-blue-700">Preparing preview...</p>@error('featuredImage')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror @if($currentFeaturedImagePath)<label class="mt-2 inline-flex items-center gap-2 text-sm text-slate-600"><input wire:model="removeFeaturedImage" type="checkbox" class="rounded border-slate-300 text-rose-600"> Remove current image</label>@endif</div>
            </div>
            <div><label for="news-publish-at" class="block text-sm font-medium text-slate-700">Publish date and time</label><input id="news-publish-at" wire:model.blur="publishedAt" type="datetime-local" class="mt-1 w-full rounded-lg border-slate-300 sm:max-w-sm"><p class="mt-1 text-xs text-slate-500">Clear this field to keep the story as a draft, or choose a future date to schedule it.</p>@error('publishedAt')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
            <div class="flex justify-end gap-3 border-t border-slate-200 pt-5"><x-button type="button" wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button><x-button type="submit" icon="save" target="save" :loading="true">Save post</x-button></div>
        </form>
    </x-modal>

    <x-modal :show="$showDeleteModal" title="Delete news post?" close-action="closeModals" maxWidth="sm">
        <div class="space-y-5"><p class="text-sm leading-6 text-slate-600">This permanently removes the story and its featured image from the public website.</p><div class="flex justify-end gap-3"><x-button type="button" wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button><x-button type="button" wire:click="delete" icon="trash" variant="danger" target="delete" :loading="true">Delete post</x-button></div></div>
    </x-modal>
</div>
