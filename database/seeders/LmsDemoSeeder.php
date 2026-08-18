<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentComponent;
use App\Models\Assignment;
use App\Models\ClassEnrollment;
use App\Models\ClassSubject;
use App\Models\GradingScale;
use App\Models\Lesson;
use App\Models\ParentGuardian;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LmsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::firstOrCreate(['code' => 'BRIGHTSTAR'], ['name' => 'BrightStar Academy']);
        $year = AcademicYear::where('school_id', $school->id)->where('name', '2026/2027')->firstOrFail();
        $term = $year->terms()->where('name', 'Term 1')->firstOrFail();
        $class = SchoolClass::where('academic_year_id', $year->id)->where('name', 'Basic 4')->firstOrFail();
        $subject = Subject::where('school_id', $school->id)->where('code', 'MATH')->firstOrFail();

        $teacherUser = User::firstOrCreate(['email' => 'ama.mensah@brightstar.test'], ['name' => 'Ama Mensah', 'password' => Hash::make('password')]);
        $teacherUser->syncRoles('teacher');
        $teacher = Teacher::firstOrCreate(['employee_id' => 'T-001'], ['user_id' => $teacherUser->id, 'school_id' => $school->id, 'first_name' => 'Ama', 'last_name' => 'Mensah', 'email' => $teacherUser->email, 'phone' => '0200000001', 'employment_date' => '2024-09-01', 'status' => 'active']);
        for ($number = 1; $number <= 10; $number++) {
            $admin = User::firstOrCreate(['email' => "admin{$number}@brightstar.test"], ['name' => "Demo Administrator {$number}", 'password' => Hash::make('password')]);
            $admin->syncRoles('super_admin');
            $teacherAccount = User::firstOrCreate(['email' => "teacher{$number}@brightstar.test"], ['name' => "Demo Teacher {$number}", 'password' => Hash::make('password')]);
            $teacherAccount->syncRoles('teacher');
            Teacher::firstOrCreate(['employee_id' => sprintf('T-%03d', $number + 1)], ['user_id' => $teacherAccount->id, 'school_id' => $school->id, 'first_name' => 'Demo', 'last_name' => "Teacher {$number}", 'email' => $teacherAccount->email, 'phone' => sprintf('0200001%03d', $number), 'employment_date' => '2024-09-01', 'status' => 'active']);
        }

        $studentUser = User::firstOrCreate(['email' => 'kojo.owusu@brightstar.test'], ['name' => 'Kojo Owusu', 'password' => Hash::make('password')]);
        $studentUser->syncRoles('student');
        $student = Student::firstOrCreate(['student_id' => 'STU-2026-001'], ['user_id' => $studentUser->id, 'school_id' => $school->id, 'admission_number' => 'ADM-2026-001', 'first_name' => 'Kojo', 'last_name' => 'Owusu', 'date_of_birth' => '2015-05-10', 'gender' => 'male', 'admission_date' => '2026-09-01', 'status' => 'active']);
        ClassEnrollment::firstOrCreate(['school_class_id' => $class->id, 'student_id' => $student->id], ['enrolled_at' => '2026-09-01', 'status' => 'active']);

        for ($number = 2; $number <= 10; $number++) {
            $user = User::firstOrCreate(['email' => "student{$number}@brightstar.test"], ['name' => "Demo Student {$number}", 'password' => Hash::make('password')]);
            $user->syncRoles('student');
            $demoStudent = Student::firstOrCreate(['student_id' => sprintf('STU-2026-%03d', $number)], ['user_id' => $user->id, 'school_id' => $school->id, 'admission_number' => sprintf('ADM-2026-%03d', $number), 'first_name' => 'Demo', 'last_name' => "Student {$number}", 'date_of_birth' => '2015-05-10', 'gender' => $number % 2 ? 'male' : 'female', 'admission_date' => '2026-09-01', 'status' => 'active']);
            ClassEnrollment::firstOrCreate(['school_class_id' => $class->id, 'student_id' => $demoStudent->id], ['enrolled_at' => '2026-09-01', 'status' => 'active']);
        }

        $parentUser = User::firstOrCreate(['email' => 'adwoa.owusu@brightstar.test'], ['name' => 'Adwoa Owusu', 'password' => Hash::make('password')]);
        $parentUser->syncRoles('parent');
        $parent = ParentGuardian::firstOrCreate(['email' => $parentUser->email], ['user_id' => $parentUser->id, 'school_id' => $school->id, 'first_name' => 'Adwoa', 'last_name' => 'Owusu', 'phone' => '0200000002', 'address' => 'Accra, Ghana']);
        $parent->students()->syncWithoutDetaching([$student->id => ['relationship' => 'Mother', 'is_primary_contact' => true]]);
        for ($number = 2; $number <= 10; $number++) {
            $parentAccount = User::firstOrCreate(['email' => "parent{$number}@brightstar.test"], ['name' => "Demo Parent {$number}", 'password' => Hash::make('password')]);
            $parentAccount->syncRoles('parent');
            $demoParent = ParentGuardian::firstOrCreate(['email' => $parentAccount->email], ['user_id' => $parentAccount->id, 'school_id' => $school->id, 'first_name' => 'Demo', 'last_name' => "Parent {$number}", 'phone' => sprintf('0200002%03d', $number), 'address' => 'Accra, Ghana']);
            $demoStudent = Student::where('student_id', sprintf('STU-2026-%03d', $number))->firstOrFail();
            $demoParent->students()->syncWithoutDetaching([$demoStudent->id => ['relationship' => 'Parent', 'is_primary_contact' => true]]);
        }

        $classSubject = ClassSubject::firstOrCreate(['school_class_id' => $class->id, 'subject_id' => $subject->id], ['teacher_id' => $teacher->id]);
        $topic = Topic::firstOrCreate(['class_subject_id' => $classSubject->id, 'title' => 'Fractions'], ['description' => 'Introduction to fractions and equivalent fractions.', 'sequence' => 1]);
        $lesson = Lesson::firstOrCreate(['topic_id' => $topic->id, 'title' => 'Adding fractions'], ['teacher_id' => $teacher->id, 'summary' => 'Add fractions with like denominators.', 'content' => '<p>Use visual models to add fractions with like denominators.</p>', 'objectives' => ['Identify numerators and denominators', 'Add fractions with like denominators'], 'sequence' => 1, 'status' => 'published', 'published_at' => now()]);

        Assignment::firstOrCreate(['class_subject_id' => $classSubject->id, 'title' => 'Fraction practice'], ['topic_id' => $topic->id, 'lesson_id' => $lesson->id, 'teacher_id' => $teacher->id, 'instructions' => '<p>Complete questions 1–10 in your exercise book.</p>', 'max_score' => 20, 'opens_at' => '2026-09-10 08:00:00', 'due_at' => '2026-09-17 17:00:00', 'allow_late_submission' => true, 'status' => 'published']);

        $quiz = Quiz::firstOrCreate(['class_subject_id' => $classSubject->id, 'title' => 'Fractions quiz'], ['topic_id' => $topic->id, 'lesson_id' => $lesson->id, 'teacher_id' => $teacher->id, 'instructions' => '<p>Answer each question carefully.</p>', 'time_limit_minutes' => 20, 'pass_mark' => 50, 'max_attempts' => 2, 'randomize_questions' => true, 'opens_at' => '2026-09-18 08:00:00', 'closes_at' => '2026-09-20 17:00:00', 'status' => 'published']);

        $question = Question::firstOrCreate(['school_id' => $school->id, 'prompt' => 'What is one half of ten?'], ['created_by' => $teacherUser->id, 'type' => 'multiple_choice', 'grading_key' => ['answer' => '5'], 'max_score' => 1]);
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
    }
}
