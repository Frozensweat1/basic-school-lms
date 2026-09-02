<div class="space-y-6">
    <header class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.22em] text-blue-700">Communication</p>
                <h1 class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">Email Centre</h1>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">Send a private email to one person or a school-scoped message to staff, parents, and students. Every recipient receives a separate message.</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-semibold">Delivery uses the configured mailer</p>
                <p class="mt-0.5 text-xs text-amber-800">Keep an <span class="font-semibold">emails</span> queue worker running in production.</p>
            </div>
        </div>
    </header>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Email campaigns" :value="number_format($stats['campaigns'])" tone="primary" />
        <x-stat-card label="Delivered messages" :value="number_format($stats['delivered'])" tone="success" />
        <x-stat-card label="Queued or processing" :value="number_format($stats['processing'])" tone="warning" />
        <x-stat-card label="Failed deliveries" :value="number_format($stats['failed'])" tone="danger" />
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50 px-5 py-4 sm:px-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Compose an email</h2>
                    <p class="mt-1 text-sm text-slate-500">The recipient list is rebuilt and confirmed immediately before the campaign is queued.</p>
                </div>
                <div class="inline-flex w-full rounded-xl border border-slate-200 bg-white p-1 sm:w-auto" role="group" aria-label="Email mode">
                    <button type="button" wire:click="setMode('bulk')" wire:loading.attr="disabled" wire:target="setMode" class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition sm:flex-none {{ $mode === 'bulk' ? 'bg-blue-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M16 19c0-2.2-1.8-4-4-4s-4 1.8-4 4M12 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm7 6c0-1.5-.9-2.8-2.2-3.5M17 7.4a2.5 2.5 0 0 1 0 4.8M5 18c0-1.5.9-2.8 2.2-3.5M7 7.4a2.5 2.5 0 0 0 0 4.8" stroke-linecap="round"/></svg>
                        Bulk email
                    </button>
                    <button type="button" wire:click="setMode('single')" wire:loading.attr="disabled" wire:target="setMode" class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition sm:flex-none {{ $mode === 'single' ? 'bg-blue-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3"></circle><path d="M6.5 20a5.5 5.5 0 0 1 11 0" stroke-linecap="round"/></svg>
                        Single email
                    </button>
                </div>
            </div>
        </div>

        <form wire:submit="reviewRecipients" class="space-y-6 p-5 sm:p-6">
            @if ($mode === 'bulk')
                <div class="grid gap-5 xl:grid-cols-[minmax(0,1.25fr)_minmax(280px,.75fr)]">
                    <fieldset>
                        <legend class="text-sm font-bold text-slate-800">Recipient groups</legend>
                        <p class="mt-1 text-xs text-slate-500">Select one or more groups. Duplicate email addresses are automatically sent only once.</p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-3">
                            @foreach ([
                                'staff' => ['Staff', 'Active teachers'],
                                'parents' => ['Parents', 'Parents and guardians'],
                                'students' => ['Students', 'Active learners with accounts'],
                            ] as $value => [$label, $description])
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition {{ in_array($value, $audiences, true) ? 'border-blue-400 bg-blue-50 ring-1 ring-blue-200' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50' }}">
                                    <input type="checkbox" value="{{ $value }}" wire:model.live="audiences" class="mt-0.5 rounded border-slate-300 text-blue-900 focus:ring-blue-700">
                                    <span>
                                        <span class="block text-sm font-bold text-slate-900">{{ $label }}</span>
                                        <span class="mt-0.5 block text-xs text-slate-500">{{ $description }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('audiences')<p class="mt-2 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
                        @error('audiences.*')<p class="mt-2 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
                    </fieldset>

                    <div>
                        <label for="email-class-filter" class="text-sm font-bold text-slate-800">Class filter <span class="font-normal text-slate-400">(optional)</span></label>
                        <p class="mt-1 text-xs text-slate-500">Narrows the email to assigned teachers, enrolled students, and their parents.</p>
                        <select id="email-class-filter" wire:model.live="classId" class="mt-3 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                            <option value="">All current classes</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}{{ $class->stream ? ' · '.$class->stream->name : '' }} — {{ $class->academicYear?->name }}</option>
                            @endforeach
                        </select>
                        @if ($classes->isEmpty())<p class="mt-2 text-xs font-medium text-amber-700">No active-year classes are available. School-wide groups can still be emailed.</p>@endif
                        @error('classId')<p class="mt-2 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                    <div class="grid gap-4 lg:grid-cols-[minmax(240px,.7fr)_minmax(0,1.3fr)] lg:items-end">
                        <div>
                            <label for="recipient-search" class="block text-sm font-bold text-slate-800">Find a recipient</label>
                            <div class="relative mt-2">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                                <input id="recipient-search" type="search" wire:model.live.debounce.300ms="recipientSearch" placeholder="Name, ID, email, or phone" class="block w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-24 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                                <span wire:loading wire:target="recipientSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-500">Searching...</span>
                            </div>
                        </div>
                        <div>
                            <label for="single-recipient" class="block text-sm font-bold text-slate-800">Recipient</label>
                            <select id="single-recipient" wire:model.live="singleRecipientKey" class="mt-2 block w-full rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                                <option value="">Choose a staff member, parent, or student</option>
                                @foreach ($candidates as $candidate)
                                    <option value="{{ $candidate['key'] }}" @disabled(! $candidate['sendable'])>
                                        {{ $candidate['name'] }} · {{ ucfirst($candidate['audience']) }} · {{ $candidate['email'] ?: 'No email address' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('singleRecipientKey')<p class="mt-2 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">People without a valid email are shown for clarity but cannot be selected. Student email comes from the learner's linked user account.</p>
                </div>
            @endif

            <div>
                <label for="email-subject" class="block text-sm font-bold text-slate-800">Subject</label>
                <input id="email-subject" type="text" wire:model.blur="subject" maxlength="255" placeholder="Write a clear, specific subject" class="mt-2 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <div class="mt-1 flex justify-between gap-3"><div>@error('subject')<p class="text-sm font-medium text-rose-700">{{ $message }}</p>@enderror</div><span class="text-xs text-slate-400">{{ strlen($subject) }}/255</span></div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-bold text-slate-800">Message</label>
                <x-ui.rich-text-editor wire:model="body" id="email-message-body" placeholder="Write the email message..." min-height="16rem" />
                @error('body')<p class="mt-2 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                <x-button type="button" wire:click="clearComposer" variant="ghost" icon="close" target="clearComposer" :loading="true">Clear</x-button>
                <x-button type="submit" icon="check" target="reviewRecipients" :loading="true">Review recipients</x-button>
            </div>
        </form>
    </section>

    <section class="space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Delivery history</h2>
                <p class="mt-1 text-sm text-slate-500">Track delivery progress and retry only the messages that failed.</p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_auto] lg:items-center">
                <div class="relative">
                    <label for="email-history-search" class="sr-only">Search email history</label>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                    <input id="email-history-search" type="search" wire:model.live.debounce.300ms="historySearch" placeholder="Search subject or sender" class="block w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-24 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                    <span wire:loading wire:target="historySearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-500">Searching...</span>
                </div>
                <select wire:model.live="historyStatus" aria-label="Filter delivery status" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                    <option value="">All statuses</option>
                    <option value="queued">Queued</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="partial">Partially delivered</option>
                    <option value="failed">Failed</option>
                </select>
                @if (filled($historySearch) || filled($historyStatus))
                    <x-button wire:click="clearHistoryFilters" variant="ghost" size="sm" target="clearHistoryFilters" :loading="true">Clear filters</x-button>
                @endif
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Email</th>
                            <th class="px-5 py-3">Audience</th>
                            <th class="px-5 py-3">Delivery</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Queued</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($campaigns as $campaign)
                            @php
                                $statusVariant = match ($campaign->status) {
                                    'completed' => 'success',
                                    'partial', 'queued', 'processing' => 'warning',
                                    'failed' => 'danger',
                                    default => 'default',
                                };
                                $statusLabel = match ($campaign->status) {
                                    'partial' => 'Partial',
                                    'processing' => 'Processing',
                                    'completed' => 'Completed',
                                    'failed' => 'Failed',
                                    default => 'Queued',
                                };
                            @endphp
                            <tr wire:key="email-campaign-{{ $campaign->id }}" class="align-top hover:bg-slate-50/70">
                                <td class="min-w-64 px-5 py-4">
                                    <p class="font-bold text-slate-900">{{ $campaign->subject }}</p>
                                    <p class="mt-1 text-xs text-slate-500">By {{ $campaign->creator?->name ?: 'Deleted user' }} · {{ ucfirst($campaign->mode) }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex max-w-48 flex-wrap gap-1">
                                        @foreach ($campaign->audiences as $audience)<x-badge variant="info">{{ ucfirst($audience) }}</x-badge>@endforeach
                                    </div>
                                    @if ($campaign->schoolClass)<p class="mt-1.5 text-xs text-slate-500">{{ $campaign->schoolClass->name }}{{ $campaign->schoolClass->stream ? ' · '.$campaign->schoolClass->stream->name : '' }}</p>@endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-600">
                                    <p><span class="font-bold text-emerald-700">{{ number_format($campaign->sent_count) }}</span> / {{ number_format($campaign->recipient_count) }} sent</p>
                                    @if ($campaign->failed_count)<p class="mt-1 font-semibold text-rose-700">{{ number_format($campaign->failed_count) }} failed</p>@endif
                                    @if ($campaign->skipped_count)<p class="mt-1 font-semibold text-amber-700">{{ number_format($campaign->skipped_count) }} skipped</p>@endif
                                </td>
                                <td class="px-5 py-4"><x-badge :variant="$statusVariant">{{ $statusLabel }}</x-badge></td>
                                <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-500">
                                    <p>{{ $campaign->queued_at?->format('d M Y') }}</p>
                                    <p class="mt-1">{{ $campaign->queued_at?->format('H:i') }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <x-button wire:click="viewCampaign({{ $campaign->id }})" variant="ghost" size="xs" target="viewCampaign({{ $campaign->id }})" :loading="true">Details</x-button>
                                        @if ($campaign->failed_count > 0)
                                            <x-button wire:click="confirmRetry({{ $campaign->id }})" variant="secondary" size="xs" target="confirmRetry({{ $campaign->id }})" :loading="true">Retry failed</x-button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-14 text-center text-slate-500">{{ filled($historySearch) || filled($historyStatus) ? 'No campaigns match the current filters.' : 'No email campaigns have been sent yet.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($campaigns->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $campaigns->links() }}</div>@endif
        </div>
    </section>

    <x-modal :show="$showReviewModal" title="Review and queue email" close-action="closeModals" max-width="2xl">
        <div class="space-y-5">
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                <p class="text-sm font-bold text-blue-950">{{ $subject }}</p>
                <div class="mt-3 grid gap-3 sm:grid-cols-3">
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Deliverable</p><p class="mt-1 text-2xl font-bold text-blue-950">{{ number_format($previewRecipientCount) }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Missing email</p><p class="mt-1 text-2xl font-bold text-blue-950">{{ number_format($previewMissingCount) }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Duplicates</p><p class="mt-1 text-2xl font-bold text-blue-950">{{ number_format($previewDuplicateCount) }}</p></div>
                </div>
            </div>

            @if ($previewSkippedCount > 0)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {{ number_format($previewSkippedCount) }} selected record{{ $previewSkippedCount === 1 ? ' is' : 's are' }} skipped because the email is missing, invalid, or duplicated. This will be recorded in delivery history.
                </div>
            @endif

            <div>
                <h3 class="text-sm font-bold text-slate-800">Recipient preview</h3>
                <div class="mt-2 overflow-hidden rounded-xl border border-slate-200">
                    <div class="max-h-72 divide-y divide-slate-100 overflow-y-auto">
                        @foreach ($previewSample as $recipient)
                            <div class="flex items-start justify-between gap-4 px-4 py-3 text-sm">
                                <div><p class="font-semibold text-slate-900">{{ $recipient['name'] }}</p><p class="mt-0.5 break-all text-xs text-slate-500">{{ $recipient['email'] }}</p></div>
                                <x-badge variant="info">{{ ucfirst($recipient['audience']) }}</x-badge>
                            </div>
                        @endforeach
                    </div>
                </div>
                @if ($previewRecipientCount > count($previewSample))<p class="mt-2 text-xs text-slate-500">Plus {{ number_format($previewRecipientCount - count($previewSample)) }} more deliverable recipients.</p>@endif
            </div>

            <p class="text-sm text-slate-600">Queuing creates one private email per address. Delivery continues in the background and can be monitored below.</p>
        </div>
        <x-slot:footer>
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-button wire:click="closeModals" variant="secondary" icon="close" target="closeModals" :loading="true">Go back</x-button>
                <x-button wire:click="queueEmail" variant="success" icon="check" target="queueEmail" :loading="true">Queue {{ number_format($previewRecipientCount) }} email{{ $previewRecipientCount === 1 ? '' : 's' }}</x-button>
            </div>
        </x-slot:footer>
    </x-modal>

    <x-modal :show="$showDetailsModal" title="Campaign delivery details" close-action="closeModals" max-width="3xl">
        @if ($detailsCampaign)
            @php
                $detailStatusVariant = match ($detailsCampaign->status) { 'completed' => 'success', 'failed' => 'danger', 'partial', 'queued', 'processing' => 'warning', default => 'default' };
            @endphp
            <div class="space-y-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div><h3 class="text-lg font-bold text-slate-900">{{ $detailsCampaign->subject }}</h3><p class="mt-1 text-xs text-slate-500">Queued {{ $detailsCampaign->queued_at?->format('d M Y \a\t H:i') }} by {{ $detailsCampaign->creator?->name ?: 'Deleted user' }}</p></div>
                    <x-badge :variant="$detailStatusVariant" size="md">{{ str($detailsCampaign->status)->headline() }}</x-badge>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-xl bg-slate-100 p-3"><p class="text-xs font-semibold text-slate-500">Recipients</p><p class="mt-1 text-xl font-bold text-slate-900">{{ number_format($detailsCampaign->recipient_count) }}</p></div>
                    <div class="rounded-xl bg-emerald-50 p-3"><p class="text-xs font-semibold text-emerald-700">Sent</p><p class="mt-1 text-xl font-bold text-emerald-900">{{ number_format($detailsCampaign->sent_count) }}</p></div>
                    <div class="rounded-xl bg-rose-50 p-3"><p class="text-xs font-semibold text-rose-700">Failed</p><p class="mt-1 text-xl font-bold text-rose-900">{{ number_format($detailsCampaign->failed_count) }}</p></div>
                    <div class="rounded-xl bg-amber-50 p-3"><p class="text-xs font-semibold text-amber-700">Skipped</p><p class="mt-1 text-xl font-bold text-amber-900">{{ number_format($detailsCampaign->skipped_count) }}</p></div>
                </div>

                <details class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <summary class="cursor-pointer text-sm font-bold text-slate-800">View message content</summary>
                    <div class="prose prose-sm mt-4 max-w-none text-slate-700">{!! $detailsCampaign->body !!}</div>
                </details>

                <div>
                    <h3 class="text-sm font-bold text-slate-800">Recipient ledger</h3>
                    <div class="mt-2 max-h-96 overflow-auto rounded-xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="sticky top-0 bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Recipient</th><th class="px-4 py-3">Group</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Attempts / note</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($detailsRecipients as $recipient)
                                    @php $recipientVariant = match ($recipient->status) { 'sent' => 'success', 'failed' => 'danger', 'skipped' => 'warning', 'sending' => 'info', default => 'default' }; @endphp
                                    <tr>
                                        <td class="px-4 py-3"><p class="font-semibold text-slate-900">{{ $recipient->recipient_name }}</p><p class="mt-0.5 break-all text-xs text-slate-500">{{ $recipient->email ?: 'No email address' }}</p></td>
                                        <td class="px-4 py-3 text-xs text-slate-600">{{ ucfirst($recipient->audience) }}</td>
                                        <td class="px-4 py-3"><x-badge :variant="$recipientVariant">{{ ucfirst($recipient->status) }}</x-badge></td>
                                        <td class="max-w-64 px-4 py-3 text-xs text-slate-500"><p>{{ $recipient->attempts }} attempt{{ $recipient->attempts === 1 ? '' : 's' }}</p>@if ($recipient->skip_reason || $recipient->last_error)<p class="mt-1 break-words text-rose-700">{{ str($recipient->skip_reason ?: $recipient->last_error)->limit(120) }}</p>@endif</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($detailsCampaign->recipients()->count() > 100)<p class="mt-2 text-xs text-slate-500">Showing the first 100 recipient records.</p>@endif
                </div>
            </div>
        @endif
        <x-slot:footer>
            <div class="flex justify-end gap-3">
                <x-button wire:click="closeModals" variant="secondary" icon="close" target="closeModals" :loading="true">Close</x-button>
                @if ($detailsCampaign && $detailsCampaign->failed_count > 0)<x-button wire:click="confirmRetry({{ $detailsCampaign->id }})" variant="primary" target="confirmRetry({{ $detailsCampaign->id }})" :loading="true">Retry failed</x-button>@endif
            </div>
        </x-slot:footer>
    </x-modal>

    <x-modal :show="$showRetryModal" title="Retry failed deliveries?" close-action="closeModals" max-width="md">
        <p class="text-sm text-slate-600">Only recipients currently marked as failed will be queued again. Delivered messages will not be resent.</p>
        <x-slot:footer>
            <div class="flex justify-end gap-3">
                <x-button wire:click="closeModals" variant="secondary" icon="close" target="closeModals" :loading="true">Cancel</x-button>
                <x-button wire:click="retryFailed" variant="primary" target="retryFailed" :loading="true">Queue failed again</x-button>
            </div>
        </x-slot:footer>
    </x-modal>
</div>
