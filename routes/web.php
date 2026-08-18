<?php

use App\Livewire\LMS\AcademicYears\Index as AcademicYearsIndex;
use App\Livewire\LMS\Announcements\Admin\Manage as AdminAnnouncementsManage;
use App\Livewire\LMS\Announcements\Teacher\Manage as TeacherAnnouncementsManage;
use App\Livewire\LMS\Announcements\Feed as AnnouncementsFeed;
use App\Livewire\LMS\Assessments\Admin\Index as AdminAssessmentsIndex;
use App\Livewire\LMS\Assessments\Teacher\Index as TeacherAssessmentsIndex;
use App\Livewire\LMS\AssessmentComponents\Index as AssessmentComponentsIndex;
use App\Livewire\LMS\AssessmentScores\Admin\Index as AdminAssessmentScoresIndex;
use App\Livewire\LMS\AssessmentScores\Teacher\Index as TeacherAssessmentScoresIndex;
use App\Livewire\LMS\Assignments\Admin\Index as AdminAssignmentsIndex;
use App\Livewire\LMS\Assignments\Teacher\Index as TeacherAssignmentsIndex;
use App\Livewire\LMS\Attendance\Admin\Overview as AdminAttendanceOverview;
use App\Livewire\LMS\Attendance\Teacher\Record as TeacherAttendanceRecord;
use App\Livewire\LMS\Classes\Index as ClassesIndex;
use App\Livewire\LMS\ClassSubjects\Index as ClassSubjectsIndex;
use App\Livewire\LMS\Dashboard;
use App\Livewire\LMS\Examinations\Index as ExaminationsIndex;
use App\Livewire\LMS\Lessons\Admin\Index as AdminLessonsIndex;
use App\Livewire\LMS\Lessons\Teacher\Index as TeacherLessonsIndex;
use App\Livewire\LMS\Notifications\Index as NotificationsIndex;
use App\Livewire\LMS\Parents\Index as ParentsIndex;
use App\Livewire\LMS\Permissions\Index as PermissionsIndex;
use App\Livewire\LMS\Quizzes\Admin\Index as AdminQuizzesIndex;
use App\Livewire\LMS\Quizzes\Teacher\Index as TeacherQuizzesIndex;
use App\Livewire\LMS\QuizQuestions\Admin\Index as AdminQuizQuestionsIndex;
use App\Livewire\LMS\QuizQuestions\Teacher\Index as TeacherQuizQuestionsIndex;
use App\Livewire\LMS\Questions\Index as QuestionsIndex;
use App\Livewire\LMS\GradingScales\Index as GradingScalesIndex;
use App\Livewire\LMS\Reports\Index as ReportsIndex;
use App\Livewire\LMS\Reports\Show as ReportShow;
use App\Livewire\LMS\Roles\Index as RolesIndex;
use App\Livewire\LMS\SchoolSetup;
use App\Livewire\LMS\Settings\Index as SettingsIndex;
use App\Livewire\LMS\Streams\Index as StreamsIndex;
use App\Livewire\LMS\Students\Index as StudentsIndex;
use App\Livewire\LMS\Subjects\Index as SubjectsIndex;
use App\Livewire\LMS\Teachers\Index as TeachersIndex;
use App\Livewire\LMS\Terms\Index as TermsIndex;
use App\Livewire\LMS\Timetables\Admin\Index as AdminTimetablesIndex;
use App\Livewire\LMS\Timetables\Teacher\Index as TeacherTimetablesIndex;
use App\Livewire\LMS\TimetableEntries\Index as TimetableEntriesIndex;
use App\Livewire\LMS\SchedulePeriods\Index as SchedulePeriodsIndex;
use App\Livewire\LMS\Topics\Admin\Index as AdminTopicsIndex;
use App\Livewire\LMS\Topics\Teacher\Index as TeacherTopicsIndex;
use App\Livewire\LMS\Users\Index as UsersIndex;
use App\Livewire\Website\HomePage;
use Illuminate\Support\Facades\Route;

Route::get('/', HomePage::class)->name('home');
Route::middleware(['auth'])->group(function () {
    Route::view('/lms/profile', 'profile.edit')->name('lms.profile.edit');

    // Dashboard
    Route::get('/lms/dashboard', Dashboard::class)->name('lms.dashboard');

    // Academic Setup
    Route::get('/lms/academic-years', AcademicYearsIndex::class)
        ->middleware('can:viewAny,App\\Models\\AcademicYear')
        ->name('lms.academic-years.index');
    
    Route::get('/lms/terms', TermsIndex::class)
        ->middleware('can:viewAny,App\\Models\\Term')
        ->name('lms.terms.index');
    
    Route::get('/lms/classes', ClassesIndex::class)
        ->middleware('can:viewAny,App\\Models\\SchoolClass')
        ->name('lms.classes.index');
    
    Route::get('/lms/streams', StreamsIndex::class)
        ->middleware('can:viewAny,App\\Models\\Stream')
        ->name('lms.streams.index');
    
    Route::get('/lms/subjects', SubjectsIndex::class)
        ->middleware('can:viewAny,App\\Models\\Subject')
        ->name('lms.subjects.index');

    Route::get('/lms/class-subjects', ClassSubjectsIndex::class)
        ->middleware('can:viewAny,App\\Models\\ClassSubject')
        ->name('lms.class-subjects.index');

    // People Management
    Route::get('/lms/students', StudentsIndex::class)
        ->middleware('can:viewAny,App\\Models\\Student')
        ->name('lms.students.index');
    
    Route::get('/lms/teachers', TeachersIndex::class)
        ->middleware('can:viewAny,App\\Models\\Teacher')
        ->name('lms.teachers.index');
    
    Route::get('/lms/parents', ParentsIndex::class)
        ->middleware('can:viewAny,App\\Models\\ParentGuardian')
        ->name('lms.parents.index');

    // Learning Content
    Route::get('/lms/admin/lessons', AdminLessonsIndex::class)
        ->middleware('can:viewAny,App\\Models\\Lesson')
        ->name('lms.lessons.admin.index');
    Route::get('/lms/teacher/lessons', TeacherLessonsIndex::class)
        ->middleware('can:viewAny,App\\Models\\Lesson')
        ->name('lms.lessons.teacher.index');
    
    Route::get('/lms/admin/topics', AdminTopicsIndex::class)
        ->middleware('can:viewAny,App\\Models\\Topic')
        ->name('lms.topics.admin.index');
    Route::get('/lms/teacher/topics', TeacherTopicsIndex::class)
        ->middleware('can:viewAny,App\\Models\\Topic')
        ->name('lms.topics.teacher.index');

    // Assessments
    Route::get('/lms/admin/assignments', AdminAssignmentsIndex::class)
        ->middleware('can:viewAny,App\\Models\\Assignment')
        ->name('lms.assignments.admin.index');
    Route::get('/lms/teacher/assignments', TeacherAssignmentsIndex::class)
        ->middleware('can:viewAny,App\\Models\\Assignment')
        ->name('lms.assignments.teacher.index');
    
    Route::get('/lms/admin/quizzes', AdminQuizzesIndex::class)
        ->middleware('can:viewAny,App\\Models\\Quiz')
        ->name('lms.quizzes.admin.index');
    Route::get('/lms/teacher/quizzes', TeacherQuizzesIndex::class)
        ->middleware('can:viewAny,App\\Models\\Quiz')
        ->name('lms.quizzes.teacher.index');
    Route::get('/lms/admin/quizzes/{quiz}/questions', AdminQuizQuestionsIndex::class)
        ->middleware('can:update,quiz')
        ->name('lms.quizzes.admin.questions.index');
    Route::get('/lms/teacher/quizzes/{quiz}/questions', TeacherQuizQuestionsIndex::class)
        ->middleware('can:update,quiz')
        ->name('lms.quizzes.teacher.questions.index');
    Route::get('/lms/questions', QuestionsIndex::class)->middleware('can:viewAny,App\\Models\\Question')->name('lms.questions.index');
    
    Route::get('/lms/examinations', ExaminationsIndex::class)
        ->name('lms.examinations.index');
    
    Route::get('/lms/admin/assessments', AdminAssessmentsIndex::class)
        ->middleware('can:viewAny,App\\Models\\Assessment')
        ->name('lms.assessments.admin.index');
    Route::get('/lms/teacher/assessments', TeacherAssessmentsIndex::class)
        ->middleware('can:viewAny,App\\Models\\Assessment')
        ->name('lms.assessments.teacher.index');
    Route::get('/lms/grading-scales', GradingScalesIndex::class)->middleware('can:viewAny,App\\Models\\GradingScale')->name('lms.grading-scales.index');
    Route::get('/lms/assessment-components', AssessmentComponentsIndex::class)->middleware('can:viewAny,App\\Models\\AssessmentComponent')->name('lms.assessment-components.index');
    Route::get('/lms/admin/assessments/{assessment}/scores', AdminAssessmentScoresIndex::class)->middleware('can:update,assessment')->name('lms.assessments.admin.scores.index');
    Route::get('/lms/teacher/assessments/{assessment}/scores', TeacherAssessmentScoresIndex::class)->middleware('can:update,assessment')->name('lms.assessments.teacher.scores.index');

    // School Records
    Route::get('/lms/admin/attendance', AdminAttendanceOverview::class)
        ->middleware('can:viewAny,App\\Models\\AttendanceRecord')
        ->name('lms.attendance.admin.overview');
    Route::get('/lms/teacher/attendance', TeacherAttendanceRecord::class)
        ->middleware('can:viewAny,App\\Models\\AttendanceRecord')
        ->name('lms.attendance.teacher.record');
    
    Route::get('/lms/admin/timetables', AdminTimetablesIndex::class)
        ->middleware('can:viewAny,App\\Models\\Timetable')
        ->name('lms.timetables.admin.index');
    Route::get('/lms/teacher/timetables', TeacherTimetablesIndex::class)
        ->middleware('can:viewAny,App\\Models\\Timetable')
        ->name('lms.timetables.teacher.index');
    Route::get('/lms/timetables/{timetable}/entries', TimetableEntriesIndex::class)->middleware('can:update,timetable')->name('lms.timetables.entries.index');
    Route::get('/lms/schedule-periods', SchedulePeriodsIndex::class)->middleware('can:viewAny,App\\Models\\SchedulePeriod')->name('lms.schedule-periods.index');
    
    Route::get('/lms/reports', ReportsIndex::class)
        ->middleware('can:viewAny,App\\Models\\ReportCard')
        ->name('lms.reports.index');
    Route::get('/lms/reports/{reportCard}', ReportShow::class)->middleware('can:view,reportCard')->name('lms.reports.show');

    // Communication
    Route::get('/lms/admin/announcements', AdminAnnouncementsManage::class)
        ->middleware('can:viewAny,App\\Models\\Announcement')
        ->name('lms.announcements.admin.manage');
    Route::get('/lms/teacher/announcements', TeacherAnnouncementsManage::class)
        ->middleware('can:viewAny,App\\Models\\Announcement')
        ->name('lms.announcements.teacher.manage');
    Route::get('/lms/announcements', AnnouncementsFeed::class)
        ->middleware('can:viewAny,App\\Models\\Announcement')
        ->name('lms.announcements.feed');
    
    Route::get('/lms/notifications', NotificationsIndex::class)
        ->name('lms.notifications.index');

    // Administration
    Route::get('/lms/users', UsersIndex::class)
        ->middleware('can:viewAny,App\\Models\\User')
        ->name('lms.users.index');
    
    Route::get('/lms/roles', RolesIndex::class)
        ->name('lms.roles.index');
    
    Route::get('/lms/permissions', PermissionsIndex::class)
        ->name('lms.permissions.index');
    
    Route::get('/lms/school-setup', SchoolSetup::class)
        ->middleware('can:viewAny,App\\Models\\AcademicYear')
        ->name('lms.school-setup');
    
    Route::get('/lms/settings', SettingsIndex::class)
        ->name('lms.settings.index');
});
