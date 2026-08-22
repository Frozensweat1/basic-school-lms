<div class="space-y-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Learning</p>
        <h2 class="mt-2 text-2xl font-bold text-slate-900">My lessons</h2>
        <p class="mt-1 text-sm text-slate-600">Read published lessons and track your progress.</p>
    </div>

    @if ($selectedLesson)
        <article class="rounded-2xl border border-blue-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-slate-500">{{ $selectedLesson->topic->classSubject->subject->name }} · {{ $selectedLesson->topic->title }}</p>
                    <h3 class="mt-1 text-xl font-bold text-slate-900">{{ $selectedLesson->title }}</h3>
                    @if ($selectedLesson->summary)
                        <p class="mt-2 text-sm text-slate-600">{{ $selectedLesson->summary }}</p>
                    @endif
                </div>
                <x-button wire:click="toggleCompleted({{ $selectedLesson->id }})" variant="primary" target="toggleCompleted({{ $selectedLesson->id }})" :loading="true">
                    {{ isset($completed[$selectedLesson->id]) ? 'Mark incomplete' : 'Mark complete' }}
                </x-button>
            </div>

            @if ($selectedLesson->content)
                <div class="prose prose-slate mt-6 max-w-none">{!! strip_tags($selectedLesson->content, '<p><br><strong><em><u><ol><ul><li><blockquote><h2><h3><h4>') !!}</div>
            @endif

            @if ($selectedLesson->objectives)
                <div class="mt-6 rounded-xl bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-900">Learning objectives</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-600">
                        @foreach ($selectedLesson->objectives as $objective)
                            <li>{{ $objective }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($selectedLesson->resources->isNotEmpty())
                <div class="mt-6">
                    <p class="text-sm font-semibold text-slate-900">Learning resources</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($selectedLesson->resources as $resource)
                            @if ($resource->external_url)
                                <a href="{{ $resource->external_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex cursor-pointer items-center rounded-lg bg-sky-100 px-3 py-2 text-xs font-semibold text-sky-800 hover:bg-sky-200">
                                    {{ $resource->title }} <span class="ml-1" aria-hidden="true">↗</span>
                                </a>
                            @else
                                <x-button wire:click="downloadResource({{ $resource->id }})" variant="secondary" size="xs" target="downloadResource({{ $resource->id }})" :loading="true">{{ $resource->title }}</x-button>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </article>
    @endif

    <div class="grid gap-4">
        @forelse ($lessons as $lesson)
            <article wire:key="lesson-{{ $lesson->id }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-500">{{ $lesson->topic->classSubject->subject->name }} · {{ $lesson->topic->title }}</p>
                        <h3 class="mt-1 font-semibold text-slate-900">{{ $lesson->title }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ $lesson->summary }}</p>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <x-button wire:click="viewLesson({{ $lesson->id }})" variant="secondary" target="viewLesson({{ $lesson->id }})" :loading="true">Read lesson</x-button>
                        <x-button wire:click="toggleCompleted({{ $lesson->id }})" variant="{{ isset($completed[$lesson->id]) ? 'secondary' : 'primary' }}" target="toggleCompleted({{ $lesson->id }})" :loading="true">
                            {{ isset($completed[$lesson->id]) ? 'Completed' : 'Mark complete' }}
                        </x-button>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-500">No published lessons are available for your current enrolment.</div>
        @endforelse
    </div>

    <x-pagination :paginator="$lessons" />
</div>
