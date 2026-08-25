@php
    $startsAtUtc = $event->starts_at?->copy()->utc()->format('Ymd\THis\Z');
    $endsAtUtc = ($event->ends_at ?: $event->starts_at?->copy()->addHours(2))?->copy()->utc()->format('Ymd\THis\Z');
    $calendarUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' . rawurlencode($event->title)
        . '&dates=' . $startsAtUtc . '/' . $endsAtUtc
        . '&details=' . rawurlencode($event->description ?? '')
        . '&location=' . rawurlencode($event->location ?? '');
@endphp

<article class="bg-white">
    <x-website.hero
        variant="article"
        eyebrow="{{ $event->starts_at->format('l, d F Y') }}"
        :title="$event->title"
        :description="$event->description"
        :image="$event->featured_image_path ? Storage::disk('public')->url($event->featured_image_path) : null"
        :image-alt="$event->title"
        :action="$calendarUrl"
        action-label="Add to calendar"
        :secondary-action="route('website.events')"
        secondary-action-label="All events"
        :breadcrumbs="[['label' => 'Events', 'url' => route('website.events')], ['label' => $event->title]]"
    />

    <section class="py-14 sm:py-20">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 sm:px-6 lg:grid-cols-[1fr_22rem] lg:px-8">
            <div>
                <x-website.section-heading eyebrow="Event details" title="Everything you need to plan your visit" />
                @if ($event->description)
                    <p class="mt-6 max-w-3xl text-lg leading-8 text-slate-600">{{ $event->description }}</p>
                @else
                    <p class="mt-6 max-w-3xl text-lg leading-8 text-slate-600">Contact our school office if you would like more information about this event.</p>
                @endif
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ $calendarUrl }}" target="_blank" rel="noopener noreferrer" class="website-button website-button-primary">Add to Google Calendar</a>
                    <a href="{{ route('website.contact') }}" class="website-button website-button-secondary">Ask a question</a>
                </div>
            </div>

            <dl class="divide-y divide-slate-200 overflow-hidden rounded-3xl border border-slate-200 bg-slate-50">
                <div class="p-6">
                    <dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Date and time</dt>
                    <dd class="mt-2 font-bold leading-7 text-slate-950">
                        <time datetime="{{ $event->starts_at->toAtomString() }}">{{ $event->starts_at->format('d F Y, H:i') }}</time>
                        @if ($event->ends_at)<span class="block text-sm font-medium text-slate-600">Until {{ $event->ends_at->format($event->ends_at->isSameDay($event->starts_at) ? 'H:i' : 'd F Y, H:i') }}</span>@endif
                    </dd>
                </div>
                @if ($event->location)
                    <div class="p-6">
                        <dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Location</dt>
                        <dd class="mt-2 font-bold leading-7 text-slate-950">{{ $event->location }}</dd>
                    </div>
                @endif
                <div class="p-6">
                    <dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Need help?</dt>
                    <dd class="mt-2 text-sm leading-6 text-slate-600">Our school office can help with directions, accessibility, and attendance questions.</dd>
                    <a href="{{ route('website.contact') }}" class="mt-3 inline-flex text-sm font-bold text-[var(--brand-primary)] hover:underline">Contact the office</a>
                </div>
            </dl>
        </div>
    </section>
</article>
