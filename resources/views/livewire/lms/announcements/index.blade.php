<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div><p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Communication</p><h2 class="mt-2 text-2xl font-bold text-slate-900">Announcements</h2><p class="mt-1 max-w-2xl text-sm text-slate-600">Create targeted notices, schedule publication, notify recipients, and share supporting files.</p></div>
        <div class="flex justify-end"><x-button wire:click="create" icon="plus" target="create" :loading="true">New announcement</x-button></div>
    </div>

    <div class="grid grid-cols-4 gap-4">
        <article class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm"><p class="text-sm font-medium text-blue-800">Announcements</p><p class="mt-2 text-3xl font-bold text-blue-900">{{ $totalCount }}</p><p class="mt-1 text-xs text-blue-700">Available in your management scope</p></article>
        <article class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm"><p class="text-sm font-medium text-emerald-800">Published</p><p class="mt-2 text-3xl font-bold text-emerald-900">{{ $publishedCount }}</p><p class="mt-1 text-xs text-emerald-700">Currently visible to recipients</p></article>
        <article class="rounded-2xl border border-violet-100 bg-violet-50 p-5 shadow-sm"><p class="text-sm font-medium text-violet-800">Scheduled</p><p class="mt-2 text-3xl font-bold text-violet-900">{{ $scheduledCount }}</p><p class="mt-1 text-xs text-violet-700">Awaiting automatic publication</p></article>
        <article class="rounded-2xl border border-amber-100 bg-amber-50 p-5 shadow-sm"><p class="text-sm font-medium text-amber-800">Drafts</p><p class="mt-2 text-3xl font-bold text-amber-900">{{ $draftCount }}</p><p class="mt-1 text-xs text-amber-700">Not yet visible or notified</p></article>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_200px_auto] lg:items-center">
        <div class="relative"><label for="announcement-search" class="sr-only">Search announcements</label><svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg><input id="announcement-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search title, content, or author" class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-24 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700"><span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching...</span></div>
        <select wire:model.live="filterAudience" aria-label="Filter by audience" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700"><option value="">All audiences</option>@foreach ($allowedAudiences as $audienceOption)<option value="{{ $audienceOption }}">{{ match ($audienceOption) { 'school' => 'School-wide', 'teachers' => 'Teachers', 'class' => 'Class', 'subject' => 'Subject', default => ucfirst($audienceOption) } }}</option>@endforeach</select>
        <select wire:model.live="filterState" aria-label="Filter by publication state" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700"><option value="">All states</option><option value="published">Published</option><option value="scheduled">Scheduled</option><option value="draft">Draft</option><option value="expired">Expired</option></select>
        @if (filled($search) || filled($filterAudience) || filled($filterState))<x-button wire:click="clearFilters" variant="ghost" size="sm" target="clearFilters" :loading="true">Clear filters</x-button>@endif
    </div></section>

    <div class="grid gap-4">
        @forelse ($announcements as $announcement)<x-ui.announcement-card wire:key="managed-announcement-{{ $announcement->id }}" :announcement="$announcement" :manageable="true" />@empty<div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-500">{{ filled($search) || filled($filterAudience) || filled($filterState) ? 'No announcements match the current search and filters.' : 'No announcements have been created yet.' }}</div>@endforelse
    </div>
    @if ($announcements->hasPages())<div class="rounded-2xl border border-slate-200 bg-white px-5 py-4">{{ $announcements->links() }}</div>@endif

    <x-modal :show="$showFormModal" :title="$editingId ? 'Edit announcement' : 'Create announcement'" close-action="closeModals" max-width="2xl">
        <form wire:submit="save" class="space-y-5">
                    <p class="text-sm text-slate-500">Choose who should receive this notice and when it becomes visible.</p>
                    <div><label for="announcement-title" class="block text-sm font-semibold text-slate-700">Title</label><input id="announcement-title" wire:model.blur="title" maxlength="255" placeholder="A clear announcement title" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">@error('title')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label for="announcement-audience" class="block text-sm font-semibold text-slate-700">Audience</label><select id="announcement-audience" wire:model.live="audience" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">@foreach ($allowedAudiences as $audienceOption)<option value="{{ $audienceOption }}">{{ match ($audienceOption) { 'school' => 'Entire school', 'teachers' => 'All teachers', 'class' => 'Selected class', 'subject' => 'Selected subject', default => ucfirst($audienceOption) } }}</option>@endforeach</select>@error('audience')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        @if ($audience === 'class')<div><label for="announcement-class" class="block text-sm font-semibold text-slate-700">Target class</label><select id="announcement-class" wire:model.blur="classId" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700"><option value="">Choose a class</option>@foreach ($classes as $class)<option value="{{ $class->id }}">{{ $class->name }} - {{ $class->academicYear?->name }}</option>@endforeach</select>@error('classId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>@endif
                        @if ($audience === 'subject')<div><label for="announcement-subject" class="block text-sm font-semibold text-slate-700">Target subject</label><select id="announcement-subject" wire:model.blur="subjectId" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700"><option value="">Choose a subject</option>@foreach ($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select>@error('subjectId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>@endif
                    </div>
                    <div><label class="mb-1 block text-sm font-semibold text-slate-700">Announcement content</label><x-ui.rich-text-editor wire:model="content" placeholder="Write the announcement..." min-height="14rem" />@error('content')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    <div class="grid gap-4 lg:grid-cols-3">
                        <div><label for="publication-mode" class="block text-sm font-semibold text-slate-700">Publication</label><select id="publication-mode" wire:model.live="publicationMode" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700"><option value="publish_now">Publish now</option><option value="schedule">Schedule publication</option><option value="draft">Save as draft</option></select>@error('publicationMode')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        @if ($publicationMode === 'schedule')<div><label for="announcement-published-at" class="block text-sm font-semibold text-slate-700">Publish at</label><input id="announcement-published-at" type="datetime-local" wire:model.blur="publishedAt" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">@error('publishedAt')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>@endif
                        <div><label for="announcement-expires-at" class="block text-sm font-semibold text-slate-700">Expires at <span class="font-normal text-slate-400">(optional)</span></label><input id="announcement-expires-at" type="datetime-local" wire:model.blur="expiresAt" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">@error('expiresAt')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    </div>
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4"><label for="announcement-files" class="block text-sm font-semibold text-slate-700">Attachments <span class="font-normal text-slate-400">(up to five files, 10 MB each)</span></label><input id="announcement-files" type="file" wire:model="attachmentFiles" multiple class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-blue-100 file:px-3 file:py-2 file:font-semibold file:text-blue-800 hover:file:bg-blue-200"><p wire:loading wire:target="attachmentFiles" class="mt-2 text-xs font-semibold text-blue-700">Uploading attachments...</p>@error('attachmentFiles')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror @error('attachmentFiles.*')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        @if ($existingAttachments->isNotEmpty())<div class="mt-4 border-t border-slate-200 pt-3"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Current attachments</p><div class="mt-2 flex flex-wrap gap-2">@foreach ($existingAttachments as $attachment)<span class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">{{ $attachment->name }}<button type="button" wire:click="removeAttachment({{ $attachment->id }})" wire:loading.attr="disabled" wire:target="removeAttachment({{ $attachment->id }})" class="cursor-pointer text-rose-600 hover:text-rose-800" aria-label="Remove {{ $attachment->name }}">&times;</button></span>@endforeach</div></div>@endif
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-5"><x-button type="button" wire:click="closeModals" variant="secondary" target="closeModals" :loading="true">Cancel</x-button><x-button type="submit" icon="save" target="save" :loading="true">{{ $editingId ? 'Save changes' : ($publicationMode === 'draft' ? 'Save draft' : ($publicationMode === 'schedule' ? 'Schedule announcement' : 'Publish announcement')) }}</x-button></div>
        </form>
    </x-modal>

    <x-modal :show="$showPublishModal" title="Publish announcement now?" close-action="closeModals" max-width="md">
        <p class="text-sm text-slate-600">It will become visible immediately and notifications will be queued for the selected audience.</p>
        <x-slot:footer>
            <div class="flex justify-end gap-3">
                <x-button wire:click="closeModals" variant="secondary" icon="close" target="closeModals" :loading="true">Cancel</x-button>
                <x-button wire:click="publishNow" variant="success" target="publishNow" :loading="true">Publish now</x-button>
            </div>
        </x-slot:footer>
    </x-modal>

    <x-modal :show="$showDeleteModal" title="Archive announcement?" close-action="closeModals" max-width="md">
        <p class="text-sm text-slate-600">The announcement will no longer appear in management or recipient feeds.</p>
        <x-slot:footer>
            <div class="flex justify-end gap-3">
                <x-button wire:click="closeModals" variant="secondary" icon="close" target="closeModals" :loading="true">Cancel</x-button>
                <x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Archive</x-button>
            </div>
        </x-slot:footer>
    </x-modal>
</div>
