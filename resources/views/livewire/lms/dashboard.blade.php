<div class="space-y-6">
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total students</p>
            <p class="mt-4 text-3xl font-bold text-slate-900">1,420</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total teachers</p>
            <p class="mt-4 text-3xl font-bold text-slate-900">84</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Classes</p>
            <p class="mt-4 text-3xl font-bold text-slate-900">36</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Pending submissions</p>
            <p class="mt-4 text-3xl font-bold text-slate-900">28</p>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[2fr_1fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Attendance overview</h2>
            <div class="mt-6 h-56 rounded-xl bg-gradient-to-r from-sky-100 via-blue-100 to-indigo-100 p-4">
                <div class="flex h-full items-end gap-3">
                    @foreach ([60, 72, 68, 80, 76, 88, 90] as $value)
                        <div class="flex-1 rounded-t-xl bg-blue-600/80" style="height: {{ $value }}%;"></div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Recent announcements</h2>
            <ul class="mt-6 space-y-4 text-sm text-slate-600">
                <li class="rounded-xl bg-slate-50 p-3"><strong class="text-slate-900">School fee reminder</strong><br>Published 2 hours ago.</li>
                <li class="rounded-xl bg-slate-50 p-3"><strong class="text-slate-900">Parent meeting</strong><br>Next Friday at 9:00 AM.</li>
                <li class="rounded-xl bg-slate-50 p-3"><strong class="text-slate-900">Science fair</strong><br>Registration closes this week.</li>
            </ul>
        </div>
    </section>
</div>
