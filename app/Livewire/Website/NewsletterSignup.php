<?php

namespace App\Livewire\Website;

use App\Livewire\Traits\HasNewsletterSubscription;
use Livewire\Component;

class NewsletterSignup extends Component
{
    use HasNewsletterSubscription;

    public bool $compact = false;

    public function render()
    {
        return view('livewire.website.newsletter-signup', [
            'compact' => $this->compact,
        ]);
    }
}
