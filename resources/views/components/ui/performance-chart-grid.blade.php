@props([
    'prefix',
    'overview',
    'audienceLabel' => 'Performance',
])

<section class="space-y-4">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.2em] text-slate-500">{{ $audienceLabel }}</p>
        <h2 class="mt-1 text-xl font-bold text-slate-900">Performance trends</h2>
        <p class="mt-1 text-sm text-slate-600">Compare results by term, academic year, and subject using normalized percentages.</p>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-ui.dashboard-chart
            :id="$prefix.'-termly-performance-chart'"
            :title="'Termly performance — '.$overview['academicYearName']"
            subtitle="Average performance for each term in the selected academic year."
            :config="$overview['termlyChart']"
            empty-message="Termly performance will appear after scores are recorded for this academic year."
        />

        <x-ui.dashboard-chart
            :id="$prefix.'-academic-year-performance-chart'"
            title="Academic year performance"
            subtitle="Average performance trend across academic years."
            :config="$overview['academicYearChart']"
            empty-message="Academic year comparisons will appear when scored assessments are available."
        />

        <x-ui.dashboard-chart
            :id="$prefix.'-subject-period-performance-chart'"
            title="Subject performance: term vs academic year"
            :subtitle="$overview['termName'].' compared with '.$overview['academicYearName'].'.'"
            :config="$overview['subjectChart']"
            empty-message="Subject comparisons will appear after scores are recorded for the active term."
            class="xl:col-span-2"
        />
    </div>
</section>
