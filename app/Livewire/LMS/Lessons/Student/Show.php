<?php

namespace App\Livewire\LMS\Lessons\Student;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonResource;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class Show extends Component
{
    use AuthorizesRequests;

    public Student $student;

    public Lesson $lesson;

    public ?Lesson $nextLesson = null;

    public bool $justCompleted = false;

    public function mount(Lesson $lesson): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User && $user->hasRole('student'), 403);

        $student = $user->student;
        abort_unless($student instanceof Student, 403);

        $this->student = $student;
        $this->lesson = Lesson::query()
            ->publishedForStudent($student)
            ->with([
                'topic.classSubject.subject',
                'topic.classSubject.schoolClass.stream',
                'teacher',
                'resources',
            ])
            ->findOrFail($lesson->id);

        $this->authorize('view', $this->lesson);
        $this->completeLesson();
        $this->nextLesson = $this->findNextLesson();
    }

    public function downloadResource(int $resourceId)
    {
        $lesson = Lesson::query()
            ->publishedForStudent($this->student)
            ->findOrFail($this->lesson->id);
        $this->authorize('view', $lesson);

        $resource = LessonResource::query()
            ->whereKey($resourceId)
            ->where('lesson_id', $lesson->id)
            ->firstOrFail();

        abort_unless(
            filled($resource->path) && Storage::disk($resource->disk)->exists($resource->path),
            404,
        );

        return Storage::disk($resource->disk)->download($resource->path, $resource->title);
    }

    public function render()
    {
        return view('livewire.lms.lessons.student.show');
    }

    private function completeLesson(): void
    {
        $progress = LessonProgress::query()->firstOrCreate(
            [
                'lesson_id' => $this->lesson->id,
                'student_id' => $this->student->id,
            ],
            ['completed_at' => now()],
        );

        if ($progress->completed_at) {
            $this->justCompleted = $progress->wasRecentlyCreated;

            return;
        }

        $progress->completed_at = now();
        $progress->save();
        $this->justCompleted = true;
    }

    private function findNextLesson(): ?Lesson
    {
        $topic = $this->lesson->topic;

        return Lesson::query()
            ->select('lessons.*')
            ->join('topics', 'topics.id', '=', 'lessons.topic_id')
            ->publishedForStudent($this->student)
            ->where('topics.class_subject_id', $topic->class_subject_id)
            ->where(function (Builder $after) use ($topic): void {
                $after->where('topics.sequence', '>', $topic->sequence)
                    ->orWhere(function (Builder $sameTopicSequence) use ($topic): void {
                        $sameTopicSequence
                            ->where('topics.sequence', $topic->sequence)
                            ->where('topics.id', '>', $topic->id);
                    })
                    ->orWhere(function (Builder $sameTopic) use ($topic): void {
                        $sameTopic
                            ->where('topics.id', $topic->id)
                            ->where('lessons.sequence', '>', $this->lesson->sequence);
                    })
                    ->orWhere(function (Builder $samePosition) use ($topic): void {
                        $samePosition
                            ->where('topics.id', $topic->id)
                            ->where('lessons.sequence', $this->lesson->sequence)
                            ->where('lessons.id', '>', $this->lesson->id);
                    });
            })
            ->with('topic.classSubject.subject')
            ->orderBy('topics.sequence')
            ->orderBy('topics.id')
            ->orderBy('lessons.sequence')
            ->orderBy('lessons.id')
            ->first();
    }
}
