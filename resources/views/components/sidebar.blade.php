@php
    $sidebarUser = auth()->user();
    $sidebarRole = $sidebarUser?->hasRole('teacher')
        ? 'teacher'
        : ($sidebarUser?->hasRole('student') ? 'student' : ($sidebarUser?->hasRole('parent') ? 'parent' : 'admin'));

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
            'student' => 'lms.lessons.student.index',
            'parent' => 'lms.lessons.parent.index',
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
            'admin' => 'lms.examinations.index',
            'teacher' => 'lms.examinations.index',
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
            'teacher' => 'lms.dashboard.teacher',
            'student' => 'lms.reports.student.index',
            'parent' => 'lms.reports.parent.index',
        ],
        'announcements' => [
            'admin' => 'lms.announcements.admin.manage',
            'teacher' => 'lms.announcements.teacher.manage',
            'student' => 'lms.announcements.feed',
            'parent' => 'lms.announcements.feed',
        ],
    ];
@endphp

<aside id="lms-sidebar" class="sidebar-panel fixed inset-y-0 left-0 z-30 flex h-dvh w-72 flex-col overflow-hidden border-r border-slate-200 bg-slate-900 text-slate-100 transition-all duration-200 ease-in-out">
    <div class="z-10 shrink-0 flex items-center justify-between border-b border-slate-800 bg-slate-900 px-3 py-4">
        <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white">BS</div>
            <div class="brand-copy min-w-0">
                <p class="truncate text-base font-semibold">BrightStar LMS</p>
                <p class="text-xs uppercase tracking-[0.16em] text-slate-400">School portal</p>
            </div>
        </div>

        <button id="sidebar-toggle" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-700 bg-slate-800 text-slate-200 transition hover:bg-slate-700 hover:text-white" aria-label="Toggle sidebar" title="Collapse sidebar">
            <span aria-hidden="true">«</span>
        </button>
    </div>

    <nav class="sidebar-nav flex-1 space-y-5 overflow-y-auto px-3 py-5 text-sm">
        <div>
            <p class="nav-section-label mb-2 px-3 text-xs font-semibold uppercase tracking-widest text-slate-500">Main</p>
            <div class="space-y-1">
                <a href="{{ route('lms.dashboard') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.dashboard')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">📊</span>
                    <span class="nav-link-text">Dashboard</span>
                </a>
            </div>
        </div>

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
            </div>
        </div>

        <div>
            <p class="nav-section-label mb-2 px-3 text-xs font-semibold uppercase tracking-widest text-slate-500">People</p>
            <div class="space-y-1">
                <a href="{{ route('lms.students.index') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.students.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">👨‍🎓</span>
                    <span class="nav-link-text">Students</span>
                </a>
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

        <div>
            <p class="nav-section-label mb-2 px-3 text-xs font-semibold uppercase tracking-widest text-slate-500">Learning</p>
            <div class="space-y-1">
                <a href="{{ route($sidebarRoutes['lessons'][$sidebarRole]) }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.lessons.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">📖</span>
                    <span class="nav-link-text">Lessons</span>
                </a>
                <a href="{{ route($sidebarRoutes['topics'][$sidebarRole]) }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.topics.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">🔖</span>
                    <span class="nav-link-text">Topics</span>
                </a>
            </div>
        </div>

        <div>
            <p class="nav-section-label mb-2 px-3 text-xs font-semibold uppercase tracking-widest text-slate-500">Assessments</p>
            <div class="space-y-1">
                <a href="{{ route($sidebarRoutes['assignments'][$sidebarRole]) }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.assignments.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">✍️</span>
                    <span class="nav-link-text">Assignments</span>
                </a>
                <a href="{{ route($sidebarRoutes['quizzes'][$sidebarRole]) }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.quizzes.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">❓</span>
                    <span class="nav-link-text">Quizzes</span>
                </a>
                <a href="{{ route($sidebarRoutes['examinations'][$sidebarRole]) }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.examinations.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">📝</span>
                    <span class="nav-link-text">Examinations</span>
                </a>
                <a href="{{ route($sidebarRoutes['assessments'][$sidebarRole]) }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.assessments.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">📊</span>
                    <span class="nav-link-text">Assessments</span>
                </a>
            </div>
        </div>

        <div>
            <p class="nav-section-label mb-2 px-3 text-xs font-semibold uppercase tracking-widest text-slate-500">Records</p>
            <div class="space-y-1">
                <a href="{{ route($sidebarRoutes['attendance'][$sidebarRole]) }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.attendance.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">✅</span>
                    <span class="nav-link-text">Attendance</span>
                </a>
                <a href="{{ route($sidebarRoutes['timetables'][$sidebarRole]) }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.timetables.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">⏰</span>
                    <span class="nav-link-text">Timetables</span>
                </a>
                @can('viewAny', App\Models\SchedulePeriod::class)
                    <a href="{{ route('lms.schedule-periods.index') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.schedule-periods.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="M12 7v5l3 2"></path>
                        </svg>
                        <span class="nav-link-text">Schedule Periods</span>
                    </a>
                @endcan
                <a href="{{ route($sidebarRoutes['reports'][$sidebarRole]) }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.reports.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">📋</span>
                    <span class="nav-link-text">Reports</span>
                </a>
            </div>
        </div>

        <div>
            <p class="nav-section-label mb-2 px-3 text-xs font-semibold uppercase tracking-widest text-slate-500">Communication</p>
            <div class="space-y-1">
                <a href="{{ route($sidebarRoutes['announcements'][$sidebarRole]) }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.announcements.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">📢</span>
                    <span class="nav-link-text">Announcements</span>
                </a>
                <a href="{{ route('lms.notifications.index') ?? '#' }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 @if(request()->routeIs('lms.notifications.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
                    <span aria-hidden="true">🔔</span>
                    <span class="nav-link-text">Notifications</span>
                </a>
            </div>
        </div>

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
            </div>
        </div>
    </nav>
</aside>
