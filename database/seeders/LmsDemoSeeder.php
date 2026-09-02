<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Assessment;
use App\Models\AssessmentComponent;
use App\Models\AssessmentScore;
use App\Models\Assignment;
use App\Models\AssignmentAttachment;
use App\Models\AssignmentSubmission;
use App\Models\ClassEnrollment;
use App\Models\ClassSubject;
use App\Models\Examination;
use App\Models\GradingScale;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonResource;
use App\Models\ParentGuardian;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\ReportCard;
use App\Models\SchedulePeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Stream;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectResult;
use App\Models\SubmissionAttachment;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\LmsNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LmsDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Keep this seeder safe to run on its own as well as through DatabaseSeeder.
        $this->call([RoleSeeder::class, SchoolSetupSeeder::class]);
        $school = School::firstOrCreate(['code' => 'BRIGHTSTAR'], ['name' => 'BrightStar Academy']);
        foreach ([
            'timezone' => 'Africa/Accra', 'week_starts_on' => 'monday', 'notifications_enabled' => true, 'late_submissions_enabled' => true,
            'brand_primary' => '#1e3a8a', 'brand_secondary' => '#0f172a', 'brand_accent' => '#f59e0b',
            'hero_title' => 'Where curious minds grow into confident leaders.', 'hero_subtitle' => 'Strong academics, creative learning, and a caring community help every child thrive.',
            'footer_text' => 'A caring learning community dedicated to academic excellence, creativity, and character.',
        ] as $key => $value) {
            SchoolSetting::firstOrCreate(['school_id' => $school->id, 'key' => $key], ['value' => ['value' => $value]]);
        }
        $year = AcademicYear::where('school_id', $school->id)->where('name', '2026/2027')->firstOrFail();
        $term = $year->terms()->where('name', 'Term 1')->firstOrFail();
        $class = SchoolClass::where('academic_year_id', $year->id)->where('name', 'Basic 4')->firstOrFail();
        $subject = Subject::where('school_id', $school->id)->where('code', 'MATH')->firstOrFail();

        $teacherUser = $this->syncDemoUser('Ama Mensah', 'ama.mensah@brightstar.test', 'teacher');
        $teacher = $this->syncDemoTeacher($teacherUser, 'T-001', ['school_id' => $school->id, 'first_name' => 'Ama', 'last_name' => 'Mensah', 'email' => $teacherUser->email, 'phone' => '0200000001', 'employment_date' => '2024-09-01', 'status' => 'active']);
        for ($number = 1; $number <= 10; $number++) {
            $admin = User::firstOrCreate(['email' => "admin{$number}@brightstar.test"], ['name' => "Demo Administrator {$number}", 'password' => Hash::make('password')]);
            $admin->syncRoles('super_admin');
            $teacherAccount = $this->syncDemoUser("Demo Teacher {$number}", "teacher{$number}@brightstar.test", 'teacher');
            $this->syncDemoTeacher($teacherAccount, sprintf('T-%03d', $number + 1), ['school_id' => $school->id, 'first_name' => 'Demo', 'last_name' => "Teacher {$number}", 'email' => $teacherAccount->email, 'phone' => sprintf('0200001%03d', $number), 'employment_date' => '2024-09-01', 'status' => 'active']);
        }

        $studentUser = $this->syncDemoUser('Kojo Owusu', 'kojo.owusu@brightstar.test', 'student');
        $student = $this->syncDemoStudent($studentUser, 'STU-2026-001', ['school_id' => $school->id, 'admission_number' => 'ADM-2026-001', 'first_name' => 'Kojo', 'last_name' => 'Owusu', 'date_of_birth' => '2015-05-10', 'gender' => 'male', 'admission_date' => '2026-09-01', 'status' => 'active']);
        ClassEnrollment::firstOrCreate(['school_class_id' => $class->id, 'student_id' => $student->id], ['enrolled_at' => '2026-09-01', 'status' => 'active']);

        for ($number = 2; $number <= 10; $number++) {
            $user = $this->syncDemoUser("Demo Student {$number}", "student{$number}@brightstar.test", 'student');
            $demoStudent = $this->syncDemoStudent($user, sprintf('STU-2026-%03d', $number), ['school_id' => $school->id, 'admission_number' => sprintf('ADM-2026-%03d', $number), 'first_name' => 'Demo', 'last_name' => "Student {$number}", 'date_of_birth' => '2015-05-10', 'gender' => $number % 2 ? 'male' : 'female', 'admission_date' => '2026-09-01', 'status' => 'active']);
            ClassEnrollment::firstOrCreate(['school_class_id' => $class->id, 'student_id' => $demoStudent->id], ['enrolled_at' => '2026-09-01', 'status' => 'active']);
        }

        $parentUser = $this->syncDemoUser('Adwoa Owusu', 'adwoa.owusu@brightstar.test', 'parent');
        $parent = $this->syncDemoParent($parentUser, ['school_id' => $school->id, 'first_name' => 'Adwoa', 'last_name' => 'Owusu', 'phone' => '0200000002', 'address' => 'Accra, Ghana']);
        $parent->students()->syncWithoutDetaching([$student->id => ['relationship' => 'Mother', 'is_primary_contact' => true]]);
        for ($number = 2; $number <= 10; $number++) {
            $parentAccount = $this->syncDemoUser("Demo Parent {$number}", "parent{$number}@brightstar.test", 'parent');
            $demoParent = $this->syncDemoParent($parentAccount, ['school_id' => $school->id, 'first_name' => 'Demo', 'last_name' => "Parent {$number}", 'phone' => sprintf('0200002%03d', $number), 'address' => 'Accra, Ghana']);
            $demoStudent = Student::where('student_id', sprintf('STU-2026-%03d', $number))->firstOrFail();
            $demoParent->students()->syncWithoutDetaching([$demoStudent->id => ['relationship' => 'Parent', 'is_primary_contact' => true]]);
        }

        $classSubject = ClassSubject::firstOrCreate(['school_class_id' => $class->id, 'subject_id' => $subject->id], ['teacher_id' => $teacher->id]);
        $topic = Topic::firstOrCreate(['class_subject_id' => $classSubject->id, 'title' => 'Fractions'], ['description' => 'Introduction to fractions and equivalent fractions.', 'sequence' => 1]);
        $lesson = Lesson::firstOrCreate(['topic_id' => $topic->id, 'title' => 'Adding fractions'], ['teacher_id' => $teacher->id, 'summary' => 'Add fractions with like denominators.', 'content' => '<p>Use visual models to add fractions with like denominators.</p>', 'objectives' => ['Identify numerators and denominators', 'Add fractions with like denominators'], 'sequence' => 1, 'status' => 'published', 'published_at' => now()]);

        Assignment::firstOrCreate(['class_subject_id' => $classSubject->id, 'title' => 'Fraction practice'], ['topic_id' => $topic->id, 'lesson_id' => $lesson->id, 'teacher_id' => $teacher->id, 'instructions' => '<p>Complete questions 1–10 in your exercise book.</p>', 'max_score' => 20, 'opens_at' => '2026-09-10 08:00:00', 'due_at' => '2026-09-17 17:00:00', 'allow_late_submission' => true, 'status' => 'published']);

        $quiz = Quiz::firstOrCreate(['class_subject_id' => $classSubject->id, 'title' => 'Fractions quiz'], ['topic_id' => $topic->id, 'lesson_id' => $lesson->id, 'teacher_id' => $teacher->id, 'instructions' => '<p>Answer each question carefully.</p>', 'time_limit_minutes' => 20, 'pass_mark' => 50, 'max_attempts' => 2, 'randomize_questions' => true, 'opens_at' => '2026-09-18 08:00:00', 'closes_at' => '2026-09-20 17:00:00', 'status' => 'published']);

        $question = Question::firstOrCreate(
            ['school_id' => $school->id, 'subject_id' => $subject->id, 'topic_id' => $topic->id, 'lesson_id' => $lesson->id, 'prompt' => 'What is one half of ten?'],
            ['created_by' => $teacherUser->id, 'type' => 'multiple_choice', 'grading_key' => ['answer' => '5'], 'max_score' => 1],
        );
        $question->options()->updateOrCreate(['label' => '4'], ['is_correct' => false, 'sequence' => 1]);
        $question->options()->updateOrCreate(['label' => '5'], ['is_correct' => true, 'sequence' => 2]);
        $quiz->quizQuestions()->firstOrCreate(['question_id' => $question->id], ['sequence' => 1]);

        $component = AssessmentComponent::firstOrCreate(['term_id' => $term->id, 'name' => 'Class Exercise'], ['weight' => 10, 'sequence' => 1]);
        AssessmentComponent::firstOrCreate(['term_id' => $term->id, 'name' => 'Assignment'], ['weight' => 10, 'sequence' => 2]);
        AssessmentComponent::firstOrCreate(['term_id' => $term->id, 'name' => 'Quiz'], ['weight' => 20, 'sequence' => 3]);
        AssessmentComponent::firstOrCreate(['term_id' => $term->id, 'name' => 'Examination'], ['weight' => 60, 'sequence' => 4]);
        Assessment::firstOrCreate(['class_subject_id' => $classSubject->id, 'term_id' => $term->id, 'title' => 'Fractions class exercise'], ['assessment_component_id' => $component->id, 'teacher_id' => $teacher->id, 'max_score' => 10, 'assessed_at' => '2026-09-12', 'status' => 'published']);

        foreach ([
            ['grade' => 'A', 'minimum' => 80, 'maximum' => 100, 'remark' => 'Excellent', 'sequence' => 1],
            ['grade' => 'B', 'minimum' => 70, 'maximum' => 79.99, 'remark' => 'Very Good', 'sequence' => 2],
            ['grade' => 'C', 'minimum' => 60, 'maximum' => 69.99, 'remark' => 'Good', 'sequence' => 3],
            ['grade' => 'D', 'minimum' => 50, 'maximum' => 59.99, 'remark' => 'Pass', 'sequence' => 4],
            ['grade' => 'F', 'minimum' => 0, 'maximum' => 49.99, 'remark' => 'Needs Improvement', 'sequence' => 5],
        ] as $scale) {
            GradingScale::firstOrCreate(['school_id' => $school->id, 'grade' => $scale['grade']], $scale);
        }

        $this->seedExpandedDemoData($school, $year, $term, $teacherUser);
    }

    private function syncDemoUser(string $name, string $email, string $role): User
    {
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->first();

        if ($user) {
            $user->forceFill(['name' => $name, 'email' => strtolower($email)])->save();
        } else {
            $user = User::create([
                'name' => $name,
                'email' => strtolower($email),
                'password' => Hash::make('password'),
            ]);
        }

        $user->syncRoles($role);

        return $user;
    }

    /** @param array<string, mixed> $attributes */
    private function syncDemoTeacher(User $user, string $employeeId, array $attributes): Teacher
    {
        $teacher = Teacher::withTrashed()
            ->where('user_id', $user->id)
            ->orWhere('employee_id', $employeeId)
            ->first() ?? new Teacher;

        $teacher->fill($attributes + ['user_id' => $user->id, 'employee_id' => $employeeId])->save();
        $teacher->restore();

        return $teacher;
    }

    /** @param array<string, mixed> $attributes */
    private function syncDemoStudent(User $user, string $studentId, array $attributes): Student
    {
        $student = Student::withTrashed()
            ->where('user_id', $user->id)
            ->orWhere('student_id', $studentId)
            ->first() ?? new Student;

        $student->fill($attributes + ['user_id' => $user->id, 'student_id' => $studentId])->save();
        $student->restore();

        return $student;
    }

    /** @param array<string, mixed> $attributes */
    private function syncDemoParent(User $user, array $attributes): ParentGuardian
    {
        $parent = ParentGuardian::withTrashed()
            ->where('user_id', $user->id)
            ->orWhereRaw('LOWER(email) = ?', [strtolower($user->email)])
            ->first() ?? new ParentGuardian;

        $parent->fill($attributes + ['user_id' => $user->id, 'email' => strtolower($user->email)])->save();
        $parent->restore();

        return $parent;
    }

    /** Seed the complete learning journey with repeatable demo records. */
    private function seedExpandedDemoData(School $school, AcademicYear $year, $term, User $author): void
    {
        $teachers = Teacher::where('school_id', $school->id)->where('status', 'active')->orderBy('id')->get();
        $students = Student::where('school_id', $school->id)->where('status', 'active')->orderBy('id')->get();
        $classes = SchoolClass::where('academic_year_id', $year->id)->where('status', 'active')->orderBy('id')->get();
        $subjects = Subject::where('school_id', $school->id)->where('is_active', true)->orderBy('id')->get();
        if ($teachers->isEmpty() || $students->isEmpty() || $classes->isEmpty() || $subjects->isEmpty()) {
            return;
        }

        foreach (['North Campus', 'South Campus', 'Online Learning'] as $streamName) {
            Stream::firstOrCreate(['school_id' => $school->id, 'name' => $streamName], ['is_active' => true]);
        }

        $classSubjects = collect();
        foreach ($classes as $classIndex => $class) {
            foreach ($subjects as $subjectIndex => $subject) {
                $teacher = $teachers[($classIndex + $subjectIndex) % $teachers->count()];
                $classSubject = ClassSubject::firstOrCreate(
                    ['school_class_id' => $class->id, 'subject_id' => $subject->id],
                    ['teacher_id' => $teacher->id]
                );
                if (! $classSubject->teacher_id) {
                    $classSubject->update(['teacher_id' => $teacher->id]);
                }
                $class->teachers()->syncWithoutDetaching([$teacher->id => ['role' => 'subject teacher']]);
                $classSubjects->push($classSubject->fresh());
            }
        }

        foreach ($classSubjects->take(10) as $index => $classSubject) {
            $teacherId = $classSubject->teacher_id ?: $teachers[$index % $teachers->count()]->id;
            foreach (range(1, 2) as $lessonNumber) {
                $topic = Topic::firstOrCreate(
                    ['class_subject_id' => $classSubject->id, 'title' => "Unit {$lessonNumber}: {$classSubject->subject->name}"],
                    ['description' => "Core {$classSubject->subject->name} learning activities for unit {$lessonNumber}.", 'sequence' => $lessonNumber]
                );
                $lesson = Lesson::firstOrCreate(
                    ['topic_id' => $topic->id, 'title' => "Lesson {$lessonNumber}: {$classSubject->subject->name} foundations"],
                    ['teacher_id' => $teacherId, 'summary' => 'A practical, learner-centred lesson.', 'content' => '<p>Explore the concept through guided practice and reflection.</p>', 'objectives' => ['Explain the core idea', 'Apply the idea to a real example'], 'sequence' => $lessonNumber, 'status' => 'published', 'published_at' => now()->subDays($lessonNumber)]
                );
                LessonResource::firstOrCreate(['lesson_id' => $lesson->id, 'title' => 'Lesson notes'], ['type' => 'link', 'disk' => 'public', 'external_url' => 'https://example.com/learning-resources', 'uploaded_by' => $teacherId ? $teachers->firstWhere('id', $teacherId)?->user_id : null]);
                foreach ($students as $studentIndex => $student) {
                    LessonProgress::firstOrCreate(['lesson_id' => $lesson->id, 'student_id' => $student->id], ['completed_at' => $studentIndex % 3 === 0 ? now()->subDays(2) : null]);
                }

                $assignment = Assignment::firstOrCreate(
                    ['class_subject_id' => $classSubject->id, 'title' => "Unit {$lessonNumber} practice"],
                    ['topic_id' => $topic->id, 'lesson_id' => $lesson->id, 'teacher_id' => $teacherId, 'instructions' => '<p>Complete the practice activity and show your working.</p>', 'max_score' => 20, 'opens_at' => now()->subDays(10), 'due_at' => now()->addDays(10), 'allow_late_submission' => true, 'status' => 'published']
                );
                AssignmentAttachment::firstOrCreate(['assignment_id' => $assignment->id, 'name' => 'Practice worksheet'], ['disk' => 'public', 'path' => 'demo/practice-worksheet.pdf', 'size' => 12000]);
                foreach ($students as $studentIndex => $student) {
                    $submission = AssignmentSubmission::firstOrCreate(['assignment_id' => $assignment->id, 'student_id' => $student->id], ['submission_text' => $studentIndex % 2 === 0 ? 'Completed practice with working shown.' : null, 'status' => $studentIndex % 2 === 0 ? 'submitted' : 'draft', 'submitted_at' => $studentIndex % 2 === 0 ? now()->subDays(1) : null, 'score' => $studentIndex % 2 === 0 ? 15 + ($studentIndex % 6) : null, 'feedback' => $studentIndex % 2 === 0 ? 'Good effort.' : null, 'graded_by' => $studentIndex % 2 === 0 ? $author->id : null, 'graded_at' => $studentIndex % 2 === 0 ? now() : null]);
                    if ($studentIndex % 2 === 0) {
                        SubmissionAttachment::firstOrCreate(['assignment_submission_id' => $submission->id, 'name' => 'Learner response'], ['disk' => 'public', 'path' => 'demo/learner-response.pdf', 'size' => 9000]);
                    }
                }

                $quiz = Quiz::firstOrCreate(
                    ['class_subject_id' => $classSubject->id, 'title' => "Unit {$lessonNumber} quiz"],
                    ['topic_id' => $topic->id, 'lesson_id' => $lesson->id, 'teacher_id' => $teacherId, 'instructions' => '<p>Answer all questions.</p>', 'time_limit_minutes' => 20, 'pass_mark' => 50, 'max_attempts' => 2, 'randomize_questions' => true, 'opens_at' => now()->subDays(5), 'closes_at' => now()->addDays(20), 'status' => 'published']
                );
                $questions = collect();
                foreach (range(1, 3) as $number) {
                    $question = Question::firstOrCreate(
                        [
                            'school_id' => $school->id,
                            'subject_id' => $classSubject->subject_id,
                            'topic_id' => $topic->id,
                            'lesson_id' => $lesson->id,
                            'prompt' => "Unit {$lessonNumber} question {$number}: which number comes after {$number}?",
                        ],
                        ['created_by' => $author->id, 'type' => 'multiple_choice', 'grading_key' => ['answer' => (string) ($number + 1)], 'max_score' => 1],
                    );
                    foreach ([$number, $number + 1, $number + 2] as $optionIndex => $option) {
                        $question->options()->firstOrCreate(['label' => (string) $option], ['is_correct' => $option === $number + 1, 'sequence' => $optionIndex + 1]);
                    }
                    $questions->push($question);
                }
                foreach ($questions->take(3) as $questionIndex => $question) {
                    $quiz->quizQuestions()->firstOrCreate(['question_id' => $question->id], ['sequence' => $questionIndex + 1]);
                }
                foreach ($students as $studentIndex => $student) {
                    $attempt = QuizAttempt::firstOrCreate(['quiz_id' => $quiz->id, 'student_id' => $student->id, 'attempt_number' => 1], ['started_at' => now()->subDays(2), 'submitted_at' => $studentIndex % 2 === 0 ? now()->subDays(2) : null, 'score' => $studentIndex % 2 === 0 ? 2 : null, 'status' => $studentIndex % 2 === 0 ? 'submitted' : 'in_progress']);
                    foreach ($quiz->quizQuestions as $quizQuestion) {
                        QuizAnswer::firstOrCreate(['quiz_attempt_id' => $attempt->id, 'question_id' => $quizQuestion->question_id], ['answer' => ['value' => (string) ($quizQuestion->question_id)], 'score' => $attempt->status === 'submitted' ? 1 : null, 'graded_by' => $attempt->status === 'submitted' ? $author->id : null, 'graded_at' => $attempt->status === 'submitted' ? now() : null]);
                    }
                }
            }
        }

        $component = AssessmentComponent::where('term_id', $term->id)->where('name', 'Class Exercise')->firstOrFail();
        foreach ($classSubjects->take(10) as $index => $classSubject) {
            $assessment = Assessment::firstOrCreate(['class_subject_id' => $classSubject->id, 'term_id' => $term->id, 'title' => 'Term 1 continuous assessment'], ['assessment_component_id' => $component->id, 'teacher_id' => $classSubject->teacher_id, 'max_score' => 100, 'assessed_at' => now()->subDays(4)->toDateString(), 'status' => 'published']);
            foreach ($students as $studentIndex => $student) {
                AssessmentScore::firstOrCreate(['assessment_id' => $assessment->id, 'student_id' => $student->id], ['score' => 55 + (($studentIndex + $index) % 40), 'comment' => 'Consistent class participation.']);
            }
            foreach ($students as $student) {
                $total = 55 + (($student->id + $index) % 40);
                $scale = GradingScale::where('school_id', $school->id)->where('minimum', '<=', $total)->where('maximum', '>=', $total)->first();
                SubjectResult::updateOrCreate(['student_id' => $student->id, 'class_subject_id' => $classSubject->id, 'term_id' => $term->id], ['total_score' => $total, 'grading_scale_id' => $scale?->id, 'grade' => $scale?->grade, 'teacher_comment' => 'Keep building on this progress.', 'status' => 'published']);
            }
        }

        foreach ($students as $student) {
            $enrollment = ClassEnrollment::where('student_id', $student->id)->where('status', 'active')->first();
            if (! $enrollment) {
                continue;
            }
            ReportCard::updateOrCreate(['student_id' => $student->id, 'term_id' => $term->id], ['academic_year_id' => $year->id, 'school_class_id' => $enrollment->school_class_id, 'teacher_comment' => 'A positive term of learning.', 'headteacher_comment' => 'Well done. Keep learning.', 'attendance_percentage' => 92, 'status' => 'published', 'generated_at' => now(), 'published_at' => now()]);
            foreach (range(1, 10) as $day) {
                $date = now()->subDays($day)->startOfDay()->toDateTimeString();
                DB::table('attendance_records')->updateOrInsert(['student_id' => $student->id, 'school_class_id' => $enrollment->school_class_id, 'attendance_date' => $date], ['academic_year_id' => $year->id, 'term_id' => $term->id, 'status' => $day % 9 === 0 ? 'late' : 'present', 'remarks' => null, 'marked_by' => $author->id, 'updated_at' => now(), 'created_at' => now()]);
            }
        }

        $periods = collect([
            ['name' => 'Period 1', 'starts_at' => '08:00', 'ends_at' => '08:45', 'sequence' => 1], ['name' => 'Period 2', 'starts_at' => '08:50', 'ends_at' => '09:35', 'sequence' => 2], ['name' => 'Period 3', 'starts_at' => '09:40', 'ends_at' => '10:25', 'sequence' => 3], ['name' => 'Period 4', 'starts_at' => '10:45', 'ends_at' => '11:30', 'sequence' => 4], ['name' => 'Period 5', 'starts_at' => '11:35', 'ends_at' => '12:20', 'sequence' => 5], ['name' => 'Period 6', 'starts_at' => '12:25', 'ends_at' => '13:10', 'sequence' => 6],
        ])->map(fn ($period) => SchedulePeriod::firstOrCreate(['school_id' => $school->id, 'name' => $period['name']], $period));
        $timetable = Timetable::firstOrCreate(['academic_year_id' => $year->id, 'term_id' => $term->id, 'name' => 'Term 1 master timetable'], ['status' => 'published']);
        foreach ($classes as $classIndex => $class) {
            foreach ($subjects as $subjectIndex => $subject) {
                $classSubject = $classSubjects->first(fn ($item) => $item->school_class_id === $class->id && $item->subject_id === $subject->id);
                if (! $classSubject) {
                    continue;
                }
                $period = $periods[$subjectIndex % $periods->count()];
                TimetableEntry::firstOrCreate(['timetable_id' => $timetable->id, 'school_class_id' => $class->id, 'day_of_week' => ($classIndex % 5) + 1, 'schedule_period_id' => $period->id], ['class_subject_id' => $classSubject->id, 'teacher_id' => $classSubject->teacher_id, 'room' => 'Room '.(($subjectIndex % 6) + 1)]);
            }
        }
        foreach ($classSubjects->take(10) as $index => $classSubject) {
            Examination::firstOrCreate(['school_id' => $school->id, 'academic_year_id' => $year->id, 'term_id' => $term->id, 'class_subject_id' => $classSubject->id, 'title' => 'Term 1 examination'], ['teacher_id' => $classSubject->teacher_id, 'description' => 'End of term examination.', 'exam_date' => now()->addDays(30 + $index)->toDateString(), 'duration_minutes' => 90, 'max_score' => 100, 'status' => 'scheduled']);
        }

        foreach (range(1, 10) as $number) {
            Announcement::firstOrCreate(['school_id' => $school->id, 'title' => "Demo school announcement {$number}"], ['created_by' => $author->id, 'content' => 'This is a seeded school update for demonstration purposes.', 'audience' => 'school', 'published_at' => now()->subDays($number)]);
        }
        foreach ($students->take(5) as $student) {
            if ($student->user && ! $student->user->notifications()->where('type', LmsNotification::class)->whereJsonContains('data->title', 'Welcome to the LMS')->exists()) {
                $student->user->notify(new LmsNotification('Welcome to the LMS', 'Your demo learning workspace is ready.', route('lms.dashboard.student')));
            }
        }
        if (! DB::table('audit_logs')->where('school_id', $school->id)->where('event', 'demo.seeded')->exists()) {
            foreach (range(1, 10) as $number) {
                DB::table('audit_logs')->insert(['user_id' => $author->id, 'school_id' => $school->id, 'event' => 'demo.seeded', 'old_values' => null, 'new_values' => json_encode(['record' => $number]), 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }
}
