<?php

namespace App\Livewire\Website;

use App\Models\WebsiteEvent;
use App\Support\PublicWebsiteData;
use Livewire\Component;

class EventShow extends Component
{
    public int $eventId;

    public function mount(string $slug): void
    {
        $this->eventId = WebsiteEvent::query()
            ->published()
            ->where('slug', $slug)
            ->value('id') ?? abort(404);
    }

    public function render()
    {
        $site = app(PublicWebsiteData::class);
        $page = $site->page('events');
        $event = WebsiteEvent::query()
            ->published()
            ->findOrFail($this->eventId);
        $canonicalUrl = route('website.events.show', $event->slug);
        $metadata = $site->metadata(
            $event->title,
            $page,
            $canonicalUrl,
            $event->description,
            $event->featured_image_path,
        );
        $metadata['structuredData'] = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $event->title,
            'description' => $metadata['description'],
            'startDate' => $event->starts_at?->toAtomString(),
            'endDate' => $event->ends_at?->toAtomString(),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'url' => $canonicalUrl,
            'image' => $metadata['image'] ? [$metadata['image']] : null,
            'location' => $event->location ? [
                '@type' => 'Place',
                'name' => $event->location,
            ] : null,
            'organizer' => [
                '@type' => 'EducationalOrganization',
                'name' => $site->branding()['name'],
                'url' => route('home'),
            ],
        ]);

        return view('livewire.website.event-show', [
            'branding' => $site->branding(),
            'page' => $page,
            'event' => $event,
        ])->layout('layouts.website', $metadata);
    }
}
