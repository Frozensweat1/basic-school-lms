@props(['announcement', 'manageable' => false])

@php
    $state = $announcement->publicationState();
    $stateClasses = [
        'published' => 'bg-emerald-100 text-emerald-800',
        'scheduled' => 'bg-blue-100 text-blue-800',
        'draft' => 'bg-amber-100 text-amber-800',
        'expired' => 'bg-slate-200 text-slate-700',
    ];
    $audienceClasses = [
        'school' => 'bg-violet-100 text-violet-800',
        'teachers' => 'bg-cyan-100 text-cyan-800',
        'class' => 'bg-blue-100 text-blue-800',
        'subject' => 'bg-amber-100 text-amber-800',
    ];
    $attachmentCount = $announcement->attachments_count ?? $announcement->attachments->count();
@endphp

<article {{ $attributes->class('rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-200 hover:shadow-md') }}>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $stateClasses[$state] ?? $stateClasses['draft'] }}">
                    {{ ucfirst($state) }}
                </span>
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $audienceClasses[$announcement->audience] ?? 'bg-slate-100 text-slate-700' }}">
                    {{ $announcement->audienceLabel() }}
                </span>
                @if ($attachmentCount)
                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                        {{ $attachmentCount }} {{ Illuminate\Support\Str::plural('attachment', $attachmentCount) }}
                    </span>
                @endif
            </div>

            <h3 class="mt-3 text-lg font-bold text-slate-900">{{ $announcement->title }}</h3>
            <p class="mt-1 text-xs text-slate-500">
                By {{ $announcement->author?->name ?? 'School administration' }}
                @if ($announcement->published_at)
                    &middot; {{ $announcement->published_at->isFuture() ? 'Publishes' : 'Published' }}
                    {{ $announcement->published_at->format('d M Y, H:i') }}
                @else
                    &middot; Not published
                @endif
                @if ($announcement->expires_at)
                    &middot; Expires {{ $announcement->expires_at->format('d M Y, H:i') }}
                @endif
            </p>
        </div>

        @if ($manageable)
            <div class="flex shrink-0 items-center gap-2">
                @can('update', $announcement)
                    @if (in_array($state, ['draft', 'scheduled'], true))
                        <x-button
                            wire:click="confirmPublish({{ $announcement->id }})"
                            size="xs"
                            variant="success"
                            target="confirmPublish({{ $announcement->id }})"
                            :loading="true"
                        >
                            Publish now
                        </x-button>
                    @endif
                    <x-ui.icon-button
                        wire:click="edit({{ $announcement->id }})"
                        icon="edit"
                        label="Edit announcement"
                        target="edit({{ $announcement->id }})"
                    />
                @endcan

                @can('delete', $announcement)
                    <x-ui.icon-button
                        wire:click="confirmDelete({{ $announcement->id }})"
                        icon="trash"
                        label="Archive announcement"
                        variant="danger"
                        target="confirmDelete({{ $announcement->id }})"
                    />
                @endcan
            </div>
        @endif
    </div>

    <div class="prose prose-sm mt-4 max-w-none text-slate-700">{!! $announcement->content !!}</div>

    @if ($announcement->attachments->isNotEmpty())
        <div class="mt-4 border-t border-slate-100 pt-4">
            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-400">Attachments</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($announcement->attachments as $attachment)
                    <x-button
                        wire:click="downloadAttachment({{ $attachment->id }})"
                        variant="ghost"
                        size="xs"
                        target="downloadAttachment({{ $attachment->id }})"
                        :loading="true"
                    >
                        {{ $attachment->name }}
                    </x-button>
                @endforeach
            </div>
        </div>
    @endif
</article>
