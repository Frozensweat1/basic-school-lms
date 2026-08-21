<?php

namespace App\Providers;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Assessment;
use App\Models\AssessmentComponent;
use App\Models\AttendanceRecord;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Timetable;
use App\Models\SchedulePeriod;
use App\Models\Topic;
use App\Models\ParentGuardian;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Lesson;
use App\Models\GradingScale;
use App\Models\ReportCard;
use App\Models\Term;
use App\Models\Examination;
use App\Policies\AcademicYearPolicy;
use App\Policies\AnnouncementPolicy;
use App\Policies\AssignmentPolicy;
use App\Policies\AssessmentPolicy;
use App\Policies\AssessmentComponentPolicy;
use App\Policies\AttendanceRecordPolicy;
use App\Policies\ClassSubjectPolicy;
use App\Policies\SchoolClassPolicy;
use App\Policies\StreamPolicy;
use App\Policies\StudentPolicy;
use App\Policies\SubjectPolicy;
use App\Policies\TeacherPolicy;
use App\Policies\UserPolicy;
use App\Policies\TimetablePolicy;
use App\Policies\SchedulePeriodPolicy;
use App\Policies\TopicPolicy;
use App\Policies\ParentGuardianPolicy;
use App\Policies\QuizPolicy;
use App\Policies\QuestionPolicy;
use App\Policies\LessonPolicy;
use App\Policies\GradingScalePolicy;
use App\Policies\ReportCardPolicy;
use App\Policies\TermPolicy;
use App\Policies\ExaminationPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();
        Schema::defaultStringLength(191);

        Gate::policy(AcademicYear::class, AcademicYearPolicy::class);
        Gate::policy(Announcement::class, AnnouncementPolicy::class);
        Gate::policy(Assignment::class, AssignmentPolicy::class);
        Gate::policy(Assessment::class, AssessmentPolicy::class);
        Gate::policy(AssessmentComponent::class, AssessmentComponentPolicy::class);
        Gate::policy(AttendanceRecord::class, AttendanceRecordPolicy::class);
        Gate::policy(ClassSubject::class, ClassSubjectPolicy::class);
        Gate::policy(SchoolClass::class, SchoolClassPolicy::class);
        Gate::policy(Stream::class, StreamPolicy::class);
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(Subject::class, SubjectPolicy::class);
        Gate::policy(Teacher::class, TeacherPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Timetable::class, TimetablePolicy::class);
        Gate::policy(GradingScale::class, GradingScalePolicy::class);
        Gate::policy(ReportCard::class, ReportCardPolicy::class);
        Gate::policy(SchedulePeriod::class, SchedulePeriodPolicy::class);
        Gate::policy(Topic::class, TopicPolicy::class);
        Gate::policy(ParentGuardian::class, ParentGuardianPolicy::class);
        Gate::policy(Quiz::class, QuizPolicy::class);
        Gate::policy(Question::class, QuestionPolicy::class);
        Gate::policy(Lesson::class, LessonPolicy::class);
        Gate::policy(Term::class, TermPolicy::class);
        Gate::policy(Examination::class, ExaminationPolicy::class);
    }
}
