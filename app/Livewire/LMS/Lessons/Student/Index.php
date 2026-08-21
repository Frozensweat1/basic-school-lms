<?php

namespace App\Livewire\LMS\Lessons\Student;

use App\Models\{Lesson, LessonProgress, LessonResource, Student};
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class Index extends Component
{
    public Student $student;
    public ?int $selectedLessonId = null;

    public function mount(): void
    {
        $this->student = auth()->user()->student;
        abort_unless(auth()->user()->hasRole('student') && $this->student, 403);
    }

    public function viewLesson(int $lessonId): void
    {
        $this->selectedLessonId = $this->lessons()->whereKey($lessonId)->value('id');
        abort_unless($this->selectedLessonId, 404);
    }

    public function toggleCompleted(int $lessonId): void
    {
        $lesson = $this->lessons()->findOrFail($lessonId);
        $progress = LessonProgress::where('lesson_id', $lesson->id)->where('student_id', $this->student->id)->first();
        $progress ? $progress->delete() : LessonProgress::create(['lesson_id' => $lesson->id, 'student_id' => $this->student->id, 'completed_at' => now()]);
    }

    public function downloadResource(int $resourceId)
    {
        $resource = LessonResource::whereKey($resourceId)->whereIn('lesson_id', $this->lessons()->pluck('lessons.id'))->firstOrFail();
        abort_unless($resource->path && Storage::disk($resource->disk)->exists($resource->path), 404);

        return Storage::disk($resource->disk)->download($resource->path, $resource->title);
    }

    public function render()
    {
        $lessons = $this->lessons()->with(['topic.classSubject.schoolClass', 'topic.classSubject.subject', 'teacher', 'resources'])->orderBy('sequence')->get();
        $completed = LessonProgress::where('student_id', $this->student->id)->whereIn('lesson_id', $lessons->pluck('id'))->pluck('completed_at', 'lesson_id');
        $selectedLesson = $this->selectedLessonId ? $lessons->firstWhere('id', $this->selectedLessonId) : null;

        return view('livewire.lms.lessons.student.index', compact('lessons', 'completed', 'selectedLesson'));
    }

    private function lessons()
    {
        return Lesson::query()
            ->where('status', 'published')
            ->whereHas('topic.classSubject.schoolClass.academicYear', fn ($query) => $query->where('school_id', $this->student->school_id))
            ->whereHas('topic.classSubject.schoolClass.enrollments', fn ($q) => $q->where('student_id', $this->student->id)->where('status', 'active'));
    }
}
