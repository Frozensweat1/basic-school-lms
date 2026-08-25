<?php

namespace App\Livewire\LMS\Announcements;

use App\Models\Announcement;
use App\Models\ClassSubject;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;

#[Layout('layouts.lms')]
class Feed extends Index
{
    public function mount(): void
    {
        $this->authorize('viewAny', Announcement::class);
    }

    public function render()
    {
        $filtered = $this->announcementQuery()
            ->with(['author', 'schoolClass', 'subject', 'attachments'])
            ->when(filled($this->search), function (Builder $query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(fn (Builder $items) => $items
                    ->where('title', 'like', $term)
                    ->orWhere('content', 'like', $term)
                    ->orWhereHas('author', fn (Builder $authors) => $authors->where('name', 'like', $term)));
            })
            ->when(filled($this->filterAudience), fn (Builder $query) => $query->where('audience', $this->filterAudience));

        return view('livewire.lms.announcements.feed', [
            'announcements' => (clone $filtered)->latest('published_at')->paginate(12),
            'announcementCount' => (clone $filtered)->count(),
            'schoolCount' => (clone $filtered)->where('audience', 'school')->count(),
            'targetedCount' => (clone $filtered)->where('audience', '!=', 'school')->count(),
        ]);
    }

    protected function announcementQuery(): Builder
    {
        $user = auth()->user();
        $student = $user->student;
        $parent = $user->parentGuardian;
        $teacher = $user->teacher;
        $schoolId = (int) ($student?->school_id ?? $parent?->school_id ?? $teacher?->school_id ?? $this->schoolId());
        $query = Announcement::query()->where('school_id', $schoolId)->published();

        if ($user->hasAnyRole(['super_admin', 'school_admin'])) {
            return $query;
        }

        if ($teacher) {
            $classIds = $teacher->classSubjects()->pluck('school_class_id');
            $subjectIds = $teacher->classSubjects()->pluck('subject_id');

            return $query->where(function (Builder $items) use ($classIds, $subjectIds): void {
                $items->whereIn('audience', ['school', 'teachers'])
                    ->orWhere(fn (Builder $classes) => $classes->where('audience', 'class')->whereIn('school_class_id', $classIds))
                    ->orWhere(fn (Builder $subjects) => $subjects->where('audience', 'subject')->whereIn('subject_id', $subjectIds));
            });
        }

        $students = $student
            ? collect([$student])
            : ($parent?->students()->where('students.status', 'active')->get() ?? collect());
        $classIds = $students
            ->flatMap(fn ($profile) => $profile->enrollments()->where('status', 'active')->pluck('school_class_id'))
            ->unique()->values();
        $subjectIds = ClassSubject::query()->whereIn('school_class_id', $classIds)->pluck('subject_id')->unique()->values();

        return $query->where(function (Builder $items) use ($classIds, $subjectIds): void {
            $items->where('audience', 'school')
                ->orWhere(fn (Builder $classes) => $classes->where('audience', 'class')->whereIn('school_class_id', $classIds))
                ->orWhere(fn (Builder $subjects) => $subjects->where('audience', 'subject')->whereIn('subject_id', $subjectIds));
        });
    }
}
