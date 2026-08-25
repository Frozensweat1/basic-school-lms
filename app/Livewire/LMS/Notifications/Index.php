<?php

namespace App\Livewire\LMS\Notifications;

use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterState = '';

    public string $filterKind = '';

    public bool $showDeleteModal = false;

    public bool $showClearModal = false;

    public ?string $deletingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterState(): void
    {
        $this->resetPage();
    }

    public function updatedFilterKind(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterState', 'filterKind']);
        $this->resetPage();
    }

    public function markRead(string $notificationId): void
    {
        $notification = $this->notificationQuery()->findOrFail($notificationId);
        if (! $notification->read_at) {
            $notification->markAsRead();
        }
    }

    public function markUnread(string $notificationId): void
    {
        $this->notificationQuery()->findOrFail($notificationId)->markAsUnread();
    }

    public function markAllRead(): void
    {
        try {
            $count = auth()->user()->unreadNotifications()->count();
            auth()->user()->unreadNotifications()->update(['read_at' => now()]);
            LivewireAlert::title('Notifications marked as read')
                ->text($count.' '.str('notification')->plural($count).' updated.')
                ->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to update notifications')->error()->asToast()->position('top-end')->show();
        }
    }

    public function openNotification(string $notificationId)
    {
        $notification = $this->notificationQuery()->findOrFail($notificationId);
        $notification->markAsRead();
        $url = (string) ($notification->data['url'] ?? '');
        if ($url === '' || ! $this->isSafeApplicationUrl($url)) {
            return null;
        }

        return $this->redirect($url, navigate: true);
    }

    public function confirmDelete(string $notificationId): void
    {
        $this->notificationQuery()->findOrFail($notificationId);
        $this->deletingId = $notificationId;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        try {
            $this->notificationQuery()->findOrFail($this->deletingId)->delete();
            $this->closeModals();
            LivewireAlert::title('Notification removed')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to remove notification')->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmClearRead(): void
    {
        $this->showClearModal = true;
    }

    public function clearRead(): void
    {
        try {
            $count = $this->notificationQuery()->whereNotNull('read_at')->count();
            $this->notificationQuery()->whereNotNull('read_at')->delete();
            $this->closeModals();
            $this->resetPage();
            LivewireAlert::title('Read notifications cleared')
                ->text($count.' '.str('notification')->plural($count).' removed.')
                ->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to clear notifications')->error()->asToast()->position('top-end')->show();
        }
    }

    public function closeModals(): void
    {
        $this->showDeleteModal = false;
        $this->showClearModal = false;
        $this->deletingId = null;
    }

    public function render()
    {
        $filtered = $this->notificationQuery()
            ->when(filled($this->search), function ($query): void {
                $term = '%'.trim($this->search).'%';
                $query->where('data', 'like', $term);
            })
            ->when($this->filterState === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($this->filterState === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->when(filled($this->filterKind), fn ($query) => $query->where('data->kind', $this->filterKind));
        $base = $this->notificationQuery();

        return view('livewire.lms.notifications.index', [
            'notifications' => (clone $filtered)->latest()->paginate(20),
            'totalCount' => (clone $base)->count(),
            'unreadCount' => (clone $base)->whereNull('read_at')->count(),
            'readCount' => (clone $base)->whereNotNull('read_at')->count(),
            'todayCount' => (clone $base)->whereDate('created_at', today())->count(),
            'kinds' => ['announcement', 'assignment', 'attendance', 'quiz', 'result', 'report', 'info'],
        ]);
    }

    private function notificationQuery()
    {
        return auth()->user()->notifications()->getQuery();
    }

    private function isSafeApplicationUrl(string $url): bool
    {
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        $target = parse_url($url);
        $application = parse_url(url('/'));

        return is_array($target)
            && is_array($application)
            && strtolower((string) ($target['scheme'] ?? '')) === strtolower((string) ($application['scheme'] ?? ''))
            && strtolower((string) ($target['host'] ?? '')) === strtolower((string) ($application['host'] ?? ''))
            && (int) ($target['port'] ?? 0) === (int) ($application['port'] ?? 0);
    }
}
