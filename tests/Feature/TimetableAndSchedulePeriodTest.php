<?php

namespace Tests\Feature;

use App\Livewire\LMS\SchedulePeriods\Index as SchedulePeriodsIndex;
use App\Livewire\LMS\TimetableEntries\Index as TimetableEntriesIndex;
use App\Livewire\LMS\Timetables\Parent\Index as ParentTimetableIndex;
use App\Livewire\LMS\Timetables\Student\Index as StudentTimetableIndex;
use App\Livewire\LMS\Timetables\Teacher\Index as TeacherTimetableIndex;
use App\Models\AcademicYear;
use App\Models\ClassEnrollment;
use App\Models\ClassSubject;
use App\Models\ParentGuardian;
use App\Models\SchedulePeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use App\Models\User;
use App\Services\Timetables\TimetableGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TimetableAndSchedulePeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_periods_but_overlapping_times_are_rejected(): void
    {
        [$admin, $school] = $this->schoolAdminAndSchool();

        Livewire::actingAs($admin)
            ->test(SchedulePeriodsIndex::class)
            ->call('create')
            ->set('name', 'Period 1')
            ->set('startsAt', '08:00')
            ->set('endsAt', '08:45')
            ->set('sequence', '1')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('schedule_periods', [
            'school_id' => $school->id,
            'name' => 'Period 1',
            'starts_at' => '08:00',
            'ends_at' => '08:45',
        ]);

        Livewire::actingAs($admin)
            ->test(SchedulePeriodsIndex::class)
            ->call('create')
            ->set('name', 'Overlapping period')
            ->set('startsAt', '08:30')
            ->set('endsAt', '09:10')
            ->set('sequence', '2')
            ->call('save')
            ->assertHasErrors(['startsAt']);
    }

    public function test_manual_entries_reject_teacher_conflicts_and_allow_a_free_slot(): void
    {
        [$admin, , $year, $term, $teacher, $firstClassSubject, $secondClassSubject] = $this->timetableSetup();
        $period = SchedulePeriod::query()->firstOrFail();
        $timetable = Timetable::create([
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'name' => 'Term One Timetable',
            'status' => 'draft',
        ]);
        TimetableEntry::create([
            'timetable_id' => $timetable->id,
            'school_class_id' => $firstClassSubject->school_class_id,
            'class_subject_id' => $firstClassSubject->id,
            'teacher_id' => $teacher->id,
            'schedule_period_id' => $period->id,
            'day_of_week' => 1,
        ]);

        Livewire::actingAs($admin)
            ->test(TimetableEntriesIndex::class, ['timetable' => $timetable])
            ->assertSet('viewMode', 'calendar')
            ->assertSee('Weekly calendar')
            ->call('create')
            ->set('classSubjectId', (string) $secondClassSubject->id)
            ->set('periodId', (string) $period->id)
            ->set('dayOfWeek', '1')
            ->call('save')
            ->assertHasErrors(['periodId'])
            ->set('dayOfWeek', '2')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('timetable_entries', [
            'timetable_id' => $timetable->id,
            'class_subject_id' => $secondClassSubject->id,
            'teacher_id' => $teacher->id,
            'schedule_period_id' => $period->id,
            'day_of_week' => 2,
        ]);
    }

    public function test_generator_preserves_manual_entries_and_avoids_class_and_teacher_clashes(): void
    {
        [, , $year, $term, $teacher, $firstClassSubject] = $this->timetableSetup();
        $period = SchedulePeriod::query()->firstOrFail();
        $timetable = Timetable::create([
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'name' => 'Generated Timetable',
            'status' => 'draft',
        ]);
        $manualEntry = TimetableEntry::create([
            'timetable_id' => $timetable->id,
            'school_class_id' => $firstClassSubject->school_class_id,
            'class_subject_id' => $firstClassSubject->id,
            'teacher_id' => $teacher->id,
            'schedule_period_id' => $period->id,
            'day_of_week' => 1,
            'room' => 'Manual Room',
        ]);

        $result = app(TimetableGenerator::class)->generate($timetable, 2, false);

        $this->assertSame(3, $result['scheduled_count']);
        $this->assertSame([], $result['unscheduled']);
        $this->assertDatabaseHas('timetable_entries', ['id' => $manualEntry->id, 'room' => 'Manual Room']);
        $this->assertSame(4, $timetable->entries()->count());

        $entries = $timetable->entries()->get();
        $this->assertSame(
            $entries->count(),
            $entries->unique(fn (TimetableEntry $entry) => $entry->school_class_id.'-'.$entry->day_of_week.'-'.$entry->schedule_period_id)->count(),
        );
        $this->assertSame(
            $entries->whereNotNull('teacher_id')->count(),
            $entries->whereNotNull('teacher_id')->unique(fn (TimetableEntry $entry) => $entry->teacher_id.'-'.$entry->day_of_week.'-'.$entry->schedule_period_id)->count(),
        );
    }

    public function test_published_timetable_is_available_to_the_assigned_teacher_student_and_parent(): void
    {
        [, $school, $year, $term, $teacher, $classSubject] = $this->timetableSetup();
        $teacherUser = User::factory()->create();
        Role::create(['name' => 'teacher']);
        $teacherUser->assignRole('teacher');
        $teacher->update(['user_id' => $teacherUser->id]);

        $studentUser = User::factory()->create();
        Role::create(['name' => 'student']);
        $studentUser->assignRole('student');
        $student = Student::create([
            'user_id' => $studentUser->id,
            'school_id' => $school->id,
            'student_id' => 'STU-TT-001',
            'admission_number' => 'ADM-TT-001',
            'first_name' => 'Kofi',
            'last_name' => 'Asare',
            'date_of_birth' => '2016-01-01',
            'gender' => 'male',
            'admission_date' => '2026-09-01',
            'status' => 'active',
        ]);
        ClassEnrollment::create([
            'school_class_id' => $classSubject->school_class_id,
            'student_id' => $student->id,
            'enrolled_at' => '2026-09-01',
            'status' => 'active',
        ]);

        $parentUser = User::factory()->create();
        Role::create(['name' => 'parent']);
        $parentUser->assignRole('parent');
        $parent = ParentGuardian::create([
            'user_id' => $parentUser->id,
            'school_id' => $school->id,
            'first_name' => 'Akosua',
            'last_name' => 'Asare',
        ]);
        $parent->students()->attach($student->id, ['relationship' => 'mother', 'is_primary_contact' => true]);

        $timetable = Timetable::create([
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'name' => 'Published Timetable',
            'status' => 'published',
        ]);
        TimetableEntry::create([
            'timetable_id' => $timetable->id,
            'school_class_id' => $classSubject->school_class_id,
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $teacher->id,
            'schedule_period_id' => SchedulePeriod::query()->value('id'),
            'day_of_week' => 1,
            'room' => 'Room 4',
        ]);

        Livewire::actingAs($teacherUser)->test(TeacherTimetableIndex::class)->assertSee('Weekly calendar')->assertSee('Mathematics')->assertSee('Room 4')->call('showList')->assertSet('viewMode', 'list');
        Livewire::actingAs($studentUser)->test(StudentTimetableIndex::class)->assertSee('Weekly calendar')->assertSee('Mathematics')->assertSee('Room 4');
        Livewire::actingAs($parentUser)->test(ParentTimetableIndex::class)->assertSee('Weekly calendar')->assertSee('Mathematics')->assertSee('Room 4');
    }

    private function schoolAdminAndSchool(): array
    {
        Role::create(['name' => 'school_admin']);
        $admin = User::factory()->create();
        $admin->assignRole('school_admin');
        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);

        return [$admin, $school];
    }

    private function timetableSetup(): array
    {
        [$admin, $school] = $this->schoolAdminAndSchool();
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026/2027',
            'starts_at' => '2026-09-01',
            'ends_at' => '2027-07-31',
            'is_active' => true,
        ]);
        $term = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'sequence' => 1,
            'starts_at' => '2026-09-01',
            'ends_at' => '2026-12-18',
            'is_active' => true,
        ]);
        $teacher = Teacher::create([
            'school_id' => $school->id,
            'employee_id' => 'TCH-001',
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'status' => 'active',
        ]);
        $firstClass = SchoolClass::create(['academic_year_id' => $year->id, 'name' => 'Basic 4', 'status' => 'active']);
        $secondClass = SchoolClass::create(['academic_year_id' => $year->id, 'name' => 'Basic 5', 'status' => 'active']);
        $mathematics = Subject::create(['school_id' => $school->id, 'name' => 'Mathematics', 'code' => 'MATH', 'is_active' => true]);
        $science = Subject::create(['school_id' => $school->id, 'name' => 'Science', 'code' => 'SCI', 'is_active' => true]);
        $firstClassSubject = ClassSubject::create(['school_class_id' => $firstClass->id, 'subject_id' => $mathematics->id, 'teacher_id' => $teacher->id]);
        $secondClassSubject = ClassSubject::create(['school_class_id' => $secondClass->id, 'subject_id' => $science->id, 'teacher_id' => $teacher->id]);
        SchedulePeriod::create(['school_id' => $school->id, 'name' => 'Period 1', 'starts_at' => '08:00', 'ends_at' => '08:45', 'sequence' => 1]);
        SchedulePeriod::create(['school_id' => $school->id, 'name' => 'Period 2', 'starts_at' => '08:45', 'ends_at' => '09:30', 'sequence' => 2]);

        return [$admin, $school, $year, $term, $teacher, $firstClassSubject, $secondClassSubject];
    }
}
