<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\AttendanceRecord;
use App\Models\ClassEnrollment;
use App\Models\ClassSubject;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Support\DashboardChartData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardChartsTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_role_dashboard_renders_relevant_charts_with_normalized_data(): void
    {
        foreach (['school_admin', 'teacher', 'student', 'parent'] as $roleName) {
            Role::findOrCreate($roleName);
        }

        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);
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
        $secondTerm = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Term 2',
            'sequence' => 2,
            'starts_at' => '2027-01-05',
            'ends_at' => '2027-04-09',
            'is_active' => false,
        ]);
        $class = SchoolClass::create([
            'academic_year_id' => $year->id,
            'name' => 'Basic 5',
            'status' => 'active',
        ]);
        $subject = Subject::create([
            'school_id' => $school->id,
            'name' => 'Mathematics',
            'code' => 'MATH',
            'is_active' => true,
        ]);

        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('teacher');
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'school_id' => $school->id,
            'employee_id' => 'TCH-CHART-1',
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'status' => 'active',
        ]);
        $classSubject = ClassSubject::create([
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        $studentUser = User::factory()->create();
        $studentUser->assignRole('student');
        $student = Student::create([
            'user_id' => $studentUser->id,
            'school_id' => $school->id,
            'student_id' => 'STU-CHART-1',
            'admission_number' => 'ADM-CHART-1',
            'first_name' => 'Kojo',
            'last_name' => 'Owusu',
            'date_of_birth' => '2015-01-01',
            'gender' => 'male',
            'admission_date' => '2026-09-01',
            'status' => 'active',
        ]);
        ClassEnrollment::create([
            'school_class_id' => $class->id,
            'student_id' => $student->id,
            'enrolled_at' => '2026-09-01',
            'status' => 'active',
        ]);

        $parentUser = User::factory()->create();
        $parentUser->assignRole('parent');
        $parent = ParentGuardian::create([
            'user_id' => $parentUser->id,
            'school_id' => $school->id,
            'first_name' => 'Adwoa',
            'last_name' => 'Owusu',
        ]);
        $parent->students()->attach($student->id, ['relationship' => 'mother', 'is_primary_contact' => true]);

        $admin = User::factory()->create();
        $admin->assignRole('school_admin');
        $assessment = Assessment::create([
            'class_subject_id' => $classSubject->id,
            'term_id' => $term->id,
            'teacher_id' => $teacher->id,
            'title' => 'Fractions test',
            'max_score' => 50,
            'assessed_at' => '2026-10-01',
            'status' => 'published',
        ]);
        AssessmentScore::create([
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
            'score' => 40,
        ]);
        $secondTermAssessment = Assessment::create([
            'class_subject_id' => $classSubject->id,
            'term_id' => $secondTerm->id,
            'teacher_id' => $teacher->id,
            'title' => 'Decimals test',
            'max_score' => 50,
            'assessed_at' => '2027-02-01',
            'status' => 'published',
        ]);
        AssessmentScore::create([
            'assessment_id' => $secondTermAssessment->id,
            'student_id' => $student->id,
            'score' => 30,
        ]);

        $previousYear = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2025/2026',
            'starts_at' => '2025-09-01',
            'ends_at' => '2026-07-31',
            'is_active' => false,
        ]);
        $previousTerm = Term::create([
            'academic_year_id' => $previousYear->id,
            'name' => 'Term 1',
            'sequence' => 1,
            'starts_at' => '2025-09-01',
            'ends_at' => '2025-12-18',
            'is_active' => false,
        ]);
        $previousClass = SchoolClass::create([
            'academic_year_id' => $previousYear->id,
            'name' => 'Basic 4',
            'status' => 'closed',
        ]);
        $previousClassSubject = ClassSubject::create([
            'school_class_id' => $previousClass->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);
        $previousAssessment = Assessment::create([
            'class_subject_id' => $previousClassSubject->id,
            'term_id' => $previousTerm->id,
            'teacher_id' => $teacher->id,
            'title' => 'Previous year test',
            'max_score' => 50,
            'assessed_at' => '2025-10-01',
            'status' => 'published',
        ]);
        AssessmentScore::create([
            'assessment_id' => $previousAssessment->id,
            'student_id' => $student->id,
            'score' => 25,
        ]);
        $draftAssessment = Assessment::create([
            'class_subject_id' => $classSubject->id,
            'term_id' => $term->id,
            'teacher_id' => $teacher->id,
            'title' => 'Unpublished test',
            'max_score' => 50,
            'assessed_at' => '2026-11-01',
            'status' => 'draft',
        ]);
        AssessmentScore::create([
            'assessment_id' => $draftAssessment->id,
            'student_id' => $student->id,
            'score' => 50,
        ]);
        AttendanceRecord::create([
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'school_class_id' => $class->id,
            'student_id' => $student->id,
            'attendance_date' => '2026-10-01',
            'status' => 'present',
            'marked_by' => $teacherUser->id,
        ]);

        $performance = app(DashboardChartData::class)->studentPerformance($student->id);
        $this->assertSame([63.3], $performance['data']['datasets'][0]['data']);
        $overview = app(DashboardChartData::class)->performanceOverview(
            AssessmentScore::query()
                ->where('student_id', $student->id)
                ->whereHas('assessment', fn ($query) => $query->where('status', 'published')),
            $school->id,
        );
        $this->assertSame([80.0, 60.0], $overview['termlyChart']['data']['datasets'][0]['data']);
        $this->assertSame([50.0, 70.0], $overview['academicYearChart']['data']['datasets'][0]['data']);
        $this->assertSame([80.0], $overview['subjectChart']['data']['datasets'][0]['data']);
        $this->assertSame([70.0], $overview['subjectChart']['data']['datasets'][1]['data']);

        $this->actingAs($admin)->get(route('lms.dashboard.admin'))
            ->assertOk()
            ->assertSee('63.3%')
            ->assertSee('admin-enrollment-chart')
            ->assertSee('admin-attendance-chart')
            ->assertSee('admin-termly-performance-chart')
            ->assertSee('admin-academic-year-performance-chart')
            ->assertSee('admin-subject-period-performance-chart');

        $this->actingAs($teacherUser)->get(route('lms.dashboard.teacher'))
            ->assertOk()
            ->assertSee('teacher-workload-chart')
            ->assertSee('teacher-performance-chart')
            ->assertSee('teacher-termly-performance-chart')
            ->assertSee('teacher-academic-year-performance-chart')
            ->assertSee('teacher-subject-period-performance-chart');

        $this->actingAs($studentUser)->get(route('lms.dashboard.student'))
            ->assertOk()
            ->assertSee('student-performance-chart')
            ->assertSee('student-attendance-chart')
            ->assertSee('student-termly-performance-chart')
            ->assertSee('student-academic-year-performance-chart')
            ->assertSee('student-subject-period-performance-chart');

        $this->actingAs($parentUser)->get(route('lms.dashboard.parent'))
            ->assertOk()
            ->assertSee('63.3%')
            ->assertSee('parent-performance-chart')
            ->assertSee('parent-attendance-chart')
            ->assertSee('parent-termly-performance-chart')
            ->assertSee('parent-academic-year-performance-chart')
            ->assertSee('parent-subject-period-performance-chart');
    }
}
