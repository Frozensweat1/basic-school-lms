@php
    $safeBody = app(\App\Support\ContentSanitizer::class)->clean($post->body);
    $shareUrl = route('website.news.show', $post);
@endphp

<article class="bg-white">
    <x-website.hero
        variant="article"
        eyebrow="{{ $post->published_at?->format('d F Y') }}"
        :title="$post->title"
        :description="$post->excerpt"
        :image="$post->featured_image_path ? Storage::disk('public')->url($post->featured_image_path) : null"
        :image-alt="$post->title"
        :secondary-action="route('website.news')"
        secondary-action-label="Back to all news"
        :breadcrumbs="[['label' => 'News', 'url' => route('website.news')], ['label' => $post->title]]"
    />

    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-[minmax(0,1fr)_16rem] lg:px-8">
        <div class="min-w-0">
            <div class="website-prose">{!! $safeBody !!}</div>
        </div>

        <aside class="lg:border-l lg:border-slate-200 lg:pl-8">
            <div class="lg:sticky lg:top-28">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Published</p>
                <time datetime="{{ $post->published_at?->toAtomString() }}" class="mt-2 block font-semibold text-slate-950">{{ $post->published_at?->format('d F Y') }}</time>
                <p class="mt-8 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Share this story</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a class="website-icon-link" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener noreferrer" aria-label="Share on Facebook">Facebook</a>
                    <a class="website-icon-link" href="https://wa.me/?text={{ urlencode($post->title . ' ' . $shareUrl) }}" target="_blank" rel="noopener noreferrer" aria-label="Share on WhatsApp">WhatsApp</a>
                    <a class="website-icon-link" href="mailto:?subject={{ rawurlencode($post->title) }}&body={{ rawurlencode($shareUrl) }}">Email</a>
                </div>
            </div>
        </aside>
    </div>

    <x-website.cta
        eyebrow="From our community"
        title="Explore more school stories and upcoming moments"
        :action="route('website.news')"
        action-label="More news"
        :secondary-action="route('website.events')"
        secondary-action-label="View events"
    />
</article>
