<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Website CMS</p>
            <h1 class="text-2xl font-bold">Gallery albums</h1>
        </div>
        <x-button wire:click="create" icon="plus" target="create" :loading="true">
            New album
        </x-button>
    </div>

    <!-- Albums Grid -->
    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        @forelse($albums as $album)
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                <!-- Album Thumbnail Grid -->
                <div class="grid grid-cols-3 gap-1 bg-slate-100 p-1">
                    @forelse($album->images->take(3) as $image)
                        <img
                            src="{{ $image->url ?? 'https://placehold.co/300x200/e0f2fe/1e3a8a?text=Gallery' }}"
                            class="aspect-[4/3] w-full object-cover"
                            alt="{{ $image->caption }}"
                            loading="lazy"
                        >
                    @empty
                        <div class="col-span-3 aspect-[4/3] flex items-center justify-center bg-slate-200">
                            <span class="text-xs text-slate-500">No images</span>
                        </div>
                    @endforelse
                </div>

                <!-- Album Info -->
                <div class="p-5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <h2 class="font-semibold text-slate-900">{{ $album->title }}</h2>
                            <p class="mt-1 text-sm text-slate-600 line-clamp-2">{{ $album->description }}</p>
                        </div>
                        <span class="flex-shrink-0 rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                            {{ $album->images->count() }}
                        </span>
                    </div>

                    <!-- Album Actions -->
                    <div class="mt-4 flex gap-3">
                        <x-button
                            wire:click="edit({{ $album->id }})"
                            variant="secondary"
                            size="sm"
                            icon="edit"
                            class="flex-1"
                            target="edit({{ $album->id }})"
                            :loading="true"
                        >
                            Edit
                        </x-button>
                        <x-button
                            wire:click="confirmDelete({{ $album->id }})"
                            variant="danger"
                            size="sm"
                            icon="trash"
                            class="flex-1"
                            target="confirmDelete({{ $album->id }})"
                            :loading="true"
                        >
                            Delete
                        </x-button>
                    </div>
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-2xl border-2 border-dashed border-slate-300 p-10 text-center text-slate-500">
                <p class="font-medium">No albums yet</p>
                <p class="mt-1 text-sm">Create your first gallery album to get started.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($albums->hasPages())
        <div class="mt-6 flex justify-center">
            {{ $albums->links('pagination::tailwind') }}
        </div>
    @endif

    <x-modal :show="$showFormModal" maxWidth="2xl">
    <form wire:submit="save" class="space-y-5">
        <!-- Modal Header -->
        <div class="border-b border-slate-200 pb-4">
            <h2 class="text-lg font-semibold text-slate-900">
                {{ $editingId ? 'Edit album' : 'Create new album' }}
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                {{ $editingId ? 'Update album details and upload new images.' : 'Create a new photo album with details and images.' }}
            </p>
        </div>

        <!-- Album Title -->
        <div>
            <label class="block text-sm font-medium text-slate-900 mb-2">Album title *</label>
            <input
                wire:model="title"
                type="text"
                placeholder="e.g., Sports Day 2026"
                class="w-full rounded-lg border border-slate-300 px-4 py-2 text-slate-900 placeholder-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                required
            >
            @error('title')
                <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>

        <!-- Album Description -->
        <div>
            <label class="block text-sm font-medium text-slate-900 mb-2">Description</label>
            <textarea
                wire:model="description"
                rows="3"
                placeholder="Add a brief description of this album..."
                class="w-full rounded-lg border border-slate-300 px-4 py-2 text-slate-900 placeholder-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
            ></textarea>
            @error('description')
                <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>

        <!-- Image Upload -->
        <div>
            <label class="block text-sm font-medium text-slate-900 mb-2">Photos</label>
            <div class="relative rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 p-6 text-center transition hover:border-slate-400 hover:bg-slate-100">
                <input
                    wire:model="images"
                    type="file"
                    multiple
                    accept="image/png,image/jpeg,image/webp,image/avif"
                    class="absolute inset-0 cursor-pointer opacity-0"
                />
                <div class="space-y-2">
                    <svg class="mx-auto h-10 w-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <p class="font-medium text-slate-900">Click to upload or drag & drop</p>
                    <p class="text-xs text-slate-600">PNG, JPG, WebP or AVIF up to 4MB each</p>
                </div>
            </div>
            @error('images.*')
                <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>

        <!-- Image Previews -->
        @if(count($imagePreviews) > 0)
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <p class="mb-3 text-sm font-medium text-slate-900">
                    {{ count($imagePreviews) }} image{{ count($imagePreviews) > 1 ? 's' : '' }} selected
                </p>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @foreach($imagePreviews as $index => $preview)
                        <div class="flex items-center justify-between rounded-lg bg-white p-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded bg-blue-100">
                                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="truncate text-sm font-medium text-slate-900">{{ $preview['preview'] }}</p>
                                    <p class="text-xs text-slate-600">{{ $preview['size'] }} KB</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                wire:click="removeImage({{ $index }})"
                                class="rounded-lg p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600 flex items-center justify-center"
                                title="Remove image"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Form Actions -->
        <div class="border-t border-slate-200 pt-4 flex justify-end gap-3">
            <x-button
                type="button"
                wire:click="closeModal"
                variant="secondary"
                target="closeModal"
                :loading="true"
            >
                Cancel
            </x-button>
            <x-button
                type="submit"
                icon="save"
                target="save"
                :loading="true"
            >
                {{ $editingId ? 'Update album' : 'Create album' }}
            </x-button>
        </div>
    </form>
</x-modal>

<x-modal :show="$showDeleteModal" maxWidth="md">
    <div class="space-y-4">
        <div class="flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mx-auto">
            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0-11a9 9 0 110 18 9 9 0 010-18zm0 16a7 7 0 110-14 7 7 0 010 14zm0-6a1 1 0 11-2 0 1 1 0 012 0zm0-2a1 1 0 10-2 0 1 1 0 002 0z" />
            </svg>
        </div>

        <div class="text-center">
            <h3 class="text-lg font-semibold text-slate-900">Delete album</h3>
            <p class="mt-2 text-sm text-slate-600">
                Are you sure you want to delete this album and all its images? This action cannot be undone.
            </p>
        </div>

        <div class="flex gap-3 pt-4">
            <x-button
                type="button"
                wire:click="closeDeleteModal"
                variant="secondary"
                class="flex-1"
                target="closeDeleteModal"
                :loading="true"
            >
                Cancel
            </x-button>
            <x-button
                type="button"
                wire:click="delete"
                variant="danger"
                icon="trash"
                class="flex-1"
                target="delete"
                :loading="true"
            >
                Delete album
            </x-button>
        </div>
    </div>
</x-modal>
</div>
