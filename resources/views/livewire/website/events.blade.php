<div class="bg-white">
    <x-website.hero eyebrow="Be part of the moment" title="Upcoming events"
        description="There is always something meaningful happening at our school." />
    <div class="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        <div class="space-y-4">
            @forelse($upcoming as $event)
                <article wire:key="event-{{ $event->id }}"
                    class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-6 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">
                            {{ $event->starts_at->format('d F Y, H:i') }}</p>
                        <h2 class="mt-2 text-xl font-bold text-slate-900">{{ $event->title }}</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ $event->description }}</p>
                        <p class="mt-2 text-xs text-slate-500">{{ $event->location }}</p>
                    </div><a href="{{ route('website.contact') }}"
                        class="inline-flex w-fit rounded-full px-4 py-2 text-sm font-semibold text-white"
                        style="background: {{ app(\App\Support\SchoolBranding::class)->data()['colors']['primary'] }}">Ask
                        a question</a>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-600">No
                    upcoming events have been published yet.</div>
            @endforelse
            @if ($past->isNotEmpty())
                <h2 class="pt-10 text-2xl font-bold text-slate-900">Recently held</h2>
                @foreach ($past as $event)
                    <article wire:key="past-event-{{ $event->id }}"
                        class="flex flex-col gap-2 rounded-2xl border border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                {{ $event->starts_at->format('d F Y') }}</p>
                            <h3 class="mt-1 font-semibold text-slate-900">{{ $event->title }}</h3>
                        </div><span class="text-sm text-slate-500">{{ $event->location }}</span>
                    </article>
                @endforeach
            @endif
        </div>
    </div>
</div>
