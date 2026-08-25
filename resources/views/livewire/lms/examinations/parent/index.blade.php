<div class="space-y-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Assessment schedule</p>
        <h2 class="mt-2 text-2xl font-bold text-slate-900">Ward examinations</h2>
        <p class="mt-1 text-sm text-slate-600">Review each ward’s scheduled and completed examinations.</p>
    </div>

    <section class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-[minmax(180px,0.65fr)_minmax(0,1fr)_minmax(180px,0.55fr)_auto] lg:items-center">
        <div>
            <label for="parent-examination-student" class="sr-only">Choose a ward</label>
            <select wire:model.live="studentId" id="parent-examination-student" class="w-full rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">Choose a ward</option>
                @foreach ($students as $student)
                    <option value="{{ $student->id }}">{{ $student->first_name }} {{ $student->last_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="relative">
            <label for="parent-examination-search" class="sr-only">Search examinations</label>
            <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.5-3.5"></path>
            </svg>
            <input id="parent-examination-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search by examination, subject, or class" autocomplete="off" class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-24 text-sm shadow-sm transition focus:border-blue-700 focus:ring-blue-700">
            <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching…</span>
        </div>
        <div>
            <label for="parent-examination-term" class="sr-only">Filter by term</label>
            <select wire:model.live="termId" id="parent-examination-term" class="w-full rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All terms</option>
                @foreach ($terms as $term)
                    <option value="{{ $term->id }}">{{ $term->name }}</option>
                @endforeach
            </select>
        </div>
        @if (filled($search) || filled($termId))
            <x-button wire:click="clearFilters" variant="ghost" size="sm" target="clearFilters" :loading="true">Clear</x-button>
        @endif
    </section>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Examination</th>
                        <th class="px-5 py-3">Class subject</th>
                        <th class="px-5 py-3">Term</th>
                        <th class="px-5 py-3">Date and duration</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($examinations as $examination)
                        @php
                            $statusClasses = $examination->status === 'completed'
                                ? 'bg-emerald-100 text-emerald-800'
                                : 'bg-blue-100 text-blue-800';
                        @endphp
                        <tr wire:key="parent-examination-{{ $examination->id }}" class="hover:bg-slate-50/80">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-900">{{ $examination->title }}</p>
                                @if ($examination->description)
                                    <p class="mt-1 max-w-xs truncate text-xs text-slate-500">{{ $examination->description }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 font-medium text-slate-800">{{ $examination->classSubject->schoolClass->name }} · {{ $examination->classSubject->subject->name }}</td>
                            <td class="px-5 py-4 text-slate-700">{{ $examination->term->name }}</td>
                            <td class="px-5 py-4 text-slate-700">
                                <p class="font-medium">{{ $examination->exam_date->format('d M Y') }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $examination->duration_minutes ? $examination->duration_minutes.' minutes' : 'Duration to be confirmed' }}</p>
                            </td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">{{ $examination->status === 'published' ? 'Scheduled' : ucfirst($examination->status) }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-14 text-center text-slate-500">
                                @if (! filled($studentId))
                                    Choose a ward to view examinations.
                                @elseif (filled($search) || filled($termId))
                                    No examinations match the selected filters.
                                @else
                                    No examinations are scheduled for this ward.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-pagination :paginator="$examinations" />
</div>
