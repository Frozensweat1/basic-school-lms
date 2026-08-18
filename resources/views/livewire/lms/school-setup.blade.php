<div class="space-y-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">School management</p>
        <h2 class="mt-2 text-2xl font-bold text-slate-900">School setup</h2>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Academic years</h3>
            <ul class="mt-4 space-y-2 text-sm text-slate-600">
                @forelse ($years as $year)
                    <li>{{ $year->name }} ({{ $year->terms->count() }} terms)</li>
                @empty
                    <li>No configured years.</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Classes</h3>
            <ul class="mt-4 space-y-2 text-sm text-slate-600">
                @forelse ($classes as $schoolClass)
                    <li>{{ $schoolClass->name }} · {{ $schoolClass->students->count() }} students</li>
                @empty
                    <li>No configured classes.</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Subjects</h3>
            <ul class="mt-4 space-y-2 text-sm text-slate-600">
                @forelse ($subjects as $subject)
                    <li>{{ $subject->name }} ({{ $subject->code }})</li>
                @empty
                    <li>No configured subjects.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
