<div class="space-y-6">
    <div
        class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Analytics</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">Reports</h2>
        </div>
            <span class="text-sm text-slate-500">Generate one report card or queue a complete class set.</span>
    </div>

    <form class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-6 md:grid-cols-3">
        <select wire:model="termId" class="rounded-lg border-slate-300">
            <option value="">Select term</option>
            @foreach ($terms as $term)
                <option value="{{ $term->id }}">{{ $term->name }}</option>
            @endforeach
        </select>
        <select wire:model="classId" class="rounded-lg border-slate-300">
            <option value="">Select class</option>
            @foreach ($classes as $class)
                <option value="{{ $class->id }}">{{ $class->name }}</option>
            @endforeach
        </select>
        <select wire:model="studentId" class="rounded-lg border-slate-300">
            <option value="">Select student for single report</option>
            @foreach ($students as $student)
                <option value="{{ $student->id }}">{{ $student->first_name }} {{ $student->last_name }}</option>
            @endforeach
        </select>
        <div class="md:col-span-3 flex flex-wrap justify-end gap-3"><x-button wire:click="generateSingle" icon="save"
                target="generateSingle">Generate single</x-button><x-button wire:click="generateBulk"
                variant="secondary" icon="save" target="generateBulk">Generate class reports</x-button><x-button
                wire:click="publishBulk" variant="success" target="publishBulk">Publish class reports</x-button></div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                <tr>
                    <th class="px-5 py-3">Student</th>
                    <th class="px-5 py-3">Attendance</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Generated</th>
                    <th class="px-5 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($reportCards as $reportCard)
                    <tr wire:key="report-card-{{ $reportCard->id }}">
                        <td class="px-5 py-4 font-medium">{{ $reportCard->student->first_name }}
                            {{ $reportCard->student->last_name }}</td>
                        <td class="px-5 py-4">
                            {{ $reportCard->attendance_percentage === null ? '—' : $reportCard->attendance_percentage . '%' }}
                        </td>
                        <td class="px-5 py-4">{{ ucfirst($reportCard->status) }}</td>
                        <td class="px-5 py-4">{{ $reportCard->generated_at?->format('d M Y') }}</td>
                        <td class="px-5 py-4 text-right">
                            @if ($reportCard->status !== 'published')
                                <x-button wire:click="publish({{ $reportCard->id }})" size="xs"
                                    target="publish({{ $reportCard->id }})">Publish</x-button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-slate-500">No report cards generated yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <x-stat-card label="Attendance rate" :value="number_format((float) ($metrics['attendance'] ?? 0), 1).'%'" tone="success" />
        <x-stat-card label="Average score" :value="number_format((float) ($metrics['averageScore'] ?? 0), 1)" tone="primary" />
        <x-stat-card label="At-risk students" :value="(string) ($metrics['atRisk'] ?? 0)" tone="warning" />
    </div>
</div>
