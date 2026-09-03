@props([
    'unreadNotificationCount' => 0,
    'branding' => [],
])

@php
    $sidebarUser = auth()->user();
    $sidebarRole = $sidebarUser?->hasRole('teacher')
        ? 'teacher'
        : ($sidebarUser?->hasRole('student') ? 'student' : ($sidebarUser?->hasRole('parent') ? 'parent' : 'admin'));
    $sidebarIsAdministrator = $sidebarUser?->hasAnyRole(['super_admin', 'school_admin']) ?? false;

    $sidebarRoutes = [
        'lessons' => [
            'admin' => 'lms.lessons.admin.index',
            'teacher' => 'lms.lessons.teacher.index',
            'student' => 'lms.lessons.student.index',
            'parent' => 'lms.lessons.parent.index',
        ],
        'topics' => [
            'admin' => 'lms.topics.admin.index',
            'teacher' => 'lms.topics.teacher.index',
            'student' => null,
            'parent' => null,
        ],
        'assignments' => [
            'admin' => 'lms.assignments.admin.index',
            'teacher' => 'lms.assignments.teacher.index',
            'student' => 'lms.assignments.student.index',
            'parent' => 'lms.assignments.parent.index',
        ],
        'quizzes' => [
            'admin' => 'lms.quizzes.admin.index',
            'teacher' => 'lms.quizzes.teacher.index',
            'student' => 'lms.quizzes.student.index',
            'parent' => 'lms.quizzes.parent.index',
        ],
        'examinations' => [
            'admin' => 'lms.examinations.admin.index',
            'teacher' => 'lms.examinations.teacher.index',
            'student' => 'lms.examinations.student.index',
            'parent' => 'lms.examinations.parent.index',
        ],
        'assessments' => [
            'admin' => 'lms.assessments.admin.index',
            'teacher' => 'lms.assessments.teacher.index',
            'student' => 'lms.results.student.index',
            'parent' => 'lms.results.parent.index',
        ],
        'attendance' => [
            'admin' => 'lms.attendance.admin.overview',
            'teacher' => 'lms.attendance.teacher.record',
            'student' => 'lms.attendance.student.show',
            'parent' => 'lms.attendance.parent.show',
        ],
        'timetables' => [
            'admin' => 'lms.timetables.admin.index',
            'teacher' => 'lms.timetables.teacher.index',
            'student' => 'lms.timetables.student.index',
            'parent' => 'lms.timetables.parent.index',
        ],
        'reports' => [
            'admin' => 'lms.reports.index',
            'teacher' => 'lms.reports.index',
            'student' => 'lms.reports.student.index',
            'parent' => 'lms.reports.parent.index',
        ],
        'announcements' => [
            'admin' => 'lms.announcements.admin.manage',
            'teacher' => 'lms.announcements.teacher.manage',
            'student' => 'lms.announcements.feed',
            'parent' => 'lms.announcements.feed',
        ],
        'results' => [
            'admin' => 'lms.reports.index',
            'teacher' => 'lms.assessments.teacher.index',
            'student' => 'lms.results.student.index',
            'parent' => 'lms.results.parent.index',
        ],
    ];

    $sidebarRoute = static fn (?string $name): ?string => $name && \Illuminate\Support\Facades\Route::has($name) ? route($name) : null;

    $lessonsRoute = $sidebarRoute($sidebarRoutes['lessons'][$sidebarRole] ?? null);
    $topicsRoute = $sidebarRoute($sidebarRoutes['topics'][$sidebarRole] ?? null);
    $assignmentsRoute = $sidebarRoute($sidebarRoutes['assignments'][$sidebarRole] ?? null);
    $quizzesRoute = $sidebarRoute($sidebarRoutes['quizzes'][$sidebarRole] ?? null);
    $examinationsRoute = $sidebarRoute($sidebarRoutes['examinations'][$sidebarRole] ?? null);
    $assessmentsRoute = $sidebarRoute($sidebarRoutes['assessments'][$sidebarRole] ?? null);
    $attendanceRoute = $sidebarRoute($sidebarRoutes['attendance'][$sidebarRole] ?? null);
    $timetablesRoute = $sidebarRoute($sidebarRoutes['timetables'][$sidebarRole] ?? null);
    $reportsRoute = $sidebarRoute($sidebarRoutes['reports'][$sidebarRole] ?? null);
    $announcementsRoute = $sidebarRoute($sidebarRoutes['announcements'][$sidebarRole] ?? null);
@endphp

<aside id="lms-sidebar" class="sidebar-panel fixed inset-y-0 left-0 z-40 flex h-dvh w-72 flex-col overflow-hidden border-r border-slate-800 bg-slate-900 text-slate-100 shadow-2xl transition-[width,transform] duration-200 ease-in-out md:shadow-none" aria-label="Primary navigation" aria-hidden="true">
    <div class="z-10 flex min-h-16 shrink-0 items-center justify-between border-b border-slate-800 bg-slate-900 px-3 py-2">
        <div class="flex min-w-0 items-center gap-3">
            @if ($branding['logo_url'] ?? null)
                <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['name'] }} logo" class="h-10 w-10 shrink-0 rounded-xl bg-white object-contain ring-1 ring-white/20">
            @else
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-sm font-bold text-white ring-1 ring-white/20" style="background-color: {{ data_get($branding, 'colors.primary', '#2563eb') }}">{{ $branding['initials'] ?? 'LMS' }}</div>
            @endif
            <div class="brand-copy min-w-0">
                <p class="truncate text-base font-semibold" title="{{ $branding['name'] ?? 'School LMS' }}">{{ $branding['name'] ?? 'School LMS' }}</p>
                <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Learning portal</p>
            </div>
        </div>

        <button id="sidebar-toggle" type="button" class="inline-flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-xl border border-slate-700 bg-slate-800 text-slate-200 transition hover:bg-slate-700 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Close navigation" aria-controls="lms-sidebar" aria-expanded="false" title="Close navigation">
            <!--
            <span aria-hidden="true">«</span>
            -->
            <svg data-sidebar-icon="mobile-close" class="h-5 w-5 md:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"></path></svg>
            <svg data-sidebar-icon="desktop-collapse" class="hidden h-5 w-5 md:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m14 7-5 5 5 5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
        </button>
    </div>

    <nav class="sidebar-nav flex-1 space-y-5 overflow-y-auto overscroll-contain px-3 py-5 text-sm" aria-label="LMS modules">
        <div>
            <p class="nav-section-label mb-2 px-3 text-xs font-semibold uppercase tracking-widest text-slate-500">Main</p>
            <div class="space-y-1">
                <a href="{{ route('lms.dashboard') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.dashboard*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">📊</span>
                    <span class="nav-link-text">Dashboard</span>
                </a>
                @if ($profileRoute = $sidebarRoute('lms.profile.edit'))
                    <a href="{{ $profileRoute }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.profile.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                        <span aria-hidden="true">👤</span>
                        <span class="nav-link-text">My Profile</span>
                    </a>
                @endif
                <a href="{{ route('home') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 text-slate-300 hover:bg-slate-800 hover:text-white">
                    <span aria-hidden="true">🌐</span>
                    <span class="nav-link-text">School Website</span>
                </a>
            </div>
        </div>

        @if ($sidebarIsAdministrator)
            <div>
                <p class="nav-section-label mb-2 px-3 text-xs font-semibold uppercase tracking-widest text-slate-500">Academic Setup</p>
            <div class="space-y-1">
                <a href="{{ route('lms.academic-years.index') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.academic-years.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">📅</span>
                    <span class="nav-link-text">Academic Years</span>
                </a>
                <a href="{{ route('lms.terms.index') ?? '#' }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.terms.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">🗓️</span>
                    <span class="nav-link-text">Terms</span>
                </a>
                <a href="{{ route('lms.classes.index') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.classes.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">🏛️</span>
                    <span class="nav-link-text">Classes</span>
                </a>
                <a href="{{ route('lms.streams.index') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.streams.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">🌊</span>
                    <span class="nav-link-text">Streams</span>
                </a>
                <a href="{{ route('lms.subjects.index') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.subjects.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">📚</span>
                    <span class="nav-link-text">Subjects</span>
                </a>
                @can('viewAny', App\Models\ClassSubject::class)
                    <a href="{{ route('lms.class-subjects.index') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.class-subjects.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M4 5h16v14H4z" stroke-linejoin="round"></path>
                            <path d="M8 3v4M16 3v4M7 11h10M8 15h3"></path>
                        </svg>
                        <span class="nav-link-text">Class Subjects</span>
                    </a>
                @endcan
            </div>
        </div>

        <div>
            <p class="nav-section-label mb-2 px-3 text-xs font-semibold uppercase tracking-widest text-slate-500">People</p>
            <div class="space-y-1">
                <a href="{{ route('lms.students.index') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.students.index')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">👨‍🎓</span>
                    <span class="nav-link-text">Students</span>
                </a>
                @can('create', App\Models\Student::class)
                    <a href="{{ route('lms.students.promotions.index') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.students.promotions.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M5 19h14M7 16l5-5 5 5M12 11V4" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        <span class="nav-link-text">Student Promotions</span>
                    </a>
                @endcan
                <a href="{{ route('lms.teachers.index') ?? '#' }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.teachers.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">👨‍🏫</span>
                    <span class="nav-link-text">Teachers</span>
                </a>
                <a href="{{ route('lms.parents.index') ?? '#' }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.parents.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">👨‍👩‍👧</span>
                    <span class="nav-link-text">Parents</span>
                </a>
            </div>
            </div>
        @endif

        <div>
            <p class="nav-section-label mb-2 px-3 text-xs font-semibold uppercase tracking-widest text-slate-500">Learning</p>
            <div class="space-y-1">
                @if ($lessonsRoute)
                    <a href="{{ $lessonsRoute }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.lessons.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                        <span aria-hidden="true">📖</span>
                        <span class="nav-link-text">Lessons</span>
                    </a>
                @endif
                @if ($topicsRoute)
                    <a href="{{ $topicsRoute }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.topics.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                        <span aria-hidden="true">🔖</span>
                        <span class="nav-link-text">Topics</span>
                    </a>
                @endif
            </div>
        </div>

        <div>
            <p class="nav-section-label mb-2 px-3 text-xs font-semibold uppercase tracking-widest text-slate-500">Assessments</p>
            <div class="space-y-1">
                @if ($assignmentsRoute)
                    <a href="{{ $assignmentsRoute }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.assignments.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                        <span aria-hidden="true">✍️</span>
                        <span class="nav-link-text">Assignments</span>
                    </a>
                @endif
                @if ($quizzesRoute)
                    <a href="{{ $quizzesRoute }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.quizzes.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                        <span aria-hidden="true">❓</span>
                        <span class="nav-link-text">Quizzes</span>
                    </a>
                @endif
                @can('viewAny', App\Models\Question::class)
                    <a href="{{ route('lms.questions.index') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.questions.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M9 5h9M9 9h9M9 13h5M5 5h.01M5 9h.01M5 13h.01"></path>
                            <path d="M4 3h16v18H4z" stroke-linejoin="round"></path>
                        </svg>
                        <span class="nav-link-text">Question Bank</span>
                    </a>
                @endcan
                @if ($examinationsRoute)
                    <a href="{{ $examinationsRoute }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.examinations.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                        <span aria-hidden="true">📝</span>
                        <span class="nav-link-text">Examinations</span>
                    </a>
                @endif
                @if ($assessmentsRoute)
                    <a href="{{ $assessmentsRoute }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.assessments.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                        <span aria-hidden="true">📊</span>
                        <span class="nav-link-text">Assessments</span>
                    </a>
                @endif
                @if (in_array($sidebarRole, ['student', 'parent'], true) && \Illuminate\Support\Facades\Route::has($sidebarRoutes['results'][$sidebarRole]))
                    <a href="{{ route($sidebarRoutes['results'][$sidebarRole]) }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.results.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                        <span aria-hidden="true">🎯</span>
                        <span class="nav-link-text">Results</span>
                    </a>
                @endif
                @can('viewAny', App\Models\AssessmentComponent::class)
                    <a href="{{ route('lms.assessment-components.index') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.assessment-components.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M6 3h12v18H6z" stroke-linejoin="round"></path>
                            <path d="M9 8h6M9 12h6M9 16h3"></path>
                        </svg>
                        <span class="nav-link-text">Assessment Components</span>
                    </a>
                @endcan
                @can('viewAny', App\Models\GradingScale::class)
                    <a href="{{ route('lms.grading-scales.index') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.grading-scales.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M6 3h12v18H6z" stroke-linejoin="round"></path>
                            <path d="M9 8h6M9 12h6M9 16h3"></path>
                        </svg>
                        <span class="nav-link-text">Grading Scales</span>
                    </a>
                @endcan
            </div>
        </div>

        <div>
            <p class="nav-section-label mb-2 px-3 text-xs font-semibold uppercase tracking-widest text-slate-500">Records</p>
            <div class="space-y-1">
                @if ($attendanceRoute)
                    <a href="{{ $attendanceRoute }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.attendance.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                        <span aria-hidden="true">✅</span>
                        <span class="nav-link-text">Attendance</span>
                    </a>
                @endif
                @if ($timetablesRoute)
                    <a href="{{ $timetablesRoute }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.timetables.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                        <span aria-hidden="true">⏰</span>
                        <span class="nav-link-text">Timetables</span>
                    </a>
                @endif
                @can('viewAny', App\Models\SchedulePeriod::class)
                    <a href="{{ route('lms.schedule-periods.index') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.schedule-periods.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="M12 7v5l3 2"></path>
                        </svg>
                        <span class="nav-link-text">Schedule Periods</span>
                    </a>
                @endcan
                @if ($reportsRoute)
                    <a href="{{ $reportsRoute }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.reports.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                        <span aria-hidden="true">📋</span>
                        <span class="nav-link-text">Reports</span>
                    </a>
                @endif
            </div>
        </div>

        <div>
            <p class="nav-section-label mb-2 px-3 text-xs font-semibold uppercase tracking-widest text-slate-500">Communication</p>
            <div class="space-y-1">
                @if ($announcementsRoute)
                    <a href="{{ $announcementsRoute }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.announcements.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                        <span aria-hidden="true">📢</span>
                        <span class="nav-link-text">Announcements</span>
                    </a>
                @endif
                @if ($sidebarIsAdministrator)
                    <a href="{{ route('lms.emails.index') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.emails.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M3 6h18v12H3z" stroke-linejoin="round"></path>
                            <path d="m3 7 9 6 9-6" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        <span class="nav-link-text">Email Centre</span>
                    </a>
                    <a href="{{ route('lms.sms.index') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.sms.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="6" y="3" width="12" height="18" rx="2" stroke-linejoin="round"></rect>
                            <path d="M9 6h6M10 18h4" stroke-linecap="round"></path>
                        </svg>
                        <span class="nav-link-text">SMS Centre</span>
                    </a>
                @endif
                <a href="{{ route('lms.notifications.index') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.notifications.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">🔔</span>
                    <span class="nav-link-text">Notifications</span>
                    @if ($unreadNotificationCount)
                        <span class="nav-link-text ml-auto inline-flex min-w-5 items-center justify-center rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
                    @endif
                </a>
            </div>
        </div>

        @if ($sidebarIsAdministrator)
            <div>
                <p class="nav-section-label mb-2 px-3 text-xs font-semibold uppercase tracking-widest text-slate-500">Administration</p>
            <div class="space-y-1">
                <a href="{{ route('lms.users.index') ?? '#' }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.users.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">👥</span>
                    <span class="nav-link-text">Users</span>
                </a>
                <a href="{{ route('lms.roles.index') ?? '#' }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.roles.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">🎭</span>
                    <span class="nav-link-text">Roles</span>
                </a>
                <a href="{{ route('lms.permissions.index') ?? '#' }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.permissions.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">🔐</span>
                    <span class="nav-link-text">Permissions</span>
                </a>
                <a href="{{ route('lms.school-setup') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.school-setup')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">⚙️</span>
                    <span class="nav-link-text">School Setup</span>
                </a>
                <a href="{{ route('lms.settings.index') ?? '#' }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.settings.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">🔧</span>
                    <span class="nav-link-text">Settings</span>
                </a>
                @can('viewAny', App\Models\AuditLog::class)
                    @if ($auditLogsRoute = $sidebarRoute('lms.audit-logs.index'))
                        <a href="{{ $auditLogsRoute }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.audit-logs.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                            <span aria-hidden="true">🧾</span>
                            <span class="nav-link-text">Audit Logs</span>
                        </a>
                    @endif
                @endcan
            </div>
            </div>
        @endif

        @can('manage website content')
            <div>
                <p class="nav-section-label mb-2 px-3 text-xs font-semibold uppercase tracking-widest text-slate-500">Public Website</p>
                <div class="space-y-1">
                    @if ($websiteSettingsRoute = $sidebarRoute('lms.website.settings'))
                        <a href="{{ $websiteSettingsRoute }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.website.settings')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                            <span aria-hidden="true">🎨</span>
                            <span class="nav-link-text">Brand & Contact</span>
                        </a>
                    @endif
                    @if ($websitePagesRoute = $sidebarRoute('lms.website.pages'))
                        <a href="{{ $websitePagesRoute }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.website.pages')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                            <span aria-hidden="true">🧩</span>
                            <span class="nav-link-text">Website Pages</span>
                        </a>
                    @endif
                    @if ($websiteTestimonialsRoute = $sidebarRoute('lms.website.pages'))
                        <a href="{{ $websiteTestimonialsRoute }}?focus=testimonials" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.website.pages') && request()->query('focus') === 'testimonials') bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                            <span aria-hidden="true">💬</span>
                            <span class="nav-link-text">Testimonials</span>
                        </a>
                    @endif
                    @if ($websiteNewsRoute = $sidebarRoute('lms.website.news'))
                        <a href="{{ $websiteNewsRoute }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.website.news')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                            <span aria-hidden="true">📰</span>
                            <span class="nav-link-text">News</span>
                        </a>
                    @endif
                    @if ($websiteEventsRoute = $sidebarRoute('lms.website.events'))
                        <a href="{{ $websiteEventsRoute }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.website.events')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                            <span aria-hidden="true">📅</span>
                            <span class="nav-link-text">Events</span>
                        </a>
                    @endif
                    @if ($websiteGalleryRoute = $sidebarRoute('lms.website.gallery'))
                        <a href="{{ $websiteGalleryRoute }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.website.gallery')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                            <span aria-hidden="true">🖼️</span>
                            <span class="nav-link-text">Gallery</span>
                        </a>
                    @endif
                    @if ($websiteTeachersRoute = $sidebarRoute('lms.website.teachers'))
                        <a href="{{ $websiteTeachersRoute }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.website.teachers')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                            <span aria-hidden="true">👩🏽‍🏫</span>
                            <span class="nav-link-text">Featured Teachers</span>
                        </a>
                    @endif
                    @if ($websiteInquiriesRoute = $sidebarRoute('lms.website.inquiries'))
                        <a href="{{ $websiteInquiriesRoute }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.website.inquiries')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                            <span aria-hidden="true">✉️</span>
                            <span class="nav-link-text">Enquiries</span>
                        </a>
                    @endif
                </div>
            </div>
        @endcan
    </nav>
</aside>
