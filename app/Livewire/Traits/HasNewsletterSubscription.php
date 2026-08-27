<?php

namespace App\Livewire\Traits;

use App\Models\NewsletterSubscription;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

trait HasNewsletterSubscription
{
    public string $newsletterEmail = '';

    public function subscribeNewsletter(): void
    {
        try {
            $email = $this->validate(['newsletterEmail' => ['required', 'email', 'max:255']])['newsletterEmail'];

            $subscription = NewsletterSubscription::firstOrCreate(
                ['email' => strtolower($email)],
                ['is_active' => true, 'verified_at' => now()]
            );

            if (! $subscription->wasRecentlyCreated && ! $subscription->is_active) {
                $subscription->update(['is_active' => true, 'unsubscribed_at' => null, 'verified_at' => now()]);
            }

            $this->reset('newsletterEmail');
            $this->resetValidation();

            LivewireAlert::title('Welcome to our newsletter!')
                ->text('Thank you for subscribing. We\'ll send you the latest school news and events.')
                ->success()
                ->asToast()
                ->position('top-end')
                ->show();
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to subscribe')
                ->text('Please try again or contact the school office.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();
        }
    }
}
