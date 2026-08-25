<?php

namespace App\Services\Timetables;

use App\Models\ClassSubject;
use App\Models\SchedulePeriod;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimetableGenerator
{
    public function generate(Timetable $timetable, int $sessionsPerSubject = 1, bool $replaceExisting = true): array
    {
        $timetable->loadMissing('academicYear');
        $schoolId = (int) $timetable->academicYear->school_id;
        $periods = SchedulePeriod::query()
            ->where('school_id', $schoolId)
            ->orderBy('sequence')
            ->orderBy('starts_at')
            ->get();

        if ($periods->isEmpty()) {
            throw ValidationException::withMessages([
                'generation' => 'Create schedule periods before generating a timetable.',
            ]);
        }

        $subjects = ClassSubject::query()
            ->with(['schoolClass', 'subject', 'teacher'])
            ->whereHas('schoolClass', function (Builder $classes) use ($timetable): void {
                $classes->where('academic_year_id', $timetable->academic_year_id)
                    ->where('status', 'active');
            })
            ->get();

        if ($subjects->isEmpty()) {
            throw ValidationException::withMessages([
                'generation' => 'Assign subjects to active classes before generating a timetable.',
            ]);
        }

        $teacherDemand = $subjects->whereNotNull('teacher_id')->countBy('teacher_id');
        $subjects = $subjects
            ->sortByDesc(fn (ClassSubject $subject) => $teacherDemand->get($subject->teacher_id, 0))
            ->values();

        $unscheduled = [];
        $scheduledCount = 0;

        DB::transaction(function () use ($timetable, $periods, $subjects, $sessionsPerSubject, $replaceExisting, &$unscheduled, &$scheduledCount): void {
            if ($replaceExisting) {
                $timetable->entries()->delete();
            }

            $existingEntries = $timetable->entries()
                ->with('schedulePeriod')
                ->get();
            $usedByClass = [];
            $usedByTeacher = [];
            $subjectDays = [];
            $subjectSessions = [];
            $classDayLoad = [];
            $teacherDayLoad = [];

            foreach ($existingEntries as $entry) {
                $slot = $entry->day_of_week.'-'.$entry->schedule_period_id;
                $usedByClass[$entry->school_class_id][$slot] = true;
                if ($entry->teacher_id) {
                    $usedByTeacher[$entry->teacher_id][$slot] = true;
                    $teacherDayLoad[$entry->teacher_id][$entry->day_of_week] = ($teacherDayLoad[$entry->teacher_id][$entry->day_of_week] ?? 0) + 1;
                }
                $subjectDays[$entry->class_subject_id][$entry->day_of_week] = true;
                $subjectSessions[$entry->class_subject_id] = ($subjectSessions[$entry->class_subject_id] ?? 0) + 1;
                $classDayLoad[$entry->school_class_id][$entry->day_of_week] = ($classDayLoad[$entry->school_class_id][$entry->day_of_week] ?? 0) + 1;
            }

            foreach ($subjects as $subject) {
                $alreadyScheduled = $subjectSessions[$subject->id] ?? 0;
                $sessionsToAdd = max(0, $sessionsPerSubject - $alreadyScheduled);

                for ($session = 1; $session <= $sessionsToAdd; $session++) {
                    $candidate = null;

                    foreach (array_keys(TimetableEntry::DAYS) as $day) {
                        foreach ($periods as $period) {
                            $slot = $day.'-'.$period->id;
                            if (isset($usedByClass[$subject->school_class_id][$slot])) {
                                continue;
                            }
                            if ($subject->teacher_id && isset($usedByTeacher[$subject->teacher_id][$slot])) {
                                continue;
                            }

                            $score = (isset($subjectDays[$subject->id][$day]) ? 1000 : 0)
                                + (($classDayLoad[$subject->school_class_id][$day] ?? 0) * 20)
                                + ($subject->teacher_id ? (($teacherDayLoad[$subject->teacher_id][$day] ?? 0) * 10) : 0)
                                + ((int) $period->sequence);

                            if ($candidate === null || $score < $candidate['score']) {
                                $candidate = compact('day', 'period', 'score');
                            }
                        }
                    }

                    if ($candidate === null) {
                        $unscheduled[] = $subject->schoolClass->name.' - '.$subject->subject->name.' (session '.($alreadyScheduled + $session).')';

                        continue;
                    }

                    $entry = TimetableEntry::create([
                        'timetable_id' => $timetable->id,
                        'school_class_id' => $subject->school_class_id,
                        'class_subject_id' => $subject->id,
                        'teacher_id' => $subject->teacher_id,
                        'schedule_period_id' => $candidate['period']->id,
                        'day_of_week' => $candidate['day'],
                    ]);

                    $slot = $entry->day_of_week.'-'.$entry->schedule_period_id;
                    $usedByClass[$entry->school_class_id][$slot] = true;
                    if ($entry->teacher_id) {
                        $usedByTeacher[$entry->teacher_id][$slot] = true;
                        $teacherDayLoad[$entry->teacher_id][$entry->day_of_week] = ($teacherDayLoad[$entry->teacher_id][$entry->day_of_week] ?? 0) + 1;
                    }
                    $subjectDays[$entry->class_subject_id][$entry->day_of_week] = true;
                    $subjectSessions[$entry->class_subject_id] = ($subjectSessions[$entry->class_subject_id] ?? 0) + 1;
                    $classDayLoad[$entry->school_class_id][$entry->day_of_week] = ($classDayLoad[$entry->school_class_id][$entry->day_of_week] ?? 0) + 1;
                    $scheduledCount++;
                }
            }
        });

        return [
            'scheduled_count' => $scheduledCount,
            'unscheduled' => $unscheduled,
            'replace_existing' => $replaceExisting,
        ];
    }
}
