# Sidebar Navigation Visual Reference

```
┌─────────────────────────────────────────┐
│  📦 BrightStar LMS                      │
│     School portal                       │
├─────────────────────────────────────────┤
│                                         │
│  MAIN                                   │
│  ├─ 📊 Dashboard                        │
│                                         │
│  ACADEMIC SETUP                         │
│  ├─ 📅 Academic Years                   │
│  ├─ 🗓️  Terms                            │
│  ├─ 🏛️  Classes                         │
│  ├─ 🌊 Streams                          │
│  └─ 📚 Subjects                         │
│                                         │
│  PEOPLE                                 │
│  ├─ 👨‍🎓 Students                       │
│  ├─ 👨‍🏫 Teachers                       │
│  └─ 👨‍👩‍👧 Parents/Guardians              │
│                                         │
│  LEARNING CONTENT                       │
│  ├─ 📖 Lessons                          │
│  └─ 🔖 Topics                           │
│                                         │
│  ASSESSMENTS                            │
│  ├─ ✍️  Assignments                      │
│  ├─ ❓ Quizzes                           │
│  ├─ 📝 Examinations                     │
│  └─ 📊 Assessments                      │
│                                         │
│  SCHOOL RECORDS                         │
│  ├─ ✅ Attendance                        │
│  ├─ ⏰ Timetables                        │
│  └─ 📋 Reports                          │
│                                         │
│  COMMUNICATION                          │
│  ├─ 📢 Announcements                    │
│  └─ 🔔 Notifications                    │
│                                         │
│  ADMINISTRATION                         │
│  ├─ 👥 Users                            │
│  ├─ 🎭 Roles                            │
│  ├─ 🔐 Permissions                      │
│  ├─ ⚙️  School Setup                     │
│  └─ 🔧 Settings                         │
│                                         │
└─────────────────────────────────────────┘

Active Link Example:
┌─────────────────────────────────────────┐
│  📊 Dashboard    ← Blue background      │
│  👨‍🎓 Students     ← White bold text      │
└─────────────────────────────────────────┘

Inactive Link Example:
┌─────────────────────────────────────────┐
│  👨‍🏫 Teachers     ← Gray text           │
│  🏛️  Classes      ← Hover for highlight │
└─────────────────────────────────────────┘
```

## Sidebar Implementation Details

### Component Path
```
resources/views/components/sidebar.blade.php
```

### Usage in Layout
```blade
<!-- resources/views/layouts/lms.blade.php -->
<x-sidebar />
```

### Active Link Detection
```blade
@if(request()->routeIs('lms.dashboard'))
    <!-- Active styling -->
@else
    <!-- Inactive styling -->
@endif
```

### Route Names Used
```
lms.dashboard
lms.academic-years.index
lms.terms.index
lms.classes.index
lms.streams.index
lms.subjects.index
lms.students.index
lms.teachers.index
lms.parents.index
lms.lessons.index
lms.topics.index
lms.assignments.index
lms.quizzes.index
lms.examinations.index
lms.assessments.index
lms.attendance.index
lms.timetables.index
lms.reports.index
lms.announcements.index
lms.notifications.index
lms.users.index
lms.roles.index
lms.permissions.index
lms.school-setup
lms.settings.index
```

## Responsive Behavior

```
Desktop (md+)          Mobile
┌──────┬────────┐    ┌────────┐
│      │        │    │        │
│ Side │ Content│    │ Mobile │ (Sidebar hidden)
│ bar  │        │    │ Content│
│      │        │    │        │
└──────┴────────┘    └────────┘
```

- Sidebar is **hidden** on mobile (<768px)
- Sidebar is **visible** on medium+ screens (≥768px)
- Main content has **left padding** (md:pl-72) to accommodate sidebar

## Styling Classes

```blade
<!-- Container -->
<aside class="fixed inset-y-0 left-0 hidden w-72 border-r border-slate-200 bg-slate-900 text-slate-100 md:block overflow-y-auto">

<!-- Logo Section -->
<div class="flex items-center gap-3 border-b border-slate-800 px-5 py-5">

<!-- Navigation Container -->
<nav class="space-y-6 px-4 py-6 text-sm">

<!-- Section Title -->
<p class="mb-2 px-3 text-xs font-semibold uppercase tracking-widest text-slate-500">SECTION NAME</p>

<!-- Link Container -->
<div class="space-y-1">

<!-- Individual Link -->
<a href="{{ route('route.name') }}" 
   class="block rounded-lg px-3 py-2 @if(request()->routeIs('route.*')) bg-slate-800 font-medium text-white @else text-slate-300 hover:bg-slate-800 hover:text-white @endif">
   📦 Item Name
</a>
```

## Colors & Theme

| Element | Color | Class |
|---------|-------|-------|
| Background | Dark Slate | `bg-slate-900` |
| Border | Light Slate | `border-slate-800` |
| Text (Default) | Light Slate | `text-slate-100` |
| Text (Inactive) | Lighter Slate | `text-slate-300` |
| Active BG | Darker Slate | `bg-slate-800` |
| Hover BG | Darker Slate | `hover:bg-slate-800` |
| Section Labels | Gray Slate | `text-slate-500` |

## Performance Considerations

- **Fixed Position**: Sidebar stays in place while scrolling
- **Overflow Scroll**: Navigation scrolls independently if too long
- **No JavaScript**: Pure CSS/Blade implementation
- **Lightweight**: Single component file
- **No External Scripts**: Uses only Tailwind CSS

---

**Total Modules Accessible from Sidebar: 25**
**Total Routes: 26 (including dashboard)**
**Estimated Setup Complete: ✅**
