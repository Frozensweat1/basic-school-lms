<?php

namespace App\Support;

use Illuminate\Support\Str;

class LmsPage
{
    public function title(?string $routeName = null): string
    {
        $routeName ??= request()->route()?->getName();

        return match (true) {
            blank($routeName), Str::startsWith($routeName, 'lms.dashboard') => 'Dashboard',
            Str::contains($routeName, '.assessments.') && Str::contains($routeName, '.scores.') => 'Assessment Scores',
            Str::contains($routeName, '.assignments.') && Str::contains($routeName, '.grade') => 'Assignment Submissions',
            Str::contains($routeName, '.quizzes.') && Str::contains($routeName, '.questions.') => 'Quiz Questions',
            Str::contains($routeName, '.quizzes.') && Str::contains($routeName, '.grade') => 'Quiz Results',
            Str::startsWith($routeName, 'lms.academic-years') => 'Academic Years',
            Str::startsWith($routeName, 'lms.terms') => 'Terms',
            Str::startsWith($routeName, 'lms.classes') => 'Classes',
            Str::startsWith($routeName, 'lms.streams') => 'Streams',
            Str::startsWith($routeName, 'lms.subjects') => 'Subjects',
            Str::startsWith($routeName, 'lms.class-subjects') => 'Class Subjects',
            Str::startsWith($routeName, 'lms.students.promotions') => 'Student Promotions',
            Str::startsWith($routeName, 'lms.students') => 'Students',
            Str::startsWith($routeName, 'lms.teachers') => 'Teachers',
            Str::startsWith($routeName, 'lms.parents') => 'Parents',
            Str::startsWith($routeName, 'lms.lessons') => 'Lessons',
            Str::startsWith($routeName, 'lms.topics') => 'Topics',
            Str::startsWith($routeName, 'lms.assignments') => 'Assignments',
            Str::startsWith($routeName, 'lms.quizzes') => 'Quizzes',
            Str::startsWith($routeName, 'lms.questions') => 'Question Bank',
            Str::startsWith($routeName, 'lms.examinations') => 'Examinations',
            Str::startsWith($routeName, 'lms.assessment-components') => 'Assessment Components',
            Str::startsWith($routeName, 'lms.assessments') => 'Assessments',
            Str::startsWith($routeName, 'lms.grading-scales') => 'Grading Scales',
            Str::startsWith($routeName, 'lms.attendance') => 'Attendance',
            Str::startsWith($routeName, 'lms.schedule-periods') => 'Schedule Periods',
            Str::startsWith($routeName, 'lms.timetables') && Str::contains($routeName, '.entries.') => 'Timetable Entries',
            Str::startsWith($routeName, 'lms.timetables') => 'Timetables',
            Str::startsWith($routeName, 'lms.reports') => 'Reports',
            Str::startsWith($routeName, 'lms.results') => 'Results',
            Str::startsWith($routeName, 'lms.announcements') => 'Announcements',
            Str::startsWith($routeName, 'lms.emails') => 'Email Centre',
            Str::startsWith($routeName, 'lms.sms') => 'SMS Centre',
            Str::startsWith($routeName, 'lms.notifications') => 'Notifications',
            Str::startsWith($routeName, 'lms.users') => 'Users',
            Str::startsWith($routeName, 'lms.roles') => 'Roles',
            Str::startsWith($routeName, 'lms.permissions') => 'Permissions',
            Str::startsWith($routeName, 'lms.audit-logs') => 'Audit Logs',
            Str::startsWith($routeName, 'lms.school-setup') => 'School Setup',
            Str::startsWith($routeName, 'lms.settings') => 'School Settings',
            Str::startsWith($routeName, 'lms.website.settings') => 'Website Settings',
            Str::startsWith($routeName, 'lms.website.pages') => 'Website Pages',
            Str::startsWith($routeName, 'lms.website.news') => 'Website News',
            Str::startsWith($routeName, 'lms.website.events') => 'Website Events',
            Str::startsWith($routeName, 'lms.website.inquiries') => 'Website Inquiries',
            Str::startsWith($routeName, 'lms.website.teachers') => 'Website Teachers',
            Str::startsWith($routeName, 'lms.website.gallery') => 'Website Gallery',
            Str::startsWith($routeName, 'lms.profile') => 'Profile Settings',
            default => 'Learning Portal',
        };
    }
}
