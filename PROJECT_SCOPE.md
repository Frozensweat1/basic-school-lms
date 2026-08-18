# BrightStar School LMS - Project Scope & Implementation Guide

**Project**: Modern, Production-Ready School Learning Management System  
**Framework**: Laravel 12 + Livewire 4 + Tailwind CSS  
**Status**: Foundation & Authorization Layer Complete  
**Last Updated**: 2026-08-14

---

## 📋 Project Overview

BrightStar is a comprehensive web platform that combines:
1. **Public School Website/Landing Page** - Public-facing marketing and information portal
2. **Secure Learning Management System** - Authenticated backend for students, teachers, parents, and administrators

The application maintains strict separation between public and authenticated areas while providing unified school platform experience.

---

## ✅ COMPLETED PHASES

### Phase 1: Foundation & Authentication
- [x] Laravel 12 project setup with Fortify authentication
- [x] Spatie Permission roles and policies integration
- [x] Role definitions: super_admin, school_admin, teacher, student, parent
- [x] User model with role support via HasRoles trait
- [x] Database seeders for roles and initial admin user

### Phase 2: School Domain Model
- [x] AcademicYear model (with terms, classes relationships)
- [x] Term model (belongs to academic year, has classes)
- [x] Stream model (class divisions like A, B, C streams)
- [x] SchoolClass model (academic year + term + stream combination)
- [x] Subject model (subjects taught in school)
- [x] Student model (enrolled in classes, has parents)
- [x] Teacher model (teaches subjects, assigned to classes)
- [x] ParentGuardian model (connects to multiple students)

### Phase 3: Database Migrations
- [x] Users table (with Fortify columns for 2FA)
- [x] Academic structure tables (academic_years, terms, streams, school_classes)
- [x] Subject management tables
- [x] Student profiles and enrollment tables
- [x] Teacher employment and assignment tables
- [x] Parent/guardian relationship tables
- [x] Pivot tables for many-to-many relationships
- [x] Permission tables (Spatie)

### Phase 4: Authorization & Policies
- [x] Route-level permission middleware
- [x] Model policies:
  - AcademicYearPolicy (admin-only access)
  - SchoolClassPolicy (admin/teacher/student/parent read)
  - StudentPolicy (admin/teacher/parent read, admin write)
  - SubjectPolicy (admin/teacher/student read, admin write)
  - TeacherPolicy (all authenticated roles can view)
- [x] Livewire component authorization guards
- [x] Feature tests for authorization boundaries

### Phase 5: Public & LMS Layouts
- [x] Public website layout (header, hero section, footer)
- [x] LMS dashboard layout (sidebar navigation, top bar)
- [x] Login/authentication views
- [x] Homepage component with Livewire
- [x] Dashboard stub pages

---

## 🚀 PENDING PHASES (To Be Implemented)

### Phase 6: Student Management Module
**Routes**: `/lms/students`  
**Responsibility**: School admin and teachers manage student records

**Components to build**:
- Student list (with filtering, search, pagination)
- Student profile view (personal info, photo, admission details)
- Student enrollment form (add new student)
- Student edit form (update admission number, class, stream, status)
- Student deletion (soft delete with audit trail)

**Database tables**: students, student_profiles (if extended details needed)

**Policies to implement**:
- viewAny: school_admin, teacher
- view: school_admin, teacher (can only see students in their class)
- create: school_admin
- update: school_admin
- delete: school_admin

---

### Phase 7: Teacher Management Module
**Routes**: `/lms/teachers`  
**Responsibility**: School admin manages teacher employment records and assignments

**Components to build**:
- Teacher list (filter by subject, class, status)
- Teacher profile page (personal info, qualifications, photo, contact)
- Teacher registration form (hire new teacher)
- Teacher assignment form (assign to classes and subjects)
- Teacher deletion/termination (soft delete)

**Database tables**: teachers, teacher_qualifications, teacher_leaves (if needed)

**Policies to implement**:
- viewAny: school_admin
- view: school_admin, own teacher record
- create: school_admin
- update: school_admin
- delete: school_admin

---

### Phase 8: Class & Subject Management Module
**Routes**: `/lms/classes`, `/lms/subjects`  
**Responsibility**: Admin configures academic structure

**Components to build**:
- Class list with filters (academic year, term, stream)
- Class form (create/edit class)
- Subject list with filters
- Subject form (create/edit subject, assign to classes)
- Class-teacher assignment form
- Class-subject assignment form

**Database tables**: 
- school_classes, class_student, class_teacher, class_subject
- subjects, subject_teacher

**Policies to implement**:
- All CRUD operations restricted to school_admin only

---

### Phase 9: Assignment & Homework Module
**Routes**: `/lms/assignments`  
**Responsibility**: Teachers create and manage assignments; students submit and view feedback

**Features**:
- Assignment creation (title, description, attachments, due date)
- Submission tracking (student submissions with timestamps)
- Grading interface (teacher marks and feedback)
- Submission history (version control for draft saves)
- Late submission handling
- Bulk assignment operations (assign to entire class)

**New database tables to create**:
- assignments (id, class_id, subject_id, teacher_id, title, description, due_date, created_at, updated_at)
- assignment_submissions (id, assignment_id, student_id, file_path, submitted_at, status)
- assignment_grades (id, submission_id, score, feedback, graded_at, graded_by)

**Policies to implement**:
- viewAny: school_admin, teacher (own), student (enrolled class)
- create: teacher
- update: teacher (own assignment)
- delete: teacher, school_admin
- grade: teacher (own assignment)

---

### Phase 10: Quiz & Assessment Module
**Routes**: `/lms/quizzes`  
**Responsibility**: Teachers create online quizzes; students take tests

**Features**:
- Quiz builder (multiple choice, short answer, essay questions)
- Question bank management (reusable questions across quizzes)
- Quiz scheduling (open/close dates, time limits)
- Automated grading (multiple choice auto-score)
- Manual grading interface (essay questions)
- Quiz analytics dashboard (performance by student/question)
- Student quiz attempt history

**New database tables to create**:
- quizzes (id, class_id, subject_id, teacher_id, title, description, open_at, close_at, time_limit_minutes, passing_score, created_at)
- quiz_questions (id, quiz_id, question_text, question_type, marks, order, created_at)
- quiz_options (id, quiz_question_id, option_text, is_correct, order)
- quiz_attempts (id, quiz_id, student_id, started_at, submitted_at, score, status)
- quiz_answers (id, attempt_id, question_id, student_answer, score, feedback)

**Policies to implement**:
- Similar to assignments but with additional time-window checks

---

### Phase 11: Attendance Tracking Module
**Routes**: `/lms/attendance`  
**Responsibility**: Teachers mark daily attendance; admin/parents view reports

**Features**:
- Daily attendance marking (per class per day)
- Bulk attendance operations (mark all present, all absent)
- Attendance calendar view
- Attendance reports (by student, by class, by date range)
- Parent notification alerts (high absence rates)
- Excused vs unexcused absence tracking

**New database tables to create**:
- attendance_records (id, class_id, student_id, date, status, marked_by, notes, created_at)
- attendance_status_codes (id, code, name, is_present, color_code) - lookup table

**Policies to implement**:
- viewAny: school_admin, teacher (own class), parent (own children)
- create: teacher
- update: teacher, school_admin
- delete: school_admin

---

### Phase 12: Grade & Results Module
**Routes**: `/lms/grades`, `/lms/result-cards`  
**Responsibility**: Teachers enter grades; students/parents view results and report cards

**Features**:
- Grade entry form (per student per subject)
- Grade scale configuration (A+, A, B+, B, etc. with points)
- Grade statistics (class average, distribution)
- Report card generation (PDF export)
- Result publication workflow (draft → reviewed → published)
- Parent result notification

**New database tables to create**:
- grade_scales (id, school_id, name, min_score, max_score, grade_letter, gpa_points)
- student_grades (id, student_id, class_id, subject_id, term_id, score, grade_letter, entered_by, created_at)
- report_cards (id, student_id, academic_year_id, term_id, generated_at, pdf_path, status)
- report_card_details (id, report_card_id, subject_id, score, grade, remarks)

**Policies to implement**:
- viewAny: school_admin, teacher, student (own), parent (own children)
- create: teacher
- update: teacher, school_admin (with approval workflow)
- publish: school_admin

---

### Phase 13: Timetable & Schedule Management
**Routes**: `/lms/timetables`  
**Responsibility**: Admin creates school timetable; teachers/students view schedules

**Features**:
- Timetable builder (drag-and-drop schedule creation)
- Period/slot configuration
- Subject-class-teacher assignment to time slots
- Break/holiday management
- Timetable publishing workflow
- Student class schedule view
- Teacher schedule view with room assignments

**New database tables to create**:
- schedule_periods (id, name, start_time, end_time, order)
- timetables (id, academic_year_id, term_id, created_at, status)
- timetable_entries (id, timetable_id, class_id, period_id, day_of_week, subject_id, teacher_id, room_number)
- holidays (id, name, date, duration_days, type)

**Policies to implement**:
- viewAny: all authenticated users
- create/update/delete: school_admin only

---

### Phase 14: Notifications & Messaging
**Routes**: `/lms/notifications`, `/lms/messages`  
**Responsibility**: System notifies users of important events; enables communication

**Features**:
- In-app notifications (assignments due, grades published, attendance alerts)
- Email notifications (configurable preferences)
- SMS notifications (for critical alerts)
- Teacher-parent messaging (per student)
- Class announcements (broadcast to students)
- Notification preferences center
- Notification read/unread tracking

**New database tables to create**:
- notifications (id, user_id, type, title, message, data, is_read, created_at)
- notification_preferences (id, user_id, channel, event_type, enabled)
- messages (id, from_user_id, to_user_id, subject, body, student_id, is_read, created_at)
- announcements (id, class_id, teacher_id, title, content, published_at)

**Queue jobs to create**:
- SendEmailNotificationJob
- SendSmsNotificationJob
- SendNotificationJob (abstract base)

**Policies to implement**:
- view: own notifications only
- delete: own notifications

---

### Phase 15: School Settings & Configuration
**Routes**: `/lms/settings`  
**Responsibility**: Super admin configures school parameters

**Features**:
- School basic info (name, logo, address, contact)
- Academic calendar (define academic years, terms)
- Grade scales (define grading system)
- Notification settings (email templates, SMS templates)
- User role configuration (permissions per role)
- System backup and restore
- Audit logs view

**Database tables**:
- settings (key, value, type) - or use dedicated tables per setting

**Policies**:
- super_admin only access

---

### Phase 16: Reporting & Analytics Dashboard
**Routes**: `/lms/reports`, `/lms/analytics`  
**Responsibility**: Admin and teachers get insights into school performance

**Reports to build**:
1. **Academic Performance**
   - Student progress report (subject-wise performance)
   - Class performance comparison (avg scores by class)
   - Grade distribution (how many A's, B's, C's etc)

2. **Attendance Analytics**
   - Attendance rates by class
   - Chronic absentees report
   - Monthly attendance trends

3. **Assignment Tracking**
   - Submission rates per assignment
   - Average scores
   - Late submission tracking

4. **Teacher Workload**
   - Classes assigned per teacher
   - Assignment quantity per teacher
   - Grading completion status

5. **System Usage**
   - Login statistics
   - Feature usage metrics
   - Active users dashboard

**Database considerations**:
- May need to cache/aggregate data in statistics tables
- Consider data warehousing approach for large schools

**Policies**:
- school_admin: see all reports
- teacher: see own class reports only
- parent: see own child reports only

---

## 📁 Migration Checklist (Create In Order)

1. ✅ `create_users_table` - Existing (Fortify)
2. ✅ `create_cache_table` - Existing
3. ✅ `create_jobs_table` - Existing
4. ✅ `add_two_factor_columns_to_users_table` - Existing
5. ✅ `create_passkeys_table` - Existing
6. ✅ `create_permission_tables` - Existing (Spatie)
7. ✅ `2026_08_14_080000_create_school_structure_tables` - Done
8. ⏳ `2026_08_15_000000_create_assignments_table`
9. ⏳ `2026_08_15_010000_create_quizzes_table`
10. ⏳ `2026_08_15_020000_create_attendance_table`
11. ⏳ `2026_08_15_030000_create_grades_table`
12. ⏳ `2026_08_15_040000_create_timetable_tables`
13. ⏳ `2026_08_15_050000_create_notifications_table`

---

## 🏗️ Directory Structure (Models & Controllers)

```
app/
├── Models/
│   ├── User.php ✅
│   ├── AcademicYear.php ✅
│   ├── Term.php ✅
│   ├── Stream.php ✅
│   ├── SchoolClass.php ✅
│   ├── Subject.php ✅
│   ├── Student.php ✅
│   ├── Teacher.php ✅
│   ├── ParentGuardian.php ✅
│   ├── Assignment.php (⏳ Phase 6)
│   ├── Quiz.php (⏳ Phase 10)
│   ├── AttendanceRecord.php (⏳ Phase 11)
│   ├── StudentGrade.php (⏳ Phase 12)
│   ├── TimetableEntry.php (⏳ Phase 13)
│   ├── Notification.php (⏳ Phase 14)
│   └── ...

├── Livewire/
│   ├── Website/
│   │   └── HomePage.php ✅
│   └── LMS/
│       ├── Dashboard.php ✅
│       ├── Students/
│       │   ├── Index.php (⏳ Phase 6)
│       │   ├── Create.php
│       │   ├── Edit.php
│       │   └── Show.php
│       ├── Teachers/
│       │   ├── Index.php (⏳ Phase 7)
│       │   └── ...
│       ├── Classes/
│       │   ├── Index.php (⏳ Phase 8)
│       │   └── ...
│       ├── Subjects/
│       │   ├── Index.php (⏳ Phase 8)
│       │   └── ...
│       ├── Assignments/
│       │   ├── Index.php (⏳ Phase 9)
│       │   ├── Create.php
│       │   ├── View.php (student view)
│       │   └── Grade.php (teacher grading)
│       ├── Quizzes/
│       │   ├── Index.php (⏳ Phase 10)
│       │   ├── TakeQuiz.php
│       │   └── ResultsView.php
│       ├── Attendance/
│       │   ├── Mark.php (⏳ Phase 11)
│       │   └── View.php
│       ├── Grades/
│       │   ├── Enter.php (⏳ Phase 12)
│       │   └── View.php
│       ├── Timetables/
│       │   ├── View.php (⏳ Phase 13)
│       │   └── ...
│       └── Settings/
│           └── Index.php (⏳ Phase 15)

├── Policies/
│   ├── AcademicYearPolicy.php ✅
│   ├── SchoolClassPolicy.php ✅
│   ├── StudentPolicy.php ✅
│   ├── TeacherPolicy.php ✅
│   ├── SubjectPolicy.php ✅
│   ├── AssignmentPolicy.php (⏳)
│   ├── QuizPolicy.php (⏳)
│   ├── AttendancePolicy.php (⏳)
│   ├── GradePolicy.php (⏳)
│   └── ...

└── Http/
    └── Controllers/
        (Optional: can use Livewire-only pattern for simpler features)
```

---

## 🧪 Testing Strategy

**Current Test Coverage**:
- ✅ SchoolFoundationTest (public homepage, auth gate, roles)
- ✅ LmsAuthorizationTest (policy enforcement)

**Tests to Add**:
- StudentManagementTest
- TeacherManagementTest
- AssignmentSubmissionTest
- QuizTakingTest
- AttendanceMarkingTest
- GradeEntryTest
- NotificationDispatchTest
- ... and more per feature

**Test Pattern**:
- Use RefreshDatabase trait for isolation
- Seed test roles in setUp()
- Test both authorized and unauthorized access paths
- Verify data integrity after operations

---

## 🔐 Security Considerations

1. **Authentication**: ✅ Fortify handles login/2FA/password reset
2. **Authorization**: ✅ Policies enforce access control
3. **Data Isolation**: Implement query scoping so teachers only see their students
4. **Audit Trail**: Track who made changes to sensitive data (grades, attendance)
5. **File Security**: Validate and store uploads securely (assignments, student photos)
6. **CSRF Protection**: ✅ Laravel default
7. **Rate Limiting**: Apply to sensitive endpoints (login, password reset)
8. **Soft Deletes**: Use SoftDeletes for student/teacher records (never permanently delete)

---

## 📦 Dependencies in Use

- **Laravel Framework**: 12.66.0
- **Laravel Fortify**: Authentication scaffolding
- **Spatie Laravel Permission**: Role and permission management
- **Livewire**: Real-time reactive components
- **Tailwind CSS**: Styling framework
- **Vite**: Asset bundling

**Optional Future**: Queue system (for bulk notifications), Excel exports, PDF generation

---

## 🚢 Deployment Checklist

- [ ] Set environment variables (.env)
- [ ] Configure database connection
- [ ] Run migrations: `php artisan migrate`
- [ ] Seed initial data: `php artisan db:seed`
- [ ] Build front-end assets: `npm run build`
- [ ] Set up file storage (for uploads)
- [ ] Configure email service (for notifications)
- [ ] Set up cron for scheduled tasks if needed
- [ ] Enable HTTPS and security headers
- [ ] Test authentication and authorization flows

---

## 📞 Quick Links

- **Routes**: [routes/web.php](routes/web.php)
- **Database Migrations**: [database/migrations/](database/migrations/)
- **Models**: [app/Models/](app/Models/)
- **Policies**: [app/Policies/](app/Policies/)
- **Livewire Components**: [app/Livewire/](app/Livewire/)
- **Tests**: [tests/Feature/](tests/Feature/)

---

## 📝 Notes

- All timestamps use Laravel's default `created_at` and `updated_at`
- Soft deletes enabled for student, teacher, and parent records
- Role-based access control through Spatie Permission
- Frontend-first approach using Livewire components
- Database-agnostic migrations (work with MySQL, SQLite, PostgreSQL)

---

**Next Action**: Implement Phase 6 (Student Management Module) with full CRUD interface and role-based access.
