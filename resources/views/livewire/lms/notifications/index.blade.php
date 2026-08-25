<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div><p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Communication</p><h2 class="mt-2 text-2xl font-bold text-slate-900">Notifications</h2><p class="mt-1 text-sm text-slate-600">Updates about announcements, learning activities, attendance, results, and reports.</p></div>
        <div class="flex flex-wrap justify-end gap-3">@if ($readCount)<x-button wire:click="confirmClearRead" variant="ghost" size="sm" target="confirmClearRead" :loading="true">Clear read</x-button>@endif @if ($unreadCount)<x-button wire:click="markAllRead" variant="secondary" size="sm" target="markAllRead" :loading="true">Mark all read</x-button>@endif</div>
    </div>

    <div class="grid grid-cols-4 gap-4">
        <article class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm"><p class="text-sm font-medium text-blue-800">All notifications</p><p class="mt-2 text-3xl font-bold text-blue-900">{{ $totalCount }}</p><p class="mt-1 text-xs text-blue-700">Your complete notification inbox</p></article>
        <article class="rounded-2xl border border-violet-100 bg-violet-50 p-5 shadow-sm"><p class="text-sm font-medium text-violet-800">Unread</p><p class="mt-2 text-3xl font-bold text-violet-900">{{ $unreadCount }}</p><p class="mt-1 text-xs text-violet-700">Still requiring your attention</p></article>
        <article class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm"><p class="text-sm font-medium text-emerald-800">Read</p><p class="mt-2 text-3xl font-bold text-emerald-900">{{ $readCount }}</p><p class="mt-1 text-xs text-emerald-700">Previously reviewed updates</p></article>
        <article class="rounded-2xl border border-amber-100 bg-amber-50 p-5 shadow-sm"><p class="text-sm font-medium text-amber-800">Received today</p><p class="mt-2 text-3xl font-bold text-amber-900">{{ $todayCount }}</p><p class="mt-1 text-xs text-amber-700">New activity during the day</p></article>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_200px_220px_auto] lg:items-center">
        <div class="relative"><label for="notification-search" class="sr-only">Search notifications</label><svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg><input id="notification-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search notification titles and messages" class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-24 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700"><span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching...</span></div>
        <select wire:model.live="filterState" aria-label="Filter by read state" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700"><option value="">All states</option><option value="unread">Unread</option><option value="read">Read</option></select>
        <select wire:model.live="filterKind" aria-label="Filter by notification type" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700"><option value="">All notification types</option>@foreach ($kinds as $kind)<option value="{{ $kind }}">{{ ucfirst($kind) }}</option>@endforeach</select>
        @if (filled($search) || filled($filterState) || filled($filterKind))<x-button wire:click="clearFilters" variant="ghost" size="sm" target="clearFilters" :loading="true">Clear filters</x-button>@endif
    </div></section>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @forelse ($notifications as $notification)
            @php
                $data = $notification->data ?? [];
                $kind = $data['kind'] ?? 'info';
                $styles = match ($kind) {
                    'announcement' => ['bg-violet-100 text-violet-700', 'M4 4h12v9H9l-4 3v-3H4V4Z'],
                    'assignment' => ['bg-blue-100 text-blue-700', 'M5 2.5h10v15H5v-15ZM8 7h4M8 10h4M8 13h3'],
                    'attendance' => ['bg-amber-100 text-amber-700', 'M10 2.5a7.5 7.5 0 1 0 0 15 7.5 7.5 0 0 0 0-15ZM10 6v4l2.5 2'],
                    'quiz' => ['bg-cyan-100 text-cyan-700', 'M7.5 7a2.5 2.5 0 1 1 3 2.45V11M10 14.5h.01M3 2.5h14v15H3v-15Z'],
                    'result', 'report' => ['bg-emerald-100 text-emerald-700', 'M4 16V8m4 8V4m4 12v-6m4 6V6'],
                    default => ['bg-slate-100 text-slate-700', 'M10 17.5a7.5 7.5 0 1 0 0-15 7.5 7.5 0 0 0 0 15ZM10 9v5M10 6h.01'],
                };
            @endphp
            <article wire:key="notification-{{ $notification->id }}" class="flex items-start gap-4 border-b border-slate-100 px-5 py-5 last:border-b-0 {{ $notification->read_at ? 'bg-white' : 'bg-blue-50/50' }}">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $styles[0] }}"><svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="{{ $styles[1] }}"></path></svg></span>
                <div class="min-w-0 flex-1"><div class="flex flex-wrap items-start justify-between gap-2"><div><div class="flex items-center gap-2"><p class="font-bold text-slate-900">{{ $data['title'] ?? class_basename($notification->type) }}</p>@if (! $notification->read_at)<span class="h-2.5 w-2.5 rounded-full bg-blue-600" aria-label="Unread"></span>@endif</div><p class="mt-1 text-sm leading-6 text-slate-600">{{ $data['message'] ?? 'You have a new notification.' }}</p></div><span class="shrink-0 text-xs font-medium text-slate-400" title="{{ $notification->created_at->format('d M Y, H:i') }}">{{ $notification->created_at->diffForHumans() }}</span></div>
                    <div class="mt-3 flex flex-wrap items-center gap-3 text-xs">@if (! empty($data['url']))<button type="button" wire:click="openNotification('{{ $notification->id }}')" wire:loading.attr="disabled" wire:target="openNotification('{{ $notification->id }}')" class="inline-flex cursor-pointer items-center gap-1 font-bold text-blue-800 hover:text-blue-950 disabled:cursor-not-allowed disabled:opacity-60">Open <span aria-hidden="true">&rarr;</span></button>@endif @if ($notification->read_at)<button type="button" wire:click="markUnread('{{ $notification->id }}')" wire:loading.attr="disabled" wire:target="markUnread('{{ $notification->id }}')" class="cursor-pointer font-semibold text-slate-600 hover:text-slate-900 disabled:opacity-60">Mark unread</button>@else<button type="button" wire:click="markRead('{{ $notification->id }}')" wire:loading.attr="disabled" wire:target="markRead('{{ $notification->id }}')" class="cursor-pointer font-semibold text-slate-600 hover:text-slate-900 disabled:opacity-60">Mark read</button>@endif <button type="button" wire:click="confirmDelete('{{ $notification->id }}')" wire:loading.attr="disabled" wire:target="confirmDelete('{{ $notification->id }}')" class="cursor-pointer font-semibold text-rose-600 hover:text-rose-800 disabled:opacity-60">Remove</button></div>
                </div>
            </article>
        @empty
            <div class="px-5 py-14 text-center text-slate-500">{{ filled($search) || filled($filterState) || filled($filterKind) ? 'No notifications match the current search and filters.' : 'No notifications have arrived yet.' }}</div>
        @endforelse
        @if ($notifications->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $notifications->links() }}</div>@endif
    </div>

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm" role="dialog" aria-modal="true"><button type="button" class="absolute inset-0 cursor-default" wire:click="closeModals" aria-label="Close removal confirmation"></button><div class="relative z-10 w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"><h3 class="text-lg font-bold text-slate-900">Remove notification?</h3><p class="mt-2 text-sm text-slate-600">This notification will be permanently removed from your inbox.</p><div class="mt-6 flex justify-end gap-3"><x-button wire:click="closeModals" variant="secondary" target="closeModals" :loading="true">Cancel</x-button><x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Remove</x-button></div></div></div>
    @endif

    @if ($showClearModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm" role="dialog" aria-modal="true"><button type="button" class="absolute inset-0 cursor-default" wire:click="closeModals" aria-label="Close clear confirmation"></button><div class="relative z-10 w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"><h3 class="text-lg font-bold text-slate-900">Clear all read notifications?</h3><p class="mt-2 text-sm text-slate-600">Unread notifications will remain. This action permanently removes every notification already marked as read.</p><div class="mt-6 flex justify-end gap-3"><x-button wire:click="closeModals" variant="secondary" target="closeModals" :loading="true">Cancel</x-button><x-button wire:click="clearRead" variant="danger" icon="trash" target="clearRead" :loading="true">Clear read</x-button></div></div></div>
    @endif
</div>
