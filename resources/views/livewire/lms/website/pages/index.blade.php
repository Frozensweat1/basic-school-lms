<div class="space-y-6">
    @php
        $pageNames = [
            'home' => 'Home',
            'about' => 'About us',
            'academics' => 'Academics',
            'admissions' => 'Admissions',
            'teachers' => 'Teachers',
            'news' => 'News',
            'events' => 'Events',
            'gallery' => 'Gallery',
            'contact' => 'Contact',
        ];
    @endphp

    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Website CMS</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">Public pages</h1>
            <p class="mt-1 max-w-2xl text-sm text-slate-600">Manage each page hero and the structured content used by the public website.</p>
        </div>
        <div class="rounded-xl bg-blue-50 px-4 py-2 text-sm font-medium text-blue-800">
            {{ $pages->total() }} {{ \Illuminate\Support\Str::plural('page', $pages->total()) }} configured
        </div>
    </div>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($pages as $page)
            <article wire:key="website-page-{{ $page->id }}" class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="relative h-32 overflow-hidden bg-gradient-to-br from-blue-950 via-blue-800 to-cyan-700">
                    @if ($page->hero_image_path)
                        <img src="{{ Storage::disk('public')->url($page->hero_image_path) }}" alt="" class="h-full w-full object-cover opacity-70 transition duration-300 group-hover:scale-105" loading="lazy">
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/75 to-transparent"></div>
                    <span class="absolute bottom-3 left-4 rounded-full bg-white/90 px-3 py-1 text-xs font-bold uppercase tracking-[0.16em] text-blue-950">
                        {{ $pageNames[$page->slug] ?? str($page->slug)->headline() }}
                    </span>
                </div>
                <div class="p-5">
                    <h2 class="line-clamp-2 font-semibold text-slate-900">{{ $page->hero_title ?: 'Hero title not set' }}</h2>
                    <p class="mt-2 line-clamp-2 min-h-10 text-sm leading-5 text-slate-600">{{ $page->hero_subtitle ?: 'Add a concise description for this page.' }}</p>
                    <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                        <span class="text-xs text-slate-500">Updated {{ $page->updated_at?->diffForHumans() }}</span>
                        <x-button wire:click="edit({{ $page->id }})" size="sm" variant="ghost" icon="edit" target="edit({{ $page->id }})" :loading="true">Edit page</x-button>
                    </div>
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
                <p class="font-semibold text-slate-800">No public pages are configured.</p>
                <p class="mt-1 text-sm text-slate-500">Run the website content seeder to create the initial pages.</p>
            </div>
        @endforelse
    </div>

    <x-pagination :paginator="$pages" />

    <x-modal :show="$showFormModal" :title="$slug ? 'Edit '.($pageNames[$slug] ?? str($slug)->headline()) : 'Edit page'" close-action="closeModal" maxWidth="2xl">
        <form wire:submit="save" class="space-y-6">
            <section class="space-y-4" aria-labelledby="page-hero-heading">
                <div>
                    <h3 id="page-hero-heading" class="font-semibold text-slate-900">Hero section</h3>
                    <p class="mt-1 text-sm text-slate-500">This is the first content visitors see on the page.</p>
                </div>

                <div>
                    <label for="page-hero-title" class="block text-sm font-medium text-slate-700">Title</label>
                    <input id="page-hero-title" wire:model.blur="heroTitle" type="text" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                    @error('heroTitle') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="page-hero-subtitle" class="block text-sm font-medium text-slate-700">Supporting text</label>
                    <textarea id="page-hero-subtitle" wire:model.blur="heroSubtitle" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600"></textarea>
                    @error('heroSubtitle') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-[10rem_1fr] sm:items-center">
                    <div class="aspect-[4/3] overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200">
                        @if ($heroImage)
                            <img src="{{ $heroImage->temporaryUrl() }}" alt="New hero preview" class="h-full w-full object-cover">
                        @elseif ($currentHeroImagePath)
                            <img src="{{ Storage::disk('public')->url($currentHeroImagePath) }}" alt="Current hero" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full items-center justify-center px-4 text-center text-xs text-slate-500">No hero image</div>
                        @endif
                    </div>
                    <div>
                        <label for="page-hero-image" class="block text-sm font-medium text-slate-700">Hero image</label>
                        <input id="page-hero-image" wire:model="heroImage" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-600 file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:font-semibold file:text-slate-700">
                        <p class="mt-1 text-xs text-slate-500">JPG, PNG, or WebP up to 4 MB. Use a wide landscape image.</p>
                        <p wire:loading wire:target="heroImage" class="mt-1 text-xs font-medium text-blue-700">Preparing preview...</p>
                        @error('heroImage') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        @if ($currentHeroImagePath)
                            <label class="mt-3 inline-flex items-center gap-2 text-sm text-slate-600">
                                <input wire:model="removeHeroImage" type="checkbox" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                Remove the current image
                            </label>
                        @endif
                    </div>
                </div>
            </section>

            <section class="border-t border-slate-200 pt-6">
                <label for="page-body" class="block text-sm font-semibold text-slate-900">Page introduction</label>
                <p class="mt-1 text-sm text-slate-500">Optional rich content shown below the hero.</p>
                <div class="mt-3">
                    <x-ui.rich-text-editor wire:model="contentBody" id="page-body" placeholder="Write the page introduction..." min-height="10rem" />
                </div>
                @error('contentBody') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
            </section>

            @if ($slug === 'home')
                @include('livewire.lms.website.pages._repeater', ['collection' => 'stats', 'title' => 'Homepage statistics', 'addLabel' => 'Add statistic', 'rows' => $stats])
                @include('livewire.lms.website.pages._repeater', ['collection' => 'programs', 'title' => 'Featured programmes', 'addLabel' => 'Add programme', 'rows' => $programs])
            @elseif ($slug === 'about')
                <section class="grid gap-4 border-t border-slate-200 pt-6 sm:grid-cols-2">
                    <div><label for="mission" class="block text-sm font-semibold text-slate-900">Mission</label><textarea id="mission" wire:model.blur="mission" rows="4" class="mt-2 w-full rounded-lg border-slate-300"></textarea>@error('mission')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    <div><label for="vision" class="block text-sm font-semibold text-slate-900">Vision</label><textarea id="vision" wire:model.blur="vision" rows="4" class="mt-2 w-full rounded-lg border-slate-300"></textarea>@error('vision')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                </section>
                @include('livewire.lms.website.pages._repeater', ['collection' => 'values', 'title' => 'School values', 'addLabel' => 'Add value', 'rows' => $values])
            @elseif ($slug === 'academics')
                @include('livewire.lms.website.pages._repeater', ['collection' => 'programs', 'title' => 'Academic programmes', 'addLabel' => 'Add programme', 'rows' => $programs])
                @include('livewire.lms.website.pages._repeater', ['collection' => 'approach', 'title' => 'Learning approach', 'addLabel' => 'Add approach', 'rows' => $approach])
            @elseif ($slug === 'admissions')
                @include('livewire.lms.website.pages._repeater', ['collection' => 'steps', 'title' => 'Admission steps', 'addLabel' => 'Add step', 'rows' => $steps])
                @include('livewire.lms.website.pages._repeater', ['collection' => 'requirements', 'title' => 'Application requirements', 'addLabel' => 'Add requirement', 'rows' => $requirements])
            @endif

            <div class="flex justify-end gap-3 border-t border-slate-200 pt-5">
                <x-button type="button" wire:click="closeModal" variant="ghost" target="closeModal" :loading="true">Cancel</x-button>
                <x-button type="submit" icon="save" target="save" :loading="true">Save page</x-button>
            </div>
        </form>
    </x-modal>
</div>
