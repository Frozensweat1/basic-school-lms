# LMS Sidebar Navigation Structure

## Overview
The sidebar has been extracted into a reusable partial component at `resources/views/components/sidebar.blade.php` and includes comprehensive navigation links to all super admin modules.

## Sidebar Layout

### 📊 Main
- **Dashboard** → `/lms/dashboard` (lms.dashboard)

### 📅 Academic Setup
- **Academic Years** → `/lms/academic-years` (lms.academic-years.index)
- **Terms** → `/lms/terms` (lms.terms.index)
- **Classes** → `/lms/classes` (lms.classes.index)
- **Streams** → `/lms/streams` (lms.streams.index)
- **Subjects** → `/lms/subjects` (lms.subjects.index)

### 👥 People
- **Students** → `/lms/students` (lms.students.index)
- **Teachers** → `/lms/teachers` (lms.teachers.index)
- **Parents/Guardians** → `/lms/parents` (lms.parents.index)

### 📖 Learning Content
- **Lessons** → `/lms/lessons` (lms.lessons.index)
- **Topics** → `/lms/topics` (lms.topics.index)

### 📊 Assessments
- **Assignments** → `/lms/assignments` (lms.assignments.index)
- **Quizzes** → `/lms/quizzes` (lms.quizzes.index)
- **Examinations** → `/lms/examinations` (lms.examinations.index)
- **Assessments** → `/lms/assessments` (lms.assessments.index)

### 📋 School Records
- **Attendance** → `/lms/attendance` (lms.attendance.index)
- **Timetables** → `/lms/timetables` (lms.timetables.index)
- **Reports** → `/lms/reports` (lms.reports.index)

### 📢 Communication
- **Announcements** → `/lms/announcements` (lms.announcements.index)
- **Notifications** → `/lms/notifications` (lms.notifications.index)

### ⚙️ Administration
- **Users** → `/lms/users` (lms.users.index)
- **Roles** → `/lms/roles` (lms.roles.index)
- **Permissions** → `/lms/permissions` (lms.permissions.index)
- **School Setup** → `/lms/school-setup` (lms.school-setup)
- **Settings** → `/lms/settings` (lms.settings.index)

## Files Modified/Created

### Modified
- `resources/views/layouts/lms.blade.php` - Now uses `<x-sidebar />` component
- `routes/web.php` - Added all 24+ module routes with proper middleware
- `app/Providers/AppServiceProvider.php` - Added Stream policy registration

### Created
- `resources/views/components/sidebar.blade.php` - Sidebar partial with all navigation
- `app/Livewire/LMS/Streams/Index.php` - Streams module component
- `resources/views/livewire/lms/streams/index.blade.php` - Streams module view
- `app/Policies/StreamPolicy.php` - Stream model policy for authorization

## Active Link Detection

Each navigation link uses `request()->routeIs()` to detect if it's currently active:

```blade
@if(request()->routeIs('lms.academic-years.*')) 
    bg-slate-800 font-medium text-white 
@else 
    text-slate-300 hover:bg-slate-800 hover:text-white 
@endif
```

Active links show:
- Dark background (bg-slate-800)
- White text with bold font
- Hover effects don't apply (already highlighted)

Inactive links show:
- Slate text (text-slate-300)
- Hover to dark background
- Hover to white text

## Route Protection

All routes are protected with:
1. `middleware(['auth'])` - Must be logged in
2. Policy middleware where applicable - `middleware('can:viewAny,Model::class')`

Example:
```php
Route::get('/lms/academic-years', AcademicYearsIndex::class)
    ->middleware('can:viewAny,App\\Models\\AcademicYear')
    ->name('lms.academic-years.index');
```

## Component Structure

All Livewire components follow the modern Livewire 4.x pattern:

```php
#[Layout('layouts.lms')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.lms.module.index', [
            'data' => Model::get(),
        ]);
    }
}
```

## Sidebar Styling

- **Width**: `w-72` (288px)
- **Position**: Fixed left sidebar, hidden on mobile, visible on md+ screens
- **Background**: Dark slate (`bg-slate-900`)
- **Text**: Light slate (`text-slate-100`)
- **Border**: Light slate border on right
- **Scrolling**: `overflow-y-auto` for long lists

## Usage in Layout

```blade
<!-- resources/views/layouts/lms.blade.php -->
<body>
    <div class="min-h-screen">
        <x-sidebar />  <!-- Sidebar partial -->
        
        <div class="md:pl-72">  <!-- Content area with left padding -->
            <header>...</header>
            <main>{{ $slot }}</main>
        </div>
    </div>
</body>
```

## Testing

All changes validated with existing test suite:
- ✅ UI Component tests pass
- ✅ Routes resolve correctly
- ✅ No breaking changes to layout

## Next Steps

1. **Implement CRUD operations** for each module
2. **Add create/edit/delete** functionality with LivewireAlert notifications
3. **Implement policies** for all remaining modules
4. **Add form validation** and error handling
5. **Connect to database** records

---

**Sidebar partial is now reusable and can be included in any layout as `<x-sidebar />`**
