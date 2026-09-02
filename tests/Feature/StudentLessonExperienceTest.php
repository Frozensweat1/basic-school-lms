<?php

namespace Tests\Feature;

use App\Livewire\LMS\Lessons\Student\Index;
use App\Livewire\LMS\Lessons\Student\Show;
use App\Models\AcademicYear;
use App\Models\ClassEnrollment;
use App\Models\ClassSubject;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentLessonExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'student']);
    }

    public function test_student_lesson_index_is_scoped_and_can_be_searched_and_filtered_by_subject_and_topic(): void
    {
        $setup = $this->curriculumSetup();

        $fractions = $this->createLesson($setup['fractionsTopic'], $setup['teacher'], 'Equivalent fractions', 1);
        $geometry = $this->createLesson($setup['geometryTopic'], $setup['teacher'], 'Lines and angles', 1);
        $grammar = $this->createLesson($setup['grammarTopic'], $setup['teacher'], 'Grammar foundations', 1);
        $draft = $this->createLesson($setup['fractionsTopic'], $setup['teacher'], 'Unpublished fractions', 2, 'draft');
        $deleted = $this->createLesson($setup['fractionsTopic'], $setup['teacher'], 'Archived fractions', 3);
        $deleted->delete();

        $otherClass = SchoolClass::create([
            'academic_year_id' => $setup['academicYear']->id,
            'name' => 'Basic 5',
            'code' => 'B5',
            'status' => 'active',
        ]);
        $otherClassSubject = ClassSubject::create([
            'school_class_id' => $otherClass->id,
            'subject_id' => $setup['mathematics']->id,
            'teacher_id' => $setup['teacher']->id,
        ]);
        $otherClassTopic = Topic::create([
            'class_subject_id' => $otherClassSubject->id,
            'title' => 'Algebra',
            'sequence' => 1,
        ]);
        $otherClassLesson = $this->createLesson($otherClassTopic, $setup['teacher'], 'Other class algebra', 1);

        $otherSchool = School::create(['name' => 'Other Academy', 'code' => 'OTHER']);
        $otherYear = AcademicYear::create([
            'school_id' => $otherSchool->id,
            'name' => '2026/2027',
            'starts_at' => '2026-09-01',
            'ends_at' => '2027-07-31',
            'is_active' => true,
        ]);
        $otherSchoolClass = SchoolClass::create([
            'academic_year_id' => $otherYear->id,
            'name' => 'Basic 4',
            'code' => 'OS-B4',
            'status' => 'active',
        ]);
        $otherSubject = Subject::create([
            'school_id' => $otherSchool->id,
            'name' => 'Mathematics',
            'code' => 'OTHER-MATH',
        ]);
        $otherTeacher = Teacher::create([
            'school_id' => $otherSchool->id,
            'employee_id' => 'OTHER-T-001',
            'first_name' => 'Other',
            'last_name' => 'Teacher',
        ]);
        $otherSchoolClassSubject = ClassSubject::create([
            'school_class_id' => $otherSchoolClass->id,
            'subject_id' => $otherSubject->id,
            'teacher_id' => $otherTeacher->id,
        ]);
        $otherSchoolTopic = Topic::create([
            'class_subject_id' => $otherSchoolClassSubject->id,
            'title' => 'Numbers',
            'sequence' => 1,
        ]);
        $otherSchoolLesson = $this->createLesson($otherSchoolTopic, $otherTeacher, 'Other school numbers', 1);

        $component = Livewire::actingAs($setup['user'])
            ->test(Index::class)
            ->assertSee($fractions->title)
            ->assertSee($geometry->title)
            ->assertSee($grammar->title)
            ->assertDontSee($draft->title)
            ->assertDontSee($deleted->title)
            ->assertDontSee($otherClassLesson->title)
            ->assertDontSee($otherSchoolLesson->title)
            ->set('filterSubjectId', (string) $setup['mathematics']->id)
            ->assertSee($fractions->title)
            ->assertSee($geometry->title)
            ->assertDontSee($grammar->title)
            ->set('filterTopicId', (string) $setup['fractionsTopic']->id)
            ->assertSee($fractions->title)
            ->assertDontSee($geometry->title)
            ->call('loadMore')
            ->assertSet('visibleLessons', 12)
            ->set('filterSubjectId', (string) $setup['english']->id)
            ->assertSet('visibleLessons', 12)
            ->assertSee($grammar->title)
            ->assertDontSee($fractions->title)
            ->set('filterSubjectId', '')
            ->set('search', 'Grammar foundations')
            ->assertSet('visibleLessons', 12)
            ->assertSee($grammar->title)
            ->assertDontSee($fractions->title)
            ->assertDontSee($geometry->title);

        $component->assertHasNoErrors();
    }

    public function test_student_can_infinitely_load_more_lessons_and_filter_changes_reset_the_window(): void
    {
        $setup = $this->curriculumSetup();

        foreach (range(1, 13) as $sequence) {
            $this->createLesson(
                $setup['fractionsTopic'],
                $setup['teacher'],
                sprintf('Sequence lesson %02d', $sequence),
                $sequence,
            );
        }

        $component = Livewire::actingAs($setup['user'])
            ->test(Index::class)
            ->assertSet('visibleLessons', 12)
            ->assertSee('Sequence lesson 01')
            ->assertSee('Sequence lesson 12')
            ->assertDontSee('Sequence lesson 13')
            ->assertViewHas('hasMore', true)
            ->assertSee('wire:intersect.once.margin.300px="loadMore"', false)
            ->call('loadMore')
            ->assertSet('visibleLessons', 13)
            ->assertSee('Sequence lesson 13')
            ->assertViewHas('hasMore', false)
            ->set('filterSubjectId', (string) $setup['english']->id)
            ->assertSet('visibleLessons', 12)
            ->assertViewHas('hasMore', false)
            ->assertDontSee('Sequence lesson 01');

        $component->assertHasNoErrors();
    }

    public function test_opening_a_lesson_automatically_completes_it_once(): void
    {
        $setup = $this->curriculumSetup();
        $lesson = $this->createLesson(
            $setup['fractionsTopic'],
            $setup['teacher'],
            'Reading fractions',
            1,
            content: '<p>Read and compare the fractions.</p>',
        );

        Carbon::setTestNow('2026-10-01 09:15:00');

        try {
            $this->actingAs($setup['user'])
                ->get(route('lms.lessons.student.show', $lesson))
                ->assertOk()
                ->assertSee('Reading fractions')
                ->assertSee('Read and compare the fractions.');

            $progress = LessonProgress::query()
                ->where('lesson_id', $lesson->id)
                ->where('student_id', $setup['student']->id)
                ->firstOrFail();

            $this->assertSame('2026-10-01 09:15:00', $progress->completed_at->format('Y-m-d H:i:s'));

            Carbon::setTestNow('2026-10-02 11:30:00');

            $this->actingAs($setup['user'])
                ->get(route('lms.lessons.student.show', $lesson))
                ->assertOk();

            $this->assertDatabaseCount('lesson_progress', 1);
            $this->assertSame(
                '2026-10-01 09:15:00',
                $progress->fresh()->completed_at->format('Y-m-d H:i:s'),
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_next_lesson_follows_topic_then_lesson_sequence_and_skips_unavailable_lessons(): void
    {
        $setup = $this->curriculumSetup();

        $first = $this->createLesson($setup['fractionsTopic'], $setup['teacher'], 'Fraction basics', 1);
        $current = $this->createLesson($setup['fractionsTopic'], $setup['teacher'], 'Fraction comparison', 2);
        $this->createLesson($setup['geometryTopic'], $setup['teacher'], 'Draft geometry', 0, 'draft');
        $expectedNext = $this->createLesson($setup['geometryTopic'], $setup['teacher'], 'Geometry basics', 1);
        $otherSubjectLesson = $this->createLesson($setup['grammarTopic'], $setup['teacher'], 'English comes later', 1);

        Livewire::actingAs($setup['user'])
            ->test(Show::class, ['lesson' => $first])
            ->assertViewHas('nextLesson', fn (?Lesson $nextLesson): bool => $nextLesson?->is($current) === true)
            ->assertSee(route('lms.lessons.student.show', $current), false);

        Livewire::actingAs($setup['user'])
            ->test(Show::class, ['lesson' => $current])
            ->assertViewHas('nextLesson', fn (?Lesson $nextLesson): bool => $nextLesson?->is($expectedNext) === true)
            ->assertSee(route('lms.lessons.student.show', $expectedNext), false)
            ->assertDontSee(route('lms.lessons.student.show', $otherSubjectLesson), false);

        Livewire::actingAs($setup['user'])
            ->test(Show::class, ['lesson' => $expectedNext])
            ->assertViewHas('nextLesson', null);
    }

    public function test_only_the_enrolled_student_can_open_a_published_lesson(): void
    {
        $setup = $this->curriculumSetup();
        $availableLesson = $this->createLesson($setup['fractionsTopic'], $setup['teacher'], 'Available lesson', 1);
        $draftLesson = $this->createLesson($setup['fractionsTopic'], $setup['teacher'], 'Private draft lesson', 2, 'draft');

        $otherClass = SchoolClass::create([
            'academic_year_id' => $setup['academicYear']->id,
            'name' => 'Basic 6',
            'code' => 'B6',
            'status' => 'active',
        ]);
        $otherClassSubject = ClassSubject::create([
            'school_class_id' => $otherClass->id,
            'subject_id' => $setup['mathematics']->id,
            'teacher_id' => $setup['teacher']->id,
        ]);
        $otherTopic = Topic::create([
            'class_subject_id' => $otherClassSubject->id,
            'title' => 'Advanced numbers',
            'sequence' => 1,
        ]);
        $otherClassLesson = $this->createLesson($otherTopic, $setup['teacher'], 'Other class lesson', 1);

        $otherUser = User::factory()->create();
        $otherUser->assignRole('student');
        $otherStudent = $this->createStudent($otherUser, $setup['school'], 'STU-OTHER');
        ClassEnrollment::create([
            'school_class_id' => $otherClass->id,
            'student_id' => $otherStudent->id,
            'enrolled_at' => '2026-09-01',
            'status' => ClassEnrollment::STATUS_ACTIVE,
        ]);

        $this->get(route('lms.lessons.student.show', $availableLesson))
            ->assertRedirect(route('login'));

        $this->actingAs($setup['user'])
            ->get(route('lms.lessons.student.show', $draftLesson))
            ->assertNotFound();

        $this->actingAs($setup['user'])
            ->get(route('lms.lessons.student.show', $otherClassLesson))
            ->assertNotFound();

        $this->actingAs($otherUser)
            ->get(route('lms.lessons.student.show', $availableLesson))
            ->assertNotFound();

        $userWithoutStudentProfile = User::factory()->create();
        $userWithoutStudentProfile->assignRole('student');

        $this->actingAs($userWithoutStudentProfile)
            ->get(route('lms.lessons.student.show', $availableLesson))
            ->assertForbidden();

        $this->assertDatabaseMissing('lesson_progress', ['lesson_id' => $draftLesson->id]);
        $this->assertDatabaseMissing('lesson_progress', ['lesson_id' => $otherClassLesson->id]);
        $this->assertDatabaseMissing('lesson_progress', ['lesson_id' => $availableLesson->id]);
    }

    /**
     * @return array{
     *     user: User,
     *     student: Student,
     *     school: School,
     *     academicYear: AcademicYear,
     *     schoolClass: SchoolClass,
     *     teacher: Teacher,
     *     mathematics: Subject,
     *     english: Subject,
     *     fractionsTopic: Topic,
     *     geometryTopic: Topic,
     *     grammarTopic: Topic
     * }
     */
    private function curriculumSetup(): array
    {
        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);
        $academicYear = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026/2027',
            'starts_at' => '2026-09-01',
            'ends_at' => '2027-07-31',
            'is_active' => true,
        ]);
        $schoolClass = SchoolClass::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Basic 4',
            'code' => 'B4',
            'status' => 'active',
        ]);
        $teacher = Teacher::create([
            'school_id' => $school->id,
            'employee_id' => 'T-001',
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
        ]);
        $mathematics = Subject::create([
            'school_id' => $school->id,
            'name' => 'Mathematics',
            'code' => 'MATH',
        ]);
        $english = Subject::create([
            'school_id' => $school->id,
            'name' => 'English Language',
            'code' => 'ENG',
        ]);
        $mathematicsClassSubject = ClassSubject::create([
            'school_class_id' => $schoolClass->id,
            'subject_id' => $mathematics->id,
            'teacher_id' => $teacher->id,
        ]);
        $englishClassSubject = ClassSubject::create([
            'school_class_id' => $schoolClass->id,
            'subject_id' => $english->id,
            'teacher_id' => $teacher->id,
        ]);
        $fractionsTopic = Topic::create([
            'class_subject_id' => $mathematicsClassSubject->id,
            'title' => 'Fractions',
            'sequence' => 1,
        ]);
        $geometryTopic = Topic::create([
            'class_subject_id' => $mathematicsClassSubject->id,
            'title' => 'Geometry',
            'sequence' => 2,
        ]);
        $grammarTopic = Topic::create([
            'class_subject_id' => $englishClassSubject->id,
            'title' => 'Grammar',
            'sequence' => 1,
        ]);

        $user = User::factory()->create();
        $user->assignRole('student');
        $student = $this->createStudent($user, $school, 'STU-001');

        ClassEnrollment::create([
            'school_class_id' => $schoolClass->id,
            'student_id' => $student->id,
            'enrolled_at' => '2026-09-01',
            'status' => ClassEnrollment::STATUS_ACTIVE,
        ]);

        return compact(
            'user',
            'student',
            'school',
            'academicYear',
            'schoolClass',
            'teacher',
            'mathematics',
            'english',
            'fractionsTopic',
            'geometryTopic',
            'grammarTopic',
        );
    }

    private function createStudent(User $user, School $school, string $identifier): Student
    {
        return Student::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'student_id' => $identifier,
            'admission_number' => 'ADM-'.$identifier,
            'first_name' => 'Kojo',
            'last_name' => 'Owusu',
            'date_of_birth' => '2015-05-10',
            'gender' => 'male',
            'admission_date' => '2026-09-01',
            'status' => 'active',
        ]);
    }

    private function createLesson(
        Topic $topic,
        Teacher $teacher,
        string $title,
        int $sequence,
        string $status = 'published',
        ?string $content = null,
    ): Lesson {
        return Lesson::create([
            'topic_id' => $topic->id,
            'teacher_id' => $teacher->id,
            'title' => $title,
            'summary' => 'Summary for '.$title,
            'content' => $content,
            'sequence' => $sequence,
            'status' => $status,
            'published_at' => $status === 'published' ? now() : null,
        ]);
    }
}
