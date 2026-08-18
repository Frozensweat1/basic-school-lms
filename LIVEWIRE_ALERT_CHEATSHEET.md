# LivewireAlert Quick Reference

## Import

```php
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
```

## Quick Patterns

### Success Toast (After Save)
```php
LivewireAlert::title('Saved!')->success()->asToast()->show();
```

### Error Toast (On Failure)
```php
LivewireAlert::title('Error!')->error()->asToast()->show();
```

### Confirm Before Delete
```php
// Show confirmation
LivewireAlert::title('Delete?')
    ->warning()
    ->withConfirmButton('Yes')
    ->withCancelButton('No')
    ->onConfirm('deleteConfirmed', ['id' => $id])
    ->show();

// Handle confirmation
#[On('deleteConfirmed')]
public function deleteConfirmed($id) { /* ... */ }
```

### Toast with Timer (Auto-dismiss)
```php
LivewireAlert::title('Processing...')
    ->info()
    ->asToast()
    ->timer(3000)
    ->timerProgressBar()
    ->show();
```

### With Text Description
```php
LivewireAlert::title('Updated!')
    ->text('All changes have been saved.')
    ->success()
    ->asToast()
    ->show();
```

## All Toast Positions
- `top-start` | `top-end` | `center` | `bottom-start` | `bottom-end`

```php
->asToast()
->position('bottom-end')
```

## All Alert Types
- `.success()` - Green checkmark
- `.error()` - Red X  
- `.warning()` - Orange exclamation
- `.info()` - Blue info
- `.question()` - Blue question mark

## Modal Buttons
- `.withConfirmButton('Text')` - Main action
- `.withCancelButton('Text')` - Cancel action
- `.withDenyButton('Text')` - Secondary action

## Modal Events
- `.onConfirm('eventName', ['data' => 'value'])` - Confirm button clicked
- `.onDeny('eventName', ['data' => 'value'])` - Deny button clicked
- `.onDismiss('eventName', ['data' => 'value'])` - Dialog closed

## Livewire Component Structure

```php
#[Layout('layouts.lms')]  // ← New Livewire 4.x syntax
class MyComponent extends Component
{
    public function myAction()
    {
        LivewireAlert::title('Done!')->success()->asToast()->show();
    }
    
    #[On('myEvent')]  // ← Listen to events
    public function handleEvent($data) { }
    
    public function render()
    {
        return view('livewire.lms.my-component');
    }
}
```

## Common CRUD Patterns

| Operation | Alert Type | Pattern |
|-----------|-----------|---------|
| After Create | Success Toast | `->success()->asToast()->show()` |
| After Update | Success Toast | `->success()->asToast()->show()` |
| Before Delete | Warning Modal | `->warning()->withConfirmButton()->onConfirm()` |
| Error | Error Toast | `->error()->asToast()->show()` |
| Validation Failed | Error Toast | `->error()->asToast()->show()` |
| Bulk Operation | Info Toast | `->info()->asToast()->show()` |
| Confirmation | Question Modal | `->question()->withConfirmButton()->onConfirm()` |

## Validation Error Handling

```php
try {
    $this->validate([...]);
    // Save logic
    LivewireAlert::title('Saved')->success()->asToast()->show();
} catch (ValidationException $e) {
    LivewireAlert::title('Validation Error')
        ->error()
        ->asToast()
        ->show();
    // Livewire automatically displays validation errors in view
}
```

---

**Full Guide**: See [LIVEWIRE_ALERT_GUIDE.md](LIVEWIRE_ALERT_GUIDE.md)
