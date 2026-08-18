<div class="space-y-6">
    <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Academic</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">Examinations</h2>
        </div>
        <x-button variant="primary" size="md">New exam</x-button>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <x-empty-state
            title="No examinations scheduled"
            description="Create exams, assign schedules, and track results for each class."
            :action="'<x-button variant=\'primary\'>Schedule exam</x-button>'"
        />
    </div>
</div>
