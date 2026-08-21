@php($branding = app(\App\Support\SchoolBranding::class)->data())
<div class="bg-white">
    <x-website.hero :eyebrow="$branding['name'] . ' stories'" title="News from our school community"
        description="Highlights, ideas, and updates from learning in our school community." />
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
            @forelse($posts as $post)
                <article wire:key="news-{{ $post->id }}"
                    class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                    @if ($post->featured_image_path)
                        <img src="{{ Storage::disk('public')->url($post->featured_image_path) }}"
                            alt="{{ $post->title }}" class="aspect-[16/9] w-full object-cover">
                    @else
                        <div
                            class="flex aspect-[16/9] items-end bg-gradient-to-br from-blue-900 via-blue-700 to-sky-400 p-6">
                            <span
                                class="text-sm font-semibold uppercase tracking-widest text-white/80">{{ $branding['name'] }}</span>
                        </div>
                    @endif
                    <div class="flex flex-1 flex-col p-6">
                        <p class="text-xs font-semibold uppercase tracking-widest text-blue-700">
                            {{ $post->published_at?->format('d F Y') }}</p>
                        <h2 class="mt-3 text-xl font-bold text-slate-900">{{ $post->title }}</h2>
                        <p class="mt-3 flex-1 text-sm leading-6 text-slate-600">
                            {{ $post->excerpt ?: str($post->body)->stripTags()->limit(180) }}</p>
                        <button type="button" wire:click="open({{ $post->id }})"
                            class="mt-5 inline-flex w-fit font-semibold text-blue-800 hover:text-blue-600">Read story
                            <span class="ml-2" aria-hidden="true">→</span></button>
                    </div>
                </article>
            @empty
                <div
                    class="col-span-full rounded-2xl border border-dashed border-slate-300 p-12 text-center text-slate-600">
                    School news will be published here soon.</div>
            @endforelse
        </div>
        <div class="mt-10">{{ $posts->links() }}</div>
    </section>
    @if ($selected)
        <x-modal :show="true" maxWidth="2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-blue-700">
                        {{ $selected->published_at?->format('d F Y') }}</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ $selected->title }}</h2>
                </div><x-ui.icon-button wire:click="close" icon="close" label="Close story" target="close" />
            </div>
            <div class="prose prose-slate mt-6 max-w-none">{!! $selected->body !!}</div>
        </x-modal>
    @endif
</div>
