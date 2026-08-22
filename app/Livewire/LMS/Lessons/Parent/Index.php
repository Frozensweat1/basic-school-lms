<?php

namespace App\Livewire\LMS\Lessons\Parent;

use App\Models\{Lesson, LessonProgress, LessonResource, ParentGuardian};
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.lms')]
class Index extends Component
{
    use WithPagination;

    public ParentGuardian $parent;
    public string $studentId = '';
    public ?int $selectedLessonId = null;

    public function mount(): void
    {
        $this->parent = auth()->user()->parentGuardian;
        abort_unless(auth()->user()->hasRole('parent') && $this->parent, 403);
        $this->studentId = (string) ($this->parent->students()->where('students.status', 'active')->value('students.id') ?? '');
    }

    public function updatedStudentId(): void
    {
        $this->selectedLessonId = null;
        $this->resetPage();
        abort_unless($this->studentId === '' || $this->parent->students()->where('students.status', 'active')->whereKey((int) $this->studentId)->exists(), 403);
    }

    public function viewLesson(int $lessonId): void
    {
        $this->selectedLessonId = $this->wardLessons()->whereKey($lessonId)->value('id');
        abort_unless($this->selectedLessonId, 404);
    }

    public function downloadResource(int $resourceId)
    {
        $resource = LessonResource::whereKey($resourceId)->whereIn('lesson_id', $this->wardLessons()->pluck('lessons.id'))->firstOrFail();
        abort_unless($resource->path && Storage::disk($resource->disk)->exists($resource->path), 404);

        return Storage::disk($resource->disk)->download($resource->path, $resource->title);
    }

    public function render()
    {
        $students = $this->parent->students()->where('students.status', 'active')->orderBy('last_name')->get();
        $student = $students->firstWhere('id', (int) $this->studentId);
        $lessons = $student ? $this->wardLessons()->with(['topic.classSubject.subject', 'resources'])->orderBy('sequence')->paginate(15) : Lesson::query()->whereRaw('1 = 0')->paginate(15);
        $completed = $student ? LessonProgress::where('student_id', $student->id)->whereIn('lesson_id', $lessons->pluck('id'))->pluck('completed_at', 'lesson_id') : collect();
        $selectedLesson = $this->selectedLessonId ? $lessons->firstWhere('id', $this->selectedLessonId) : null;

        return view('livewire.lms.lessons.parent.index', compact('students', 'student', 'lessons', 'completed', 'selectedLesson'));
    }

    private function wardLessons()
    {
        $studentId = (int) $this->studentId;
        abort_unless($studentId && $this->parent->students()->where('students.status', 'active')->whereKey($studentId)->exists(), 403);

        return Lesson::where('status', 'published')
            ->whereHas('topic.classSubject.schoolClass.academicYear', fn ($query) => $query->where('school_id', $this->parent->school_id))
            ->whereHas('topic.classSubject.schoolClass.enrollments', fn ($q) => $q->where('student_id', $studentId)->where('status', 'active'));
    }
}
