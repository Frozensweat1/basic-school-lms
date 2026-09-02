<?php

namespace App\Livewire\LMS\Parents;

use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\UserProfileService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $search = '';

    public string $firstName = '';

    public string $lastName = '';

    public string $phone = '';

    public string $email = '';

    public string $address = '';

    public string $gpsAddress = '';

    public string $city = '';

    public string $workplace = '';

    public string $ghanaCardNumber = '';

    public string $relationship = 'Guardian';

    /** @var array<int, string> */
    public array $studentIds = [];

    public function mount(): void
    {
        $this->authorize('viewAny', ParentGuardian::class);
    }

    public function create(): void
    {
        $this->authorize('create', ParentGuardian::class);
        $this->ensureSchoolConfigured();

        $this->resetForm();
        $this->showFormModal = true;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function edit(ParentGuardian $parent): void
    {
        $this->ensureSchoolRecord($parent);
        $this->authorize('update', $parent);

        $this->editingId = $parent->id;
        $this->firstName = $parent->first_name;
        $this->lastName = $parent->last_name;
        $this->phone = $parent->phone ?? '';
        $this->email = $parent->user?->email ?? $parent->email ?? '';
        $this->address = $parent->address ?? '';
        $this->gpsAddress = $parent->gps_address ?? '';
        $this->city = $parent->city ?? '';
        $this->workplace = $parent->workplace ?? '';
        $this->ghanaCardNumber = $parent->ghana_card_number ?? '';
        $this->relationship = $parent->students()->first()?->pivot->relationship ?? 'Guardian';
        $this->studentIds = $parent->students()->pluck('students.id')->map(fn ($id) => (string) $id)->all();
        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $parent = $this->editingId ? ParentGuardian::findOrFail($this->editingId) : null;

        if ($parent) {
            $this->ensureSchoolRecord($parent);
        }

        $this->authorize($parent ? 'update' : 'create', $parent ?? ParentGuardian::class);
        $schoolId = $this->ensureSchoolConfigured();

        try {
            $matchingAccountId = $parent?->user_id
                ?? User::query()->whereRaw('LOWER(email) = ?', [strtolower(trim($this->email))])->value('id');
            $data = $this->validate([
                'firstName' => ['required', 'string', 'max:100'],
                'lastName' => ['required', 'string', 'max:100'],
                'phone' => [
                    'required',
                    'string',
                    'max:30',
                    function (string $attribute, mixed $value, \Closure $fail): void {
                        if (strlen($this->normalizePhone((string) $value)) < 8) {
                            $fail('Enter a valid phone number with at least 8 digits.');
                        }
                    },
                ],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($matchingAccountId)],
                'address' => ['nullable', 'string', 'max:1000'],
                'gpsAddress' => ['nullable', 'string', 'max:100'],
                'city' => ['nullable', 'string', 'max:100'],
                'workplace' => ['nullable', 'string', 'max:255'],
                'ghanaCardNumber' => ['nullable', 'string', 'max:50'],
                'relationship' => ['required', 'string', 'max:50'],
                'studentIds' => ['array'],
                'studentIds.*' => [Rule::exists('students', 'id')->where('school_id', $schoolId)],
            ]);

            DB::transaction(function () use ($parent, $schoolId, $data): void {
                $record = ParentGuardian::updateOrCreate(
                    ['id' => $parent?->id],
                    [
                        'school_id' => $schoolId,
                        'first_name' => $data['firstName'],
                        'last_name' => $data['lastName'],
                        'phone' => filled($data['phone']) ? $data['phone'] : null,
                        'email' => strtolower(trim($data['email'])),
                        'address' => filled($data['address']) ? $data['address'] : null,
                        'gps_address' => filled($data['gpsAddress']) ? trim($data['gpsAddress']) : null,
                        'city' => filled($data['city']) ? trim($data['city']) : null,
                        'workplace' => filled($data['workplace']) ? trim($data['workplace']) : null,
                        'ghana_card_number' => filled($data['ghanaCardNumber']) ? trim($data['ghanaCardNumber']) : null,
                    ],
                );

                app(UserProfileService::class)->synchronizeAccount(
                    $record,
                    'parent',
                    trim($data['firstName'].' '.$data['lastName']),
                    $data['email'],
                    $parent?->user_id ? null : $this->normalizePhone($data['phone']),
                );

                $record->students()->sync(
                    collect($data['studentIds'])
                        ->mapWithKeys(fn ($id) => [$id => ['relationship' => $data['relationship'], 'is_primary_contact' => false]])
                        ->all(),
                );
            });

            $this->showFormModal = false;
            $this->resetForm();
            $this->resetPage();
            LivewireAlert::title($parent ? 'Parent updated' : 'Parent added')
                ->success()
                ->asToast()
                ->position('top-end')
                ->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the form')
                ->text('Correct the highlighted fields and try again.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save parent')
                ->text('Please try again.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();
        }
    }

    public function confirmDelete(ParentGuardian $parent): void
    {
        $this->ensureSchoolRecord($parent);
        $this->authorize('delete', $parent);

        $this->deletingId = $parent->id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $parent = ParentGuardian::findOrFail($this->deletingId);
        $this->ensureSchoolRecord($parent);
        $this->authorize('delete', $parent);

        try {
            $parent->delete();
            $this->showDeleteModal = false;
            $this->deletingId = null;
            $this->resetPage();
            LivewireAlert::title('Parent archived')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to archive parent')
                ->text('Please try again.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();
        }
    }

    public function closeModals(): void
    {
        $this->showFormModal = false;
        $this->showDeleteModal = false;
        $this->resetForm();
        $this->resetErrorBag();
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId',
            'deletingId',
            'firstName',
            'lastName',
            'phone',
            'email',
            'address',
            'gpsAddress',
            'city',
            'workplace',
            'ghanaCardNumber',
            'relationship',
            'studentIds',
        ]);
        $this->relationship = 'Guardian';
        $this->resetValidation();
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', trim($phone)) ?? '';
    }

    private function schoolId(): int
    {
        return (int) School::query()->value('id');
    }

    private function ensureSchoolConfigured(): int
    {
        $schoolId = $this->schoolId();
        abort_unless($schoolId, 422, 'Configure a school before managing parents and guardians.');

        return $schoolId;
    }

    private function ensureSchoolRecord(ParentGuardian $parent): void
    {
        abort_unless($parent->school_id === $this->schoolId(), 404);
    }

    public function render()
    {
        $schoolId = $this->schoolId();
        $search = trim($this->search);

        return view('livewire.lms.parents.index', [
            'parents' => ParentGuardian::query()
                ->where('school_id', $schoolId)
                ->withCount('students')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($parents) use ($search): void {
                        $parents->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('gps_address', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%")
                            ->orWhere('workplace', 'like', "%{$search}%")
                            ->orWhere('ghana_card_number', 'like', "%{$search}%")
                            ->orWhereHas('students', fn ($students) => $students
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('admission_number', 'like', "%{$search}%"));
                    });
                })
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->paginate(15),
            'students' => Student::query()
                ->where('school_id', $schoolId)
                ->where('status', 'active')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
        ]);
    }
}
