<?php

namespace App\Livewire\Website;

use App\Livewire\Traits\HasNewsletterSubscription;
use App\Models\WebsiteInquiry;
use App\Support\PublicWebsiteData;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class Contact extends Component
{
    use HasNewsletterSubscription;
    public string $name = '';

    public string $email = '';

    public string $message = '';

    public string $website = '';

    public int $retryAfterSeconds = 0;

    public function submit(): void
    {
        try {
            $data = $this->validate([
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:255'],
                'message' => ['required', 'string', 'max:5000'],
                'website' => ['nullable', 'max:0'],
            ]);
            $key = 'website-contact:'.hash('sha256', request()->ip().'|'.Str::lower($data['email']));

            if (RateLimiter::tooManyAttempts($key, 5)) {
                $this->retryAfterSeconds = RateLimiter::availableIn($key);
                $minutes = max(1, (int) ceil($this->retryAfterSeconds / 60));
                $this->addError('message', "You have sent several messages. Please try again in {$minutes} minute".($minutes === 1 ? '' : 's').'.');
                LivewireAlert::title('Please wait before sending again')
                    ->text("Try again in about {$minutes} minute".($minutes === 1 ? '' : 's').'.')
                    ->warning()
                    ->asToast()
                    ->position('top-end')
                    ->show();

                return;
            }

            WebsiteInquiry::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'message' => $data['message'],
            ]);
            RateLimiter::hit($key, 3600);
            $this->reset(['name', 'email', 'message', 'website', 'retryAfterSeconds']);
            $this->resetValidation();
            LivewireAlert::title('Message sent')
                ->text('Thank you. Our school team will respond as soon as possible.')
                ->success()
                ->asToast()
                ->position('top-end')
                ->show();
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to send message')
                ->text('Please try again, or contact the school by phone or email.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();
        }
    }

    public function render()
    {
        $site = app(PublicWebsiteData::class);
        $page = $site->page('contact');

        return view('livewire.website.contact', [
            'branding' => $site->branding(),
            'page' => $page,
        ])->layout('layouts.website', $site->metadata('Contact', $page, route('website.contact')));
    }
}
