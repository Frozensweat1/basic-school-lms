<?php

namespace App\Livewire\Website;

use App\Models\WebsiteInquiry;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.website')]
class Contact extends Component
{
    public string $name = '', $email = '', $message = '';

    public function submit(): void
    {
        $key = 'website-contact:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) abort(429, 'Too many enquiries. Please try again later.');
        RateLimiter::hit($key, 3600);
        try {
            $data = $this->validate(['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:255'], 'message' => ['required', 'string', 'max:5000']]);
            WebsiteInquiry::create($data);
            $this->reset(['name', 'email', 'message']);
            LivewireAlert::title('Message sent')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to send message')->error()->asToast()->position('top-end')->show();
        }
    }
    public function render()
    {
        return view('livewire.website.contact');
    }
}
