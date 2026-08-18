<div class="mx-auto max-w-4xl bg-white p-8 text-slate-900 print:p-0">
    <div class="flex items-start justify-between border-b-2 border-slate-900 pb-5">
        <div><p class="text-sm font-semibold uppercase tracking-widest">Student report card</p><h1 class="mt-2 text-3xl font-bold">{{ $reportCard->student->school->name }}</h1></div>
        <div class="text-right text-sm"><p>{{ $reportCard->academicYear->name }} · {{ $reportCard->term->name }}</p><p>{{ $reportCard->schoolClass->name }}</p></div>
    </div>
    <div class="mt-6 grid gap-3 sm:grid-cols-2"><p><span class="font-semibold">Student:</span> {{ $reportCard->student->first_name }} {{ $reportCard->student->last_name }}</p><p><span class="font-semibold">Attendance:</span> {{ $reportCard->attendance_percentage ?? '—' }}%</p></div>
    <table class="mt-6 min-w-full border-collapse text-sm"><thead><tr class="border-y border-slate-400 text-left"><th class="py-2">Subject</th><th class="py-2">Score</th><th class="py-2">Grade</th><th class="py-2">Comment</th></tr></thead><tbody>@foreach($results as $result)<tr class="border-b border-slate-200"><td class="py-2">{{ $result->classSubject->subject->name }}</td><td class="py-2">{{ $result->total_score }}</td><td class="py-2">{{ $result->grade }}</td><td class="py-2">{{ $result->teacher_comment }}</td></tr>@endforeach</tbody></table>
    <div class="mt-8 grid gap-6 sm:grid-cols-2"><p><span class="font-semibold">Teacher comment:</span><br>{{ $reportCard->teacher_comment }}</p><p><span class="font-semibold">Headteacher comment:</span><br>{{ $reportCard->headteacher_comment }}</p></div>
</div>
