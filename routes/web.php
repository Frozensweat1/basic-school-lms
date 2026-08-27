<?php

use App\Livewire\LMS\AcademicYears\Index as AcademicYearsIndex;
use App\Livewire\LMS\Announcements\Admin\Manage as AdminAnnouncementsManage;
use App\Livewire\LMS\Announcements\Teacher\Manage as TeacherAnnouncementsManage;
use App\Livewire\LMS\Announcements\Feed as AnnouncementsFeed;
use App\Livewire\LMS\AuditLogs\Index as AuditLogsIndex;
use App\Livewire\LMS\Assessments\Admin\Index as AdminAssessmentsIndex;
use App\Livewire\LMS\Assessments\Teacher\Index as TeacherAssessmentsIndex;
use App\Livewire\LMS\AssessmentComponents\Index as AssessmentComponentsIndex;
use App\Livewire\LMS\AssessmentScores\Admin\Index as AdminAssessmentScoresIndex;
use App\Livewire\LMS\AssessmentScores\Teacher\Index as TeacherAssessmentScoresIndex;
use App\Livewire\LMS\Assignments\Admin\Index as AdminAssignmentsIndex;
use App\Livewire\LMS\Assignments\Teacher\Index as TeacherAssignmentsIndex;
use App\Livewire\LMS\Assignments\Teacher\Grade as TeacherAssignmentGrade;
use App\Livewire\LMS\Assignments\Student\Index as StudentAssignmentsIndex;
use App\Livewire\LMS\Assignments\Parent\Index as ParentAssignmentsIndex;
use App\Livewire\LMS\Attendance\Admin\Overview as AdminAttendanceOverview;
use App\Livewire\LMS\Attendance\Teacher\Record as TeacherAttendanceRecord;
use App\Livewire\LMS\Attendance\Student\Show as StudentAttendanceShow;
use App\Livewire\LMS\Attendance\Parent\Show as ParentAttendanceShow;
use App\Livewire\LMS\Classes\Index as ClassesIndex;
use App\Livewire\LMS\ClassSubjects\Index as ClassSubjectsIndex;
use App\Livewire\LMS\Dashboard\Admin as AdminDashboard;
use App\Livewire\LMS\Dashboard\Teacher as TeacherDashboard;
use App\Livewire\LMS\Dashboard\Student as StudentDashboard;
use App\Livewire\LMS\Dashboard\ParentDashboard;
use App\Livewire\LMS\Examinations\Index as ExaminationsIndex;
use App\Livewire\LMS\Examinations\Questions\AdminIndex as AdminExamQuestionsIndex;
use App\Livewire\LMS\Examinations\Questions\TeacherIndex as TeacherExamQuestionsIndex;
use App\Livewire\LMS\Examinations\Scores\AdminIndex as AdminExamScoresIndex;
use App\Livewire\LMS\Examinations\Scores\TeacherIndex as TeacherExamScoresIndex;
use App\Livewire\LMS\Examinations\Student\Index as StudentExaminationsIndex;
use App\Livewire\LMS\Examinations\Parent\Index as ParentExaminationsIndex;
use App\Livewire\LMS\Lessons\Admin\Index as AdminLessonsIndex;
use App\Livewire\LMS\Lessons\Teacher\Index as TeacherLessonsIndex;
use App\Livewire\LMS\Lessons\Student\Index as StudentLessonsIndex;
use App\Livewire\LMS\Lessons\Parent\Index as ParentLessonsIndex;
use App\Livewire\LMS\Notifications\Index as NotificationsIndex;
use App\Livewire\LMS\Profile\Index as ProfileIndex;
use App\Livewire\LMS\Parents\Index as ParentsIndex;
use App\Livewire\LMS\Permissions\Index as PermissionsIndex;
use App\Livewire\LMS\Quizzes\Admin\Index as AdminQuizzesIndex;
use App\Livewire\LMS\Quizzes\Teacher\Index as TeacherQuizzesIndex;
use App\Livewire\LMS\Quizzes\Teacher\Grade as TeacherQuizGrade;
use App\Livewire\LMS\Quizzes\Student\Index as StudentQuizzesIndex;
use App\Livewire\LMS\Quizzes\Student\Attempt as StudentQuizAttempt;
use App\Livewire\LMS\Quizzes\Parent\Index as ParentQuizzesIndex;
use App\Livewire\LMS\QuizQuestions\Admin\Index as AdminQuizQuestionsIndex;
use App\Livewire\LMS\QuizQuestions\Teacher\Index as TeacherQuizQuestionsIndex;
use App\Livewire\LMS\Questions\Index as QuestionsIndex;
use App\Livewire\LMS\GradingScales\Index as GradingScalesIndex;
use App\Livewire\LMS\Reports\Index as ReportsIndex;
use App\Livewire\LMS\Reports\Show as ReportShow;
use App\Livewire\LMS\Reports\Student\Index as StudentReportsIndex;
use App\Livewire\LMS\Reports\Parent\Index as ParentReportsIndex;
use App\Livewire\LMS\Results\Student\Index as StudentResultsIndex;
use App\Livewire\LMS\Results\Parent\Index as ParentResultsIndex;
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
use App\Livewire\LMS\Timetables\Student\Index as StudentTimetablesIndex;
use App\Livewire\LMS\Timetables\Parent\Index as ParentTimetablesIndex;
use App\Livewire\LMS\TimetableEntries\Index as TimetableEntriesIndex;
use App\Livewire\LMS\SchedulePeriods\Index as SchedulePeriodsIndex;
use App\Livewire\LMS\Topics\Admin\Index as AdminTopicsIndex;
use App\Livewire\LMS\Topics\Teacher\Index as TeacherTopicsIndex;
use App\Livewire\LMS\Users\Index as UsersIndex;
use App\Livewire\Website\HomePage;
use App\Livewire\Website\About as WebsiteAbout;
use App\Livewire\Website\Academics as WebsiteAcademics;
use App\Livewire\Website\Admissions as WebsiteAdmissions;
use App\Livewire\Website\Teachers as WebsiteTeachers;
use App\Livewire\Website\News as WebsiteNews;
use App\Livewire\Website\NewsShow as WebsiteNewsShow;
use App\Livewire\Website\Events as WebsiteEvents;
use App\Livewire\Website\EventShow as WebsiteEventShow;
use App\Livewire\Website\Gallery as WebsiteGallery;
use App\Livewire\Website\Contact as WebsiteContact;
use App\Livewire\LMS\Website\Settings as WebsiteSettings;
use App\Livewire\LMS\Website\News\Index as WebsiteNewsIndex;
use App\Livewire\LMS\Website\Events\Index as WebsiteEventsIndex;
use App\Livewire\LMS\Website\Pages\Index as WebsitePagesIndex;
use App\Livewire\LMS\Website\Inquiries\Index as WebsiteInquiriesIndex;
use App\Livewire\LMS\Website\Teachers\Index as WebsiteTeachersIndex;
use App\Livewire\LMS\Website\Gallery\Albums as WebsiteGalleryAlbums;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SitemapController;

Route::get('/', HomePage::class)->name('home');
Route::get('/about', WebsiteAbout::class)->name('website.about');
Route::get('/academics', WebsiteAcademics::class)->name('website.academics');
Route::get('/admissions', WebsiteAdmissions::class)->name('website.admissions');
Route::get('/teachers', WebsiteTeachers::class)->name('website.teachers');
Route::get('/news', WebsiteNews::class)->name('website.news');
Route::get('/news/{slug}', WebsiteNewsShow::class)
    ->where('slug', '[A-Za-z0-9_-]+')
    ->name('website.news.show');
Route::get('/events', WebsiteEvents::class)->name('website.events');
Route::get('/events/{slug}', WebsiteEventShow::class)
    ->where('slug', '[A-Za-z0-9_-]+')
    ->name('website.events.show');
Route::get('/gallery', WebsiteGallery::class)->name('website.gallery');
Route::get('/contact', WebsiteContact::class)->name('website.contact');
Route::get('/sitemap.xml', SitemapController::class)->name('website.sitemap');

Route::middleware(['auth'])->group(function () {
    Route::get('/lms/profile', ProfileIndex::class)->name('lms.profile.edit');

    // Public website CMS (restricted to users with the website permission)
    Route::middleware('can:manage website content')->group(function (): void {
        Route::get('/lms/website/settings', WebsiteSettings::class)->name('lms.website.settings');
        Route::get('/lms/website/pages', WebsitePagesIndex::class)->name('lms.website.pages');
        Route::get('/lms/website/news', WebsiteNewsIndex::class)->name('lms.website.news');
        Route::get('/lms/website/events', WebsiteEventsIndex::class)->name('lms.website.events');
        Route::get('/lms/website/inquiries', WebsiteInquiriesIndex::class)->name('lms.website.inquiries');
        Route::get('/lms/website/teachers', WebsiteTeachersIndex::class)->name('lms.website.teachers');
        Route::get('/lms/website/gallery', WebsiteGalleryAlbums::class)->name('lms.website.gallery');
    });

    // Dashboard
    Route::get('/lms/admin/dashboard', AdminDashboard::class)->name('lms.dashboard.admin');
    Route::get('/lms/teacher/dashboard', TeacherDashboard::class)->name('lms.dashboard.teacher');
    Route::get('/lms/student/dashboard', StudentDashboard::class)->name('lms.dashboard.student');
    Route::get('/lms/parent/dashboard', ParentDashboard::class)->name('lms.dashboard.parent');
    Route::get('/lms/dashboard', function () {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $roles = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', User::class)
            ->where('model_has_roles.model_id', $user->id)
            ->pluck('roles.name')
            ->all();

        $route = in_array('teacher', $roles, true)
            ? 'lms.dashboard.teacher'
            : (in_array('student', $roles, true)
                ? 'lms.dashboard.student'
                : (in_array('parent', $roles, true)
                    ? 'lms.dashboard.parent'
                    : 'lms.dashboard.admin'));
        return redirect()->route($route);
    })->name('lms.dashboard');

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
    Route::get('/lms/student/lessons', StudentLessonsIndex::class)->name('lms.lessons.student.index');
    Route::get('/lms/parent/lessons', ParentLessonsIndex::class)->name('lms.lessons.parent.index');
    
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
    Route::get('/lms/teacher/assignments/{assignment}/grade', TeacherAssignmentGrade::class)
        ->middleware('can:view,assignment')->name('lms.assignments.teacher.grade');
    Route::get('/lms/assignments/{assignment}/submissions', TeacherAssignmentGrade::class)
        ->middleware('can:view,assignment')->name('lms.assignments.submissions');
    Route::get('/lms/student/assignments', StudentAssignmentsIndex::class)->name('lms.assignments.student.index');
    Route::get('/lms/parent/assignments', ParentAssignmentsIndex::class)->name('lms.assignments.parent.index');
    
    Route::get('/lms/admin/quizzes', AdminQuizzesIndex::class)
        ->middleware('can:viewAny,App\\Models\\Quiz')
        ->name('lms.quizzes.admin.index');
    Route::get('/lms/teacher/quizzes', TeacherQuizzesIndex::class)
        ->middleware('can:viewAny,App\\Models\\Quiz')
        ->name('lms.quizzes.teacher.index');
    Route::get('/lms/teacher/quizzes/{quiz}/grade', TeacherQuizGrade::class)->middleware('can:update,quiz')->name('lms.quizzes.teacher.grade');
    Route::get('/lms/student/quizzes', StudentQuizzesIndex::class)->name('lms.quizzes.student.index');
    Route::get('/lms/student/quizzes/attempts/{attempt}', StudentQuizAttempt::class)->name('lms.quizzes.student.attempt');
    Route::get('/lms/parent/quizzes', ParentQuizzesIndex::class)->name('lms.quizzes.parent.index');
    Route::get('/lms/admin/quizzes/{quiz}/questions', AdminQuizQuestionsIndex::class)
        ->middleware('can:update,quiz')
        ->name('lms.quizzes.admin.questions.index');
    Route::get('/lms/teacher/quizzes/{quiz}/questions', TeacherQuizQuestionsIndex::class)
        ->middleware('can:update,quiz')
        ->name('lms.quizzes.teacher.questions.index');
    Route::get('/lms/questions', QuestionsIndex::class)->middleware('can:viewAny,App\\Models\\Question')->name('lms.questions.index');
    
    Route::get('/lms/admin/examinations', ExaminationsIndex::class)
        ->middleware('can:viewAny,App\\Models\\Examination')
        ->name('lms.examinations.admin.index');
    Route::get('/lms/teacher/examinations', ExaminationsIndex::class)
        ->middleware('can:viewAny,App\\Models\\Examination')
        ->name('lms.examinations.teacher.index');
    Route::get('/lms/examinations', ExaminationsIndex::class)
        ->middleware('can:viewAny,App\\Models\\Examination')
        ->name('lms.examinations.index');
    Route::get('/lms/student/examinations', StudentExaminationsIndex::class)
        ->name('lms.examinations.student.index');
    Route::get('/lms/parent/examinations', ParentExaminationsIndex::class)
        ->name('lms.examinations.parent.index');

    Route::get('/lms/admin/examinations/{examination}/questions', AdminExamQuestionsIndex::class)
        ->middleware('can:update,examination')
        ->name('lms.examinations.admin.questions.index');
    Route::get('/lms/teacher/examinations/{examination}/questions', TeacherExamQuestionsIndex::class)
        ->middleware('can:update,examination')
        ->name('lms.examinations.teacher.questions.index');
    Route::get('/lms/admin/examinations/{examination}/scores', AdminExamScoresIndex::class)
        ->middleware('can:update,examination')
        ->name('lms.examinations.admin.scores.index');
    Route::get('/lms/teacher/examinations/{examination}/scores', TeacherExamScoresIndex::class)
        ->middleware('can:update,examination')
        ->name('lms.examinations.teacher.scores.index');
    
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
    Route::get('/lms/student/attendance', StudentAttendanceShow::class)->name('lms.attendance.student.show');
    Route::get('/lms/parent/attendance', ParentAttendanceShow::class)->name('lms.attendance.parent.show');
    
    Route::get('/lms/admin/timetables', AdminTimetablesIndex::class)
        ->middleware('can:viewAny,App\\Models\\Timetable')
        ->name('lms.timetables.admin.index');
    Route::get('/lms/teacher/timetables', TeacherTimetablesIndex::class)
        ->middleware('can:viewAny,App\\Models\\Timetable')
        ->name('lms.timetables.teacher.index');
    Route::get('/lms/student/timetables', StudentTimetablesIndex::class)->name('lms.timetables.student.index');
    Route::get('/lms/parent/timetables', ParentTimetablesIndex::class)->name('lms.timetables.parent.index');
    Route::get('/lms/timetables/{timetable}/entries', TimetableEntriesIndex::class)->middleware('can:update,timetable')->name('lms.timetables.entries.index');
    Route::get('/lms/schedule-periods', SchedulePeriodsIndex::class)->middleware('can:viewAny,App\\Models\\SchedulePeriod')->name('lms.schedule-periods.index');
    
    Route::get('/lms/reports', ReportsIndex::class)
        ->middleware('can:viewAny,App\\Models\\ReportCard')
        ->name('lms.reports.index');
    Route::get('/lms/reports/{reportCard}', ReportShow::class)->middleware('can:view,reportCard')->name('lms.reports.show');
    Route::get('/lms/student/reports', StudentReportsIndex::class)->name('lms.reports.student.index');
    Route::get('/lms/parent/reports', ParentReportsIndex::class)->name('lms.reports.parent.index');
    Route::get('/lms/student/results', StudentResultsIndex::class)->name('lms.results.student.index');
    Route::get('/lms/parent/results', ParentResultsIndex::class)->name('lms.results.parent.index');

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
        ->middleware('can:viewAny,App\\Models\\User')
        ->name('lms.roles.index');
    
    Route::get('/lms/permissions', PermissionsIndex::class)
        ->middleware('can:viewAny,App\\Models\\User')
        ->name('lms.permissions.index');

    Route::get('/lms/audit-logs', AuditLogsIndex::class)
        ->middleware('can:viewAny,App\\Models\\User')
        ->name('lms.audit-logs.index');
    
    Route::get('/lms/school-setup', SchoolSetup::class)
        ->middleware('can:viewAny,App\\Models\\AcademicYear')
        ->name('lms.school-setup');
    
    Route::get('/lms/settings', SettingsIndex::class)
        ->name('lms.settings.index');
});
