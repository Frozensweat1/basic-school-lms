# Basic School LMS: Coding Assistant Development Prompt

You are an expert Laravel software architect and senior full-stack developer. Your task is to design and implement a **complete Basic School Learning Management System (LMS)** using the technical requirements and architectural conventions below.

## 1. Project Objective

Build a modern, production-ready web application for a basic school. The application must combine:

1. A **public school website/landing page** for visitors, parents and prospective students.
2. A secure **Learning Management System (LMS)** backend for administrators, teachers, students and parents.

The system must be modular, maintainable, scalable and follow Laravel and Livewire best practices.

Do not build the application as a collection of unrelated CRUD pages. Establish a proper domain architecture and reusable components.

---

# 2. Required Technology Stack

Use the following technologies:

* **Laravel 12**
* **PHP 8.3+**
* **MySQL**
* **Livewire 4**
* **Alpine.js**
* **Tailwind CSS**
* **Vite**
* **Laravel Fortify** for authentication
* **Laravel Jetstream** where appropriate for account/profile functionality
* **Spatie Laravel Permission** for roles and permissions
* **Redis** for caching and queues where appropriate

### UI and monitoring additions

* **Flux (free tier only)**, the official Livewire component library, built by the Livewire team on top of Tailwind. Use it as the base for buttons, dropdowns, modals, tooltips, and form inputs instead of building these primitives from scratch. It ships with dark mode support and keyboard/screen-reader accessibility already handled. **Do not purchase a Flux Pro license.** The components Pro would otherwise supply, charts, date and calendar pickers, and a command palette, are built in-house on top of the free tier and standard open-source libraries. Section 22.2 specifies exactly how to build each one.
* **Spatie Laravel Activitylog** for the audit trail (grade changes, attendance overrides, record edits, role changes).
* **Spatie Laravel Backup** for automated database and file backups.
* **Laravel Pulse** for production performance monitoring (slow queries, queue backlogs, exceptions).
* **Laravel Horizon** to manage and monitor the Redis queues already required by this stack.
* **Laravel Telescope** for local/staging debugging only. Never enable it in production.
* **Intervention Image** or **Spatie Media Library** for resizing and compressing student photos and lesson thumbnails on upload.
* **An HTML sanitizer** (for example, `mews/purifier`, a Laravel wrapper around HTMLPurifier) to clean rich text lesson and announcement content before it's stored, since that content renders as HTML later.

### Important frontend requirement

**Tailwind CSS must be used instead of Bootstrap.**

Do not introduce Bootstrap anywhere in the project.

Do not use Tailwind CSS through a CDN.

Tailwind must be properly configured through the project's Vite build process.

---

# 3. Livewire Folder Architecture

This requirement is mandatory.

The Livewire components must be clearly separated into two major areas:

```text
resources/views/livewire/
├── website/
│   └── ...
│
└── LMS/
    └── ...
```

## Website

The `website` directory contains all public-facing landing-page and school website components.

Examples:

```text
resources/views/livewire/website/
├── home.blade.php
├── about.blade.php
├── admissions.blade.php
├── academics.blade.php
├── teachers.blade.php
├── news.blade.php
├── events.blade.php
├── contact.blade.php
└── ...
```

The corresponding Livewire PHP components should follow the same logical namespace/directory organization.

The website must be accessible without authentication.

## LMS

The `LMS` directory contains the authenticated backend application.

Organize it into logical modules rather than placing every component directly inside one directory.

For example:

```text
resources/views/livewire/LMS/
├── Dashboard/
├── Students/
├── Teachers/
├── Parents/
├── Classes/
├── Subjects/
├── AcademicYears/
├── Terms/
├── Attendance/
├── Timetables/
├── Lessons/
├── Topics/
├── Assignments/
├── Quizzes/
├── Examinations/
├── Assessments/
├── Results/
├── Reports/
├── Announcements/
├── Notifications/
├── Users/
├── Roles/
├── Permissions/
└── Settings/
```

Also create a shared `resources/views/components/ui/` directory for any Blade wrapper components that customize Flux defaults (for example, a `<x-ui.stat-card>` built on top of Flux primitives) so the design system stays in one place instead of being re-implemented per module.

Use consistent naming conventions throughout the application.

---

# 4. User Roles

Implement role-based access control using **Spatie Laravel Permission**.

Initial roles should include:

### Super Administrator

Full access to the entire system.

### School Administrator / Headteacher

Manage:

* Students
* Teachers
* Parents
* Classes
* Subjects
* Academic years
* Terms
* Attendance
* Assessments
* Results
* Reports
* Announcements
* School settings

### Teacher

Manage only the academic resources and students relevant to their assigned classes/subjects.

Teachers should be able to:

* View assigned classes
* View students
* Create topics
* Create lessons
* Upload learning resources
* Create assignments
* Grade assignments
* Create quizzes
* Grade assessments
* Record attendance
* Enter marks
* View academic performance

### Student

Students should be able to:

* View their dashboard
* View enrolled classes
* View subjects
* View lessons
* Download learning resources
* Submit assignments
* Take quizzes
* View results
* View announcements
* View attendance

### Parent/Guardian

Parents should be able to:

* View their children
* Monitor academic performance
* View attendance
* View assignments
* View quiz results
* View examination results
* View teacher comments
* View school announcements

Additional roles such as Accountant, Librarian and ICT Administrator may be added later.

---

# 5. School Structure

The system must support:

```text
School
 └── Academic Year
      └── Term
           └── Class
                └── Stream
                     └── Subjects
                          └── Topics
                               └── Lessons
```

Do not hard-code classes such as Basic 1, Basic 2, etc.

The administrator must be able to configure the school's classes.

For example:

```text
KG 1
KG 2
Basic 1
Basic 2
Basic 3
...
Basic 9
```

But another school should be able to configure a different structure.

---

# 6. Academic Year and Terms

Implement academic year management.

Example:

```text
2026/2027
```

Each academic year can contain multiple terms:

```text
Term 1
Term 2
Term 3
```

Administrators must be able to:

* Create academic years
* Set active academic year
* Create terms
* Set active term
* Define term start/end dates
* Lock completed terms

Historical academic records must remain intact when a new academic year becomes active.

---

# 7. Student Management

Create a complete student management module.

Student records should include:

* Student ID
* Admission number
* First name
* Middle name
* Last name
* Date of birth
* Gender
* Photograph
* Admission date
* Current class
* Stream
* Status
* Parent/guardian relationships

Student statuses may include:

```text
Active
Graduated
Transferred
Withdrawn
Suspended
```

Avoid deleting historical student records unnecessarily.

Use appropriate soft-delete/archive strategies where necessary.

---

# 8. Teacher Management

Create a teacher management module.

Teacher records should include:

* Employee ID
* Name
* Photograph
* Contact information
* Subjects taught
* Classes assigned
* Employment information
* User account

Teachers must only have access to resources they are authorized to manage.

---

# 9. Parent/Guardian Management

A parent/guardian may have multiple children.

Therefore, implement a proper many-to-many or equivalent relationship between parents and students.

Example:

```text
Parent
 ├── Child 1
 ├── Child 2
 └── Child 3
```

Parents must never be able to access another parent's children.

---

# 10. Learning Management Structure

The LMS learning structure should be:

```text
Class
 └── Subject
      └── Topic
           └── Lesson
```

A lesson may contain:

* Rich text
* Images
* PDF documents
* Documents
* Presentations
* Audio
* Video
* External links
* Downloadable resources

Lessons should support ordering so teachers can control the learning sequence.

Track student lesson progress where appropriate.

---

# 11. Assignments

Teachers should be able to create assignments containing:

* Title
* Instructions
* Class
* Subject
* Topic
* Lesson
* Due date
* Maximum score
* Attachments
* Submission requirements

Students should be able to:

* Open assignments
* Read instructions
* Download attachments
* Submit answers
* Upload files
* View submission status
* View grades
* View teacher feedback

Support submission states such as:

```text
Draft
Submitted
Late
Graded
Returned
```

---

# 12. Quizzes

Implement a proper quiz engine.

Support:

* Multiple choice
* True/False
* Short answer
* Fill in the blank
* Matching
* Essay

Quiz configuration should support:

* Time limit
* Number of questions
* Pass mark
* Maximum attempts
* Question randomization
* Automatic marking
* Manual marking
* Publication date
* Closing date

Do not store all quiz logic in one Livewire component. Separate the question, attempt, answer and grading responsibilities appropriately.

**Security requirement:** the correct answer, the point value of each option, and any grading key must never be sent to the browser before a question is submitted. Grade every attempt server-side, using the question data pulled fresh from the database, not values echoed back from the client's request.

---

# 13. Examinations and Assessments

Implement a flexible assessment system.

The school should be able to configure assessment components.

Example:

```text
Class Exercise       10%
Assignment           10%
Quiz                 20%
Project              10%
Examination          50%
```

Do not hard-code these percentages.

The administrator should be able to configure the grading/assessment structure.

---

# 14. Results and Grading

Implement:

* Subject marks
* Assessment scores
* Total scores
* Grades
* Remarks
* Teacher comments
* Headteacher comments
* Term results
* Cumulative results

Allow the school to configure grading scales.

For example:

```text
80–100 = A
70–79  = B
60–69  = C
50–59  = D
0–49   = F
```

Do not hard-code this grading scale.

---

# 15. Report Cards

Generate student report cards containing:

* Student information
* Academic year
* Term
* Class
* Subject results
* Total scores
* Grades
* Attendance
* Teacher comments
* Headteacher comments

The report card should be printable and suitable for PDF generation.

Generate report card PDFs through a queued job, not synchronously in the request. A headteacher generating report cards for an entire class or school should not tie up a web worker or time out the request.

---

# 16. Attendance

Teachers should be able to record:

```text
Present
Absent
Late
Excused
```

Track attendance by:

* Student
* Class
* Date
* Term
* Academic year

Provide reports such as:

* Daily attendance
* Monthly attendance
* Term attendance
* Student attendance percentage

Cache the computed attendance percentages rather than recalculating them from raw records on every dashboard load. Invalidate the cache when new attendance is recorded for that student.

---

# 17. Timetable

Implement a timetable module supporting:

* Class timetable
* Teacher timetable
* Subject
* Teacher
* Day
* Start time
* End time
* Room/location

Avoid timetable conflicts.

For example, a teacher should not be assigned to two classes at the same time.

---

# 18. Announcements

Implement:

* School-wide announcements
* Class announcements
* Subject announcements
* Teacher announcements

Announcements should support:

* Title
* Content
* Target audience
* Publication date
* Expiration date
* Attachments

Sanitize announcement content through the HTML sanitizer before storage (see Section 24.5).

---

# 19. Notifications

Implement Laravel Notifications for relevant events.

Examples:

* New assignment
* Assignment deadline
* Assignment graded
* New quiz
* New result
* School announcement
* Attendance notification

Use queued notifications where appropriate.

---

# 20. Dashboards

Create role-specific dashboards.

For any chart or graph on a dashboard, use Chart.js wrapped in the shared `<x-ui.chart>` component described in Section 22.2. Keep the JavaScript footprint small and don't pull in a heavier charting library for what is usually a handful of bar and line charts.

### Administrator Dashboard

Display:

* Total students
* Total teachers
* Total classes
* Total subjects
* Attendance overview
* Academic performance
* Pending submissions
* Recent announcements

### Teacher Dashboard

Display:

* Assigned classes
* Assigned subjects
* Pending assignments to grade
* Upcoming quizzes
* Recent submissions
* Attendance tasks

### Student Dashboard

Display:

* Current class
* Subjects
* Upcoming assignments
* Pending assignments
* Upcoming quizzes
* Recent results
* Lesson progress
* Announcements

### Parent Dashboard

Display:

* Children
* Attendance
* Academic performance
* Pending assignments
* Recent results
* Announcements

---

# 21. Public School Website

The `website` Livewire section must be completely separate from the authenticated LMS.

Include pages such as:

```text
Home
About Us
Academics
Admissions
Teachers
News
Events
Gallery
Contact
```

The website should have:

* Responsive design
* Tailwind CSS
* Modern school-oriented design
* Mobile navigation
* Hero section
* School information
* Featured programs
* Announcements/news
* Events
* Contact information
* Footer

The public website must not expose LMS data that requires authentication.

---

# 22. UI/UX Requirements

Use **Tailwind CSS exclusively**. Do not use Bootstrap.

### 22.1 Design system

Before writing UI code, define an explicit design system and stick to it:

* **Color:** pick one primary color, one secondary color, one accent, and semantic colors for success, warning, danger, and info, as named hex values in `tailwind.config`. Draw the palette from the actual school's identity (crest, uniform colors, existing branding) where one exists. Don't default to Tailwind's stock indigo-500/violet-500 combination or a generic AI-tool look, such as a cream background with a terracotta accent, or a near-black background with a single neon accent. These read as templated rather than designed for this school.
* **Typography:** two typefaces maximum, one for headings and one for body text, self-hosted through Vite rather than pulled from a font CDN at request time. Schools frequently run on slow or capped internet connections, so every external request is a point of failure. Define a type scale (for example 12/14/16/18/24/32/40px) and use it consistently instead of arbitrary font sizes.
* **Spacing:** stick to Tailwind's default 4px-based spacing scale throughout. Don't introduce one-off spacing values per component.
* **Icons:** one icon set used everywhere (Heroicons, which Flux already uses, is the natural default).

### 22.2 Component library

Use Flux (see Section 2) as the base layer for buttons, dropdowns, modals, tooltips, form inputs, and tabs. Build school-specific composites (stat cards, gradebook cells, attendance grids) as wrapper components in `resources/views/components/ui/` on top of Flux primitives, so the visual language stays consistent without every module reinventing the same button.

The following components would normally come from Flux Pro. Since this project isn't licensing Pro, build each one in-house, once, as a shared component, rather than solving it separately in every module that needs it:

* **Charts.** Wrap Chart.js in a single `<x-ui.chart>` Blade/Alpine component that accepts a chart type and a data array as props, and re-renders when the underlying Livewire data changes. Every dashboard chart (attendance trends, class performance, pending submissions) consumes this one component instead of touching Chart.js directly.
* **Date and time fields.** For due dates, dates of birth, term start/end dates, and timetable slots, use a styled native `<input type="date">` or `<input type="time">` wrapped in `<x-ui.date-input>`. Native inputs are free, fully keyboard-accessible, and correct on mobile out of the box, so there's no real need for a JavaScript date picker here.
* **Calendar view.** For the one or two places that genuinely need a month grid (the events page on the public website, an attendance history view), build a single reusable Livewire component that computes the grid with Carbon, which already ships with Laravel. Keep the state (current month, selected date) in that one component and reuse it, rather than building a calendar per module.
* **Command palette.** This is the lowest-priority item of the four; a basic school's admin and teacher users are unlikely to lean on a Cmd+K launcher the way a developer tool's users would. Treat it as optional scope for a later stage. If it's built, keep it simple: an Alpine.js modal, opened with a keyboard shortcut, that does a client-side filter over a static list of named routes and actions. It doesn't need fuzzy-matching or a backend search.

### 22.3 Accessibility

Target WCAG 2.1 AA across both the website and the LMS:

* Text contrast of at least 4.5:1 for body text and 3:1 for large text and icons.
* Every interactive element reachable and operable by keyboard, with a visible focus ring (don't remove Tailwind's default focus outline without replacing it).
* Every image has meaningful alt text; decorative images use empty alt text.
* Every form field has an associated `<label>`, not a placeholder standing in for one.
* Respect `prefers-reduced-motion` for any transitions or animated dashboard widgets.

### 22.4 Responsive and mobile-first dashboard

Teachers taking attendance between classes and parents checking results are mostly on phones. Design the LMS dashboard mobile-first:

* Single-column, stacked layout below 768px.
* The sidebar collapses into a bottom navigation bar or a slide-over drawer on mobile rather than disappearing.
* Use Tailwind's default breakpoints (640 / 768 / 1024 / 1280 / 1536px) rather than inventing custom ones.

### 22.5 Loading, empty, and error states

* For anything that takes longer than roughly 300ms, show a skeleton placeholder shaped like the real content using `wire:loading`, not a generic spinner.
* Empty states tell the user what to do next, in the interface's own voice: "No assignments yet. Create your first one." with a button, not just "No data."
* Error messages state what happened and how to fix it in plain language. Never surface a raw exception or stack trace to a student, parent, or teacher.

### 22.6 Data tables at scale

Student lists, gradebooks, and attendance registers will regularly run into the thousands of rows:

* Server-side pagination, 25 rows by default, capped at 100 per page even for admin views.
* Sortable columns that persist their state in the query string, so a refresh doesn't lose the sort.
* Sticky header on scroll for long tables.
* Column visibility toggle for wide tables, such as a gradebook spanning many subjects.

### 22.7 File upload UX

* Drag-and-drop with a visible progress bar for uploads.
* Client-side validation (file type, size) before the upload starts, with a clear inline error, not a failure after the file has already transferred.
* Thumbnail preview for image uploads.
* State the maximum file size up front (see Section 24.4 for the actual limits).

---

# 23. Livewire Architecture

Follow Livewire best practices.

Do not create enormous Livewire components containing every operation.

Separate responsibilities appropriately.

For example:

```text
LMS/
└── Students/
    ├── Index
    ├── Create
    ├── Edit
    ├── Show
    └── Components/
```

Use:

* Form Objects where appropriate
* Actions/services for complex business logic
* Policies
* Validation
* Events/listeners where useful
* Computed properties
* Pagination
* Lazy loading where appropriate

Also apply these Livewire-specific performance rules (see Section 27.1 for the full list): debounce text inputs, use `wire:model.blur` instead of `wire:model.live` unless instant feedback is genuinely needed, and add `wire:key` to every item rendered inside a loop.

Avoid putting complex business logic directly inside Blade files.

---

# 24. Security

Implement proper authorization at multiple levels.

Use:

* Authentication
* Roles
* Permissions
* Policies
* Form validation
* CSRF protection
* Secure file uploads
* Authorization checks in Livewire actions
* Rate limiting where appropriate

A student must never be able to manipulate a request and retrieve another student's private records.

The same applies to parents and teachers.

### 24.1 Authentication and session

* Require two-factor authentication (via Fortify) for Super Administrator and School Administrator accounts. Make it available and encouraged, though not mandatory, for teachers.
* Log users out automatically after 30 minutes of inactivity. School computer labs and shared devices make an idle timeout non-negotiable, not a nice-to-have.
* Minimum password length of 10 characters, checked against a breached-password list using Laravel's `Password::uncompromised()` rule.
* Rate limit login attempts (Fortify handles this by default) and rate limit sensitive endpoints such as quiz submission and password reset requests, for example 60 requests per minute per user via throttle middleware.

### 24.2 Authorization

* Check policies inside every Livewire component's `mount()` and inside every action method, not only at the route level. A Livewire component's public methods can be invoked directly by the client, so route middleware alone is not sufficient protection.
* Always scope queries to the authenticated user's own records at the database level (`where('student_id', auth()->user()->student->id)`), never trust an ID passed from the client to decide whose data to return.

### 24.3 Audit logging

Log every grade change, attendance override, and role or permission change using Spatie Activitylog, capturing the actor, the timestamp, and the before/after values. Retain this log indefinitely. These are the records a parent or an inspector may ask to see.

### 24.4 File security

* Store assignment submissions and any personally identifiable documents on a private disk, never the `public` disk.
* Serve private files through signed, time-limited URLs, for example `Storage::temporaryUrl()` on S3-compatible storage, or a signed route if using local disk. A file link that never expires is a permanent public link in practice.
* Validate uploads server-side by sniffing the actual MIME type, not by trusting the file extension.
* Set explicit size limits per context: for example 10MB for assignment attachments, 25MB for lesson video or presentation files.

### 24.5 Content security

* Run all rich text (lesson content, announcements) through the HTML sanitizer before storage. It renders as HTML later and is a stored-XSS vector otherwise.
* Set a Content-Security-Policy header via middleware as a second layer of protection against anything the sanitizer misses.

### 24.6 Quiz integrity

Never send correct answers or point values to the browser before a question is submitted and graded (see Section 12).

### 24.7 Data protection and backups

* This system holds data on minors. Give the School Administrator a documented retention schedule for withdrawn and graduated student records, rather than leaving deletion decisions to whoever happens to be logged in.
* Automate daily backups of the database and file storage using `spatie/laravel-backup`, and test the restore process at least once. A backup that has never been restored is unverified.

---

# 25. Database Design

Before implementing the UI, design the complete relational database.

Expected entities include, but are not limited to:

```text
users
roles
permissions

academic_years
terms

classes
streams
subjects

students
teachers
parents

class_students
class_subjects
teacher_subjects
parent_student

topics
lessons
lesson_resources

assignments
assignment_submissions

questions
quizzes
quiz_questions
quiz_attempts
quiz_answers

assessments
assessment_scores
grading_scales
grades

attendance
attendance_records

timetables

announcements
notifications

activity_log

school_settings
```

`activity_log` is provided by the Spatie Activitylog package (Section 24.3) and uses its own default migration; it doesn't need custom schema design beyond that.

Do not blindly use this list. Review the relationships and normalize the database appropriately before creating migrations.

At minimum, index every foreign key and every column regularly used in a `WHERE` or `ORDER BY` clause on a large table: `student_id`, `class_id`, `academic_year_id`, `term_id`, and `status`, on the `students`, `attendance_records`, `assessment_scores`, and `assignment_submissions` tables in particular.

---

# 26. File Management

Learning resources and assignment submissions may include files.

Implement secure file handling.

Validate:

* File type
* File size
* File name
* User authorization

Do not expose private student submissions through publicly guessable URLs. Use signed, time-limited URLs instead (see Section 24.4).

Use Laravel's filesystem abstraction, targeting S3-compatible object storage in production rather than local disk (see Section 27.4).

---

# 27. Performance

The system should be designed for a school with potentially thousands of students.

Use:

* Database indexes
* Eager loading
* Pagination
* Query optimization
* Redis caching where beneficial
* Queued jobs
* Lazy loading
* Efficient Livewire updates

Avoid N+1 queries.

Do not load entire student populations into memory unnecessarily.

### 27.1 Livewire-specific rules

* Debounce search and filter inputs, for example `wire:model.live.debounce.400ms`, instead of firing a request on every keystroke.
* Default to `wire:model.blur` for standard form fields. Reserve `wire:model.live` for cases where instant feedback genuinely matters.
* Add `wire:key` to every item rendered inside a loop so Livewire can diff correctly instead of re-rendering the whole list.
* Use Livewire's lazy loading for below-the-fold dashboard widgets so the initial page paints fast.
* Cache expensive aggregates, such as attendance percentages and term averages, with a defined TTL, and invalidate that cache when the underlying data changes rather than recomputing on every page load.

### 27.2 Database

* Index every foreign key and every filtered/sorted column on tables that will hold thousands of rows.
* Eager load relationships explicitly with `with()` anywhere a list of students, results, or attendance records is displayed. This is where N+1 queries usually hide.
* Cap and paginate every list server-side, 25 rows by default, hard-capped at 100. For internal exports of an entire table, chunk the query instead of loading it all into memory at once.

### 27.3 Background jobs

Queue anything that takes more than roughly a second: report card PDF generation, bulk notification dispatch, quiz auto-grading for a full class, and bulk imports. Use Horizon to monitor queue health, and give reports their own queue separate from the default one, so a large PDF batch doesn't delay a routine notification.

### 27.4 Assets and files

* Store uploaded files (photos, lesson resources, submissions) on S3-compatible object storage in production. Local disk doesn't scale past a single app server.
* Resize and compress student photos and lesson thumbnails on upload using Intervention Image or Spatie Media Library conversions, instead of serving the original multi-megabyte file every time.
* Self-host fonts and bundle everything through Vite. Skip external CDN requests where possible.

### 27.5 Public website

Cache the mostly-static pages (Home, About, Academics) with a short TTL, for example 15 minutes, and invalidate that cache when an admin publishes news or an event. This content changes rarely but gets requested by every visitor.

### 27.6 Monitoring

Install Laravel Pulse in production to track slow queries, queue backlogs, and exception rates. Keep Telescope for local and staging environments only.

---

# 28. Development Rules

Before writing code:

1. Inspect the existing project.
2. Identify the current Laravel, PHP, Livewire, Tailwind and Vite versions.
3. Do not unnecessarily replace existing working configuration.
4. Do not introduce Bootstrap.
5. Do not use CDN Tailwind.
6. Do not install random admin templates.
7. Do not duplicate components unnecessarily.
8. Follow Laravel conventions.
9. Follow Livewire conventions.
10. Keep website and LMS components separate.
11. Use migrations for database changes.
12. Use seeders for initial system configuration.
13. Use factories for testing data.
14. Use policies and permissions for authorization.
15. Keep business logic out of Blade templates.

---

# 29. Implementation Strategy

Do not attempt to generate the entire application in one uncontrolled operation.

Implement it incrementally.

### Stage 1

Project architecture and configuration.

### Stage 2

Database architecture, migrations, models and relationships.

### Stage 3

Authentication, roles and permissions.

### Stage 4

Public `website` section.

### Stage 5

LMS layout and dashboard architecture.

### Stage 6

School administration modules.

### Stage 7

Student, teacher and parent portals.

### Stage 8

Learning management modules.

### Stage 9

Assignments and quizzes.

### Stage 10

Assessments, grading and report cards.

### Stage 11

Attendance and timetable.

### Stage 12

Notifications and announcements.

### Stage 13

Testing, security review and optimization.

---

# 30. Coding Quality Requirements

All generated code must be:

* Production-oriented
* Maintainable
* DRY
* Properly namespaced
* Properly validated
* Secure
* Testable
* Consistent with Laravel conventions

Do not use shortcuts merely to make the feature appear functional.

When implementing a feature, ensure that:

```text
Migration
    ↓
Model
    ↓
Relationship
    ↓
Policy/Permission
    ↓
Form/Validation
    ↓
Business Logic
    ↓
Livewire Component
    ↓
Blade/Tailwind UI
    ↓
Tests
```

are considered as part of the feature.

---

# 31. Important Instruction to the Coding Assistant

Do not assume requirements that have not been specified.

When a decision materially affects the database architecture or application behavior, explain the proposed approach before making an irreversible architectural decision.

However, do not repeatedly ask for confirmation on minor implementation details. Use established Laravel and Livewire best practices for those decisions.

Maintain a clear separation between:

```text
PUBLIC WEBSITE
resources/views/livewire/website/

AUTHENTICATED LMS
resources/views/livewire/LMS/
```

This separation is a core architectural requirement and must remain intact throughout development.

The final application should feel like **one cohesive school platform**, while the public website and authenticated LMS remain technically and visually organized as separate areas.
