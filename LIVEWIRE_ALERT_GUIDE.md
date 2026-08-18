# LivewireAlert Integration Guide

> Comprehensive guide for using `jantinnerezo/livewire-alert` (v4) in our LMS CRUD operations

## Installation & Setup ✅

The package is already installed in the project. SweetAlert2 is configured in `resources/js/app.js`:

```javascript
import Swal from 'sweetalert2'
window.Swal = Swal
```

## Basic Usage Patterns

### 1. Simple Toast Notifications (Recommended for CRUD Success)

**Best for**: After successful save, update, delete operations

```php
<?php
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class AcademicYears\Index extends Component
{
    public function save()
    {
        // ... save logic
        
        LivewireAlert::title('Academic Year Created!')
            ->success()
            ->asToast()
            ->show();
    }
}
```

**Toast Positions**:
- `top-start` - Top left
- `top-end` - Top right (default)
- `center` - Center
- `bottom-start` - Bottom left  
- `bottom-end` - Bottom right

### 2. Toasts with Custom Position & Timer

```php
LivewireAlert::title('Record Updated')
    ->text('The changes were applied successfully.')
    ->success()
    ->asToast()
    ->position('bottom-end')
    ->timer(3000)  // Auto-dismiss after 3 seconds
    ->timerProgressBar()
    ->show();
```

### 3. Modal Dialogs for Critical Actions

**Best for**: Confirmations before destructive operations (delete, archive)

```php
public function deleteAcademicYear($id)
{
    LivewireAlert::title('Delete Academic Year?')
        ->text('This action cannot be undone. All associated terms and classes will be affected.')
        ->warning()
        ->withConfirmButton('Yes, Delete')
        ->withCancelButton('Cancel')
        ->onConfirm('confirmDelete', ['id' => $id])
        ->show();
}

#[On('confirmDelete')]
public function confirmDelete($id)
{
    AcademicYear::find($id)->delete();
    
    LivewireAlert::title('Deleted!')
        ->text('Academic year has been removed.')
        ->success()
        ->asToast()
        ->show();
}
```

### 4. Error Handling & Validation Feedback

```php
public function saveStudent()
{
    $validated = $this->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:students',
    ]);
    
    if ($validated) {
        Student::create($validated);
        
        LivewireAlert::title('Success!')
            ->text('Student has been added to the system.')
            ->success()
            ->asToast()
            ->show();
    }
}

// For validation errors:
#[Validate]
public function saveStudent()
{
    try {
        $this->validate();
        // ... save logic
    } catch (ValidationException $e) {
        LivewireAlert::title('Validation Failed')
            ->text('Please check the form for errors.')
            ->error()
            ->asToast()
            ->show();
    }
}
```

### 5. Question/Confirmation with Multiple Actions

```php
public function archiveClass($classId)
{
    LivewireAlert::title('What would you like to do?')
        ->text('Choose an action for this class.')
        ->question()
        ->withConfirmButton('Archive', '#3b82f6')
        ->withCancelButton('Cancel')
        ->withDenyButton('Delete', '#ef4444')
        ->onConfirm('archiveClass', ['id' => $classId])
        ->onDeny('deleteClass', ['id' => $classId])
        ->onDismiss('cancelAction', ['id' => $classId])
        ->show();
}

#[On('archiveClass')]
public function performArchive($id)
{
    SchoolClass::find($id)->update(['archived' => true]);
    LivewireAlert::title('Archived')->success()->asToast()->show();
}

#[On('deleteClass')]
public function performDelete($id)
{
    SchoolClass::find($id)->delete();
    LivewireAlert::title('Deleted')->success()->asToast()->show();
}
```

## Alert Types & Icons

```php
// Success - Green checkmark (✓)
LivewireAlert::title('Saved!')->success()->asToast()->show();

// Error - Red X (✗)
LivewireAlert::title('Failed!')->error()->asToast()->show();

// Warning - Orange exclamation (!)
LivewireAlert::title('Confirm Action')->warning()->asToast()->show();

// Info - Blue information (i)
LivewireAlert::title('Information')->info()->asToast()->show();

// Question - Blue question mark (?)
LivewireAlert::title('Are you sure?')->question()->asToast()->show();
```

## Component Integration Examples

### Example 1: Students CRUD Index

```php
#[Layout('layouts.lms')]
class Students\Index extends Component
{
    public #[Modelable] $students = [];
    
    public function mount()
    {
        $this->students = Student::with(['currentClass', 'stream'])->latest()->get();
    }
    
    public function createStudent()
    {
        $this->dispatch('openStudentModal');
    }
    
    public function deleteStudent($studentId)
    {
        LivewireAlert::title('Remove Student?')
            ->text('This will remove the student from the system.')
            ->warning()
            ->withConfirmButton('Remove')
            ->withCancelButton('Cancel')
            ->onConfirm('confirmDelete', ['id' => $studentId])
            ->show();
    }
    
    #[On('confirmDelete')]
    public function confirmDelete($id)
    {
        Student::destroy($id);
        
        // Refresh the list
        $this->students = Student::with(['currentClass', 'stream'])->latest()->get();
        
        LivewireAlert::title('Student Removed')
            ->success()
            ->asToast()
            ->show();
    }
    
    #[On('studentCreated')]
    public function onStudentCreated()
    {
        // Refresh list after create modal closes
        $this->students = Student::with(['currentClass', 'stream'])->latest()->get();
        
        LivewireAlert::title('Student Added Successfully!')
            ->text('New student has been registered.')
            ->success()
            ->asToast()
            ->show();
    }
    
    public function render()
    {
        return view('livewire.lms.students.index', [
            'students' => $this->students,
        ]);
    }
}
```

### Example 2: Assignments Form Component

```php
#[Layout('layouts.lms')]
class Assignments\Create extends Component
{
    public $title = '';
    public $description = '';
    public $dueDate = '';
    public $classId = '';
    
    public function save()
    {
        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'dueDate' => 'required|date|after:today',
            'classId' => 'required|exists:school_classes,id',
        ]);
        
        try {
            Assignment::create($validated + ['created_by' => auth()->id()]);
            
            // Clear form
            $this->reset(['title', 'description', 'dueDate', 'classId']);
            
            LivewireAlert::title('Assignment Created!')
                ->text('The assignment is now available to students.')
                ->success()
                ->asToast()
                ->show();
            
            // Redirect to list after 2 seconds
            $this->redirect(route('lms.assignments.index'), navigate: true);
            
        } catch (\Exception $e) {
            LivewireAlert::title('Error!')
                ->text('Failed to create assignment: ' . $e->getMessage())
                ->error()
                ->asToast()
                ->show();
        }
    }
    
    public function render()
    {
        return view('livewire.lms.assignments.create');
    }
}
```

### Example 3: Batch Operations

```php
class Attendance\Index extends Component
{
    public $selectedStudents = [];
    public $markAs = 'present';
    
    public function markBatchAttendance()
    {
        if (empty($this->selectedStudents)) {
            LivewireAlert::title('No Students Selected')
                ->text('Please select at least one student.')
                ->info()
                ->asToast()
                ->show();
            return;
        }
        
        LivewireAlert::title('Mark Attendance?')
            ->text("Mark " . count($this->selectedStudents) . " student(s) as " . $this->markAs . "?")
            ->question()
            ->withConfirmButton('Proceed')
            ->withCancelButton('Cancel')
            ->onConfirm('confirmBatchMark')
            ->show();
    }
    
    #[On('confirmBatchMark')]
    public function confirmBatchMark()
    {
        $count = count($this->selectedStudents);
        
        Attendance::whereIn('student_id', $this->selectedStudents)
            ->whereDate('date', today())
            ->update(['status' => $this->markAs]);
        
        $this->selectedStudents = [];
        
        LivewireAlert::title('Attendance Updated')
            ->text("$count student(s) marked as {$this->markAs}.")
            ->success()
            ->asToast()
            ->show();
    }
}
```

## Best Practices

### ✅ Do's

1. **Use Toasts for non-critical feedback**
   - Save operations
   - Status updates
   - Informational messages

2. **Use Modals for confirmations**
   - Before delete
   - Before archive
   - Before bulk operations
   - When reverting changes

3. **Keep messages concise**
   ```php
   ✅ LivewireAlert::title('Saved!')->success()->asToast()->show();
   ❌ LivewireAlert::title('The academic year data has been successfully saved to the database.')->success()->asToast()->show();
   ```

4. **Match icon to action**
   - Success (✓) - After creating/updating/restoring
   - Warning (!) - Before destructive actions
   - Error (✗) - When operations fail
   - Info (i) - Status changes
   - Question (?) - For confirmations

5. **Chain events for workflows**
   ```php
   #[On('itemSaved')]
   public function onItemSaved()
   {
       // Refresh data, show success, dispatch next event
   }
   ```

### ❌ Don'ts

1. **Don't use alerts for every action**
   - Too many alerts → User fatigue
   - Reserved for important operations

2. **Don't mix modal confirmations and toasts for same action**
   ```php
   // ❌ Bad: Shows both modal and toast
   LivewireAlert::title('Delete?')->warning()->show();
   LivewireAlert::title('Deleted!')->success()->asToast()->show();
   
   // ✅ Good: Modal with onConfirm handler
   ```

3. **Don't forget to handle errors**
   ```php
   // ❌ Bad: No error handling
   public function save() { User::create($data); }
   
   // ✅ Good: Try-catch with alert
   try {
       User::create($data);
       LivewireAlert::title('Saved')->success()->asToast()->show();
   } catch (Exception $e) {
       LivewireAlert::title('Error')->error()->asToast()->show();
   }
   ```

4. **Don't ignore validation failures**
   ```php
   // ✅ Always validate before operations
   public function save()
   {
       $this->validate([...]);  // Throws ValidationException automatically
       // ... proceed with save
   }
   ```

## Common Patterns for Our LMS

### Pattern 1: List with Delete

```blade
<!-- resources/views/livewire/lms/students/index.blade.php -->
<x-button 
    variant="danger" 
    size="sm"
    wire:click="deleteStudent({{ $student->id }})"
    wire:loading.attr="disabled"
>
    Delete
</x-button>
```

**Component Logic**: See Example 1 above

### Pattern 2: Form with Submit

```php
public function save()
{
    $this->validate();
    
    $model = Student::find($this->studentId) ?? new Student();
    $model->fill($this->validated());
    $model->save();
    
    LivewireAlert::title($this->studentId ? 'Updated!' : 'Created!')
        ->success()
        ->asToast()
        ->show();
    
    $this->dispatch('close-modal');
}
```

### Pattern 3: Bulk Actions

```php
public function exportSelected()
{
    if (empty($this->selected)) {
        LivewireAlert::title('No items selected')
            ->info()
            ->asToast()
            ->show();
        return;
    }
    
    // Export logic...
    
    LivewireAlert::title('Export started')
        ->text("Exporting " . count($this->selected) . " records...")
        ->success()
        ->asToast()
        ->show();
}
```

## Styling & Customization

The alerts use Tailwind CSS classes. Customize appearance in the published config:

```bash
php artisan vendor:publish --tag=livewire-alert:config
```

This creates `config/livewire-alert.php` where you can modify:
- Default position
- Timer duration
- Button styles
- Animation behavior

## Debugging

View package source for all available methods:
- `https://github.com/jantinnerezo/livewire-alert`
- Package documentation: `https://livewire-alert.jantinnerezo.me/`

To see the JavaScript events in browser console:
```javascript
// In browser DevTools Console
Swal.fire({title: 'Test'})
```

## Integration Checklist

- [ ] All CRUD operations show success alerts
- [ ] Destructive actions (delete, archive) require confirmation
- [ ] Error messages are user-friendly
- [ ] Validation failures show inline feedback
- [ ] Bulk operations show operation count
- [ ] Form submissions redirect after alert
- [ ] All components use latest Livewire 4.x syntax with `#[Layout]`
- [ ] Toast position is consistent across the app (default: top-end)
- [ ] Modal confirmations use meaningful text

---

**Next Steps**: Implement these patterns in module CRUD operations as you build each feature.
