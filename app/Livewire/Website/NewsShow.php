<?php

namespace App\Livewire\Website;

use App\Models\WebsiteNewsPost;
use App\Support\PublicWebsiteData;
use Livewire\Component;

class NewsShow extends Component
{
    public int $postId;

    public function mount(string $slug): void
    {
        $this->postId = WebsiteNewsPost::query()
            ->published()
            ->where('slug', $slug)
            ->value('id') ?? abort(404);
    }

    public function render()
    {
        $site = app(PublicWebsiteData::class);
        $page = $site->page('news');
        $post = WebsiteNewsPost::query()
            ->published()
            ->with('author:id,name')
            ->findOrFail($this->postId);
        $description = $post->excerpt ?: strip_tags($post->body);
        $canonicalUrl = route('website.news.show', $post->slug);
        $metadata = $site->metadata(
            $post->title,
            $page,
            $canonicalUrl,
            $description,
            $post->featured_image_path,
        );
        $metadata['type'] = 'article';
        $metadata['structuredData'] = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $post->title,
            'description' => $metadata['description'],
            'datePublished' => $post->published_at?->toAtomString(),
            'dateModified' => $post->updated_at?->toAtomString(),
            'mainEntityOfPage' => $canonicalUrl,
            'url' => $canonicalUrl,
            'image' => $metadata['image'] ? [$metadata['image']] : null,
            'author' => $post->author ? [
                '@type' => 'Person',
                'name' => $post->author->name,
            ] : null,
            'publisher' => array_filter([
                '@type' => 'EducationalOrganization',
                'name' => $site->branding()['name'],
                'logo' => $site->branding()['logo_url'] ? [
                    '@type' => 'ImageObject',
                    'url' => $site->branding()['logo_url'],
                ] : null,
            ]),
        ]);

        return view('livewire.website.news-show', [
            'branding' => $site->branding(),
            'page' => $page,
            'post' => $post,
        ])->layout('layouts.website', $metadata);
    }
}
