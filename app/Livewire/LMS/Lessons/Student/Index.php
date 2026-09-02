<?php

namespace App\Livewire\LMS\Lessons\Student;

use App\Models\ClassEnrollment;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.lms')]
class Index extends Component
{
    private const LESSONS_PER_LOAD = 12;

    public Student $student;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'subject', except: '')]
    public string $filterSubjectId = '';

    #[Url(as: 'topic', except: '')]
    public string $filterTopicId = '';

    public int $visibleLessons = self::LESSONS_PER_LOAD;

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User && $user->hasRole('student'), 403);

        $student = $user->student;
        abort_unless($student instanceof Student, 403);

        $this->student = $student;
    }

    public function updatedSearch(): void
    {
        $this->resetLessonFeed();
    }

    public function updatedFilterSubjectId(): void
    {
        if (filled($this->filterTopicId) && ! $this->availableTopicsQuery()
            ->whereKey($this->filterTopicId)
            ->exists()) {
            $this->filterTopicId = '';
        }

        $this->resetLessonFeed();
    }

    public function updatedFilterTopicId(): void
    {
        $this->resetLessonFeed();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterSubjectId', 'filterTopicId']);
        $this->resetLessonFeed();
    }

    public function loadMore(): void
    {
        $total = $this->filteredLessonsQuery()->count();

        if ($total > $this->visibleLessons) {
            $this->visibleLessons = min($total, $this->visibleLessons + self::LESSONS_PER_LOAD);
        }
    }

    public function render()
    {
        $lessonsQuery = $this->filteredLessonsQuery();
        $filteredTotal = (clone $lessonsQuery)->count();
        $lessons = $lessonsQuery
            ->with([
                'topic.classSubject.subject',
                'topic.classSubject.schoolClass.stream',
                'teacher',
                'progress' => fn ($progress) => $progress
                    ->where('student_id', $this->student->id)
                    ->whereNotNull('completed_at'),
            ])
            ->limit($this->visibleLessons)
            ->get();

        $allLessons = Lesson::query()->publishedForStudent($this->student);
        $totalLessons = (clone $allLessons)->count();
        $completedLessons = (clone $allLessons)
            ->whereHas('progress', fn (Builder $progress) => $progress
                ->where('student_id', $this->student->id)
                ->whereNotNull('completed_at'))
            ->count();

        return view('livewire.lms.lessons.student.index', [
            'lessons' => $lessons,
            'subjects' => $this->availableSubjectsQuery()->get(),
            'topics' => $this->availableTopicsQuery()->with('classSubject.subject')->get(),
            'filteredTotal' => $filteredTotal,
            'hasMore' => $lessons->count() < $filteredTotal,
            'totalLessons' => $totalLessons,
            'completedLessons' => $completedLessons,
            'completionPercentage' => $totalLessons > 0
                ? (int) round(($completedLessons / $totalLessons) * 100)
                : 0,
        ]);
    }

    private function resetLessonFeed(): void
    {
        $this->visibleLessons = self::LESSONS_PER_LOAD;
    }

    private function filteredLessonsQuery(): Builder
    {
        $search = trim($this->search);

        return Lesson::query()
            ->select('lessons.*')
            ->join('topics', 'topics.id', '=', 'lessons.topic_id')
            ->join('class_subjects', 'class_subjects.id', '=', 'topics.class_subject_id')
            ->join('subjects', 'subjects.id', '=', 'class_subjects.subject_id')
            ->publishedForStudent($this->student)
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $matches) use ($search): void {
                $matches->where('lessons.title', 'like', "%{$search}%")
                    ->orWhere('lessons.summary', 'like', "%{$search}%")
                    ->orWhere('topics.title', 'like', "%{$search}%")
                    ->orWhere('subjects.name', 'like', "%{$search}%");
            }))
            ->when(filled($this->filterSubjectId), fn (Builder $query) => $query
                ->where('class_subjects.subject_id', $this->filterSubjectId))
            ->when(filled($this->filterTopicId), fn (Builder $query) => $query
                ->where('lessons.topic_id', $this->filterTopicId))
            ->orderBy('subjects.name')
            ->orderBy('class_subjects.id')
            ->orderBy('topics.sequence')
            ->orderBy('topics.id')
            ->orderBy('lessons.sequence')
            ->orderBy('lessons.id');
    }

    private function availableSubjectsQuery(): Builder
    {
        return Subject::query()
            ->where('school_id', $this->student->school_id)
            ->whereHas('classSubjects', fn (Builder $classSubjects) => $classSubjects
                ->whereHas('schoolClass.enrollments', fn (Builder $enrollments) => $enrollments
                    ->where('student_id', $this->student->id)
                    ->where('status', ClassEnrollment::STATUS_ACTIVE))
                ->whereHas('topics.lessons', fn (Builder $lessons) => $lessons
                    ->where('status', 'published')))
            ->orderBy('name');
    }

    private function availableTopicsQuery(): Builder
    {
        return Topic::query()
            ->whereHas('classSubject.schoolClass.academicYear', fn (Builder $years) => $years
                ->where('school_id', $this->student->school_id))
            ->whereHas('classSubject.schoolClass.enrollments', fn (Builder $enrollments) => $enrollments
                ->where('student_id', $this->student->id)
                ->where('status', ClassEnrollment::STATUS_ACTIVE))
            ->whereHas('lessons', fn (Builder $lessons) => $lessons->where('status', 'published'))
            ->when(filled($this->filterSubjectId), fn (Builder $topics) => $topics
                ->whereHas('classSubject', fn (Builder $classSubjects) => $classSubjects
                    ->where('subject_id', $this->filterSubjectId)))
            ->orderBy('sequence')
            ->orderBy('title');
    }
}
