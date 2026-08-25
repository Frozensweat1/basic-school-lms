<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">School records</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Ward attendance</h2>
            <p class="mt-1 text-sm text-slate-600">Monitor each ward’s attendance history and identify absences that may need support.</p>
        </div>
        <div class="rounded-xl bg-blue-50 px-4 py-3 text-right">
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Attendance rate</p>
            <p class="mt-1 text-2xl font-bold text-blue-900">{{ $percentage }}%</p>
        </div>
    </div>

    <div class="grid grid-cols-4 gap-4">
        <article class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-emerald-800">Present</p>
            <p class="mt-2 text-3xl font-bold text-emerald-900">{{ $summary['present'] ?? 0 }}</p>
            <p class="mt-1 text-xs text-emerald-700">Days marked present</p>
        </article>
        <article class="rounded-2xl border border-rose-100 bg-rose-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-rose-800">Absent</p>
            <p class="mt-2 text-3xl font-bold text-rose-900">{{ $summary['absent'] ?? 0 }}</p>
            <p class="mt-1 text-xs text-rose-700">Days missed</p>
        </article>
        <article class="rounded-2xl border border-amber-100 bg-amber-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-amber-800">Late</p>
            <p class="mt-2 text-3xl font-bold text-amber-900">{{ $summary['late'] ?? 0 }}</p>
            <p class="mt-1 text-xs text-amber-700">Late arrivals</p>
        </article>
        <article class="rounded-2xl border border-violet-100 bg-violet-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-violet-800">Excused</p>
            <p class="mt-2 text-3xl font-bold text-violet-900">{{ $summary['excused'] ?? 0 }}</p>
            <p class="mt-1 text-xs text-violet-700">Approved absences</p>
        </article>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_190px_190px] lg:items-end">
            <div>
                <label for="parent-attendance-student" class="block text-sm font-medium text-slate-700">Ward</label>
                <select wire:model.live="studentId" id="parent-attendance-student" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                    <option value="">Choose a ward</option>
                    @foreach ($students as $ward)
                        <option value="{{ $ward->id }}">{{ $ward->first_name }} {{ $ward->last_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="parent-attendance-from" class="block text-sm font-medium text-slate-700">From</label>
                <input wire:model.live="fromDate" id="parent-attendance-from" type="date" max="{{ now()->toDateString() }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                @error('fromDate') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="parent-attendance-to" class="block text-sm font-medium text-slate-700">To</label>
                <input wire:model.live="toDate" id="parent-attendance-to" type="date" max="{{ now()->toDateString() }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                @error('toDate') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="mt-3 flex justify-end"><span wire:loading wire:target="studentId,fromDate,toDate" class="text-xs font-medium text-slate-500">Refreshing attendance…</span></div>
    </section>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Class</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Remark</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $record)
                        @php
                            $statusClasses = [
                                'present' => 'bg-emerald-100 text-emerald-800',
                                'absent' => 'bg-rose-100 text-rose-800',
                                'late' => 'bg-amber-100 text-amber-800',
                                'excused' => 'bg-violet-100 text-violet-800',
                            ][$record->status] ?? 'bg-slate-100 text-slate-700';
                        @endphp
                        <tr wire:key="parent-attendance-{{ $record->id }}" class="hover:bg-slate-50/80">
                            <td class="px-5 py-4 font-medium text-slate-900">{{ $record->attendance_date->format('d M Y') }}</td>
                            <td class="px-5 py-4 text-slate-700">{{ $record->schoolClass->name }}</td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">{{ ucfirst($record->status) }}</span></td>
                            <td class="px-5 py-4 text-slate-700">{{ $record->remarks ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-14 text-center text-slate-500">No attendance records match this ward and date range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-pagination :paginator="$records" />
</div>
