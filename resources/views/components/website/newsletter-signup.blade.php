@props(['eyebrow' => 'Stay connected', 'title' => 'Get the latest news and events', 'description' => 'Subscribe to our newsletter to stay updated with school news, events, and announcements.'])

<section {{ $attributes->class('relative overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 py-16 sm:py-20 lg:py-24') }}>
    <div aria-hidden="true" class="absolute -right-32 -top-32 h-80 w-80 rounded-full opacity-10 blur-3xl" style="background: var(--brand-accent)"></div>
    <div aria-hidden="true" class="absolute -bottom-40 -left-32 h-96 w-96 rounded-full opacity-10 blur-3xl" style="background: var(--brand-primary)"></div>

    <div class="relative mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
        @if ($eyebrow)
            <p class="inline-flex items-center rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.18em] text-white ring-1 ring-white/20">{{ $eyebrow }}</p>
        @endif

        <h2 class="mt-6 text-3xl font-black tracking-tight text-white sm:text-4xl lg:text-5xl">{{ $title }}</h2>

        @if ($description)
            <p class="mx-auto mt-4 max-w-2xl text-lg leading-8 text-white/75">{{ $description }}</p>
        @endif

        <form wire:submit="subscribeNewsletter" class="mt-8 flex flex-col gap-3 sm:flex-row sm:gap-0" novalidate>
            <input
                type="email"
                wire:model.blur="newsletterEmail"
                placeholder="Enter your email address"
                class="flex-1 rounded-full border-0 bg-white px-6 py-3.5 text-slate-950 placeholder-slate-500 ring-1 ring-inset ring-white/20 focus:ring-2 focus:ring-[var(--brand-accent)]"
                aria-label="Email address for newsletter"
                @error('newsletterEmail') aria-invalid="true" @enderror
                required
            />
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="subscribeNewsletter"
                class="rounded-full bg-[var(--brand-primary)] px-8 py-3.5 font-bold text-white shadow-lg transition hover:shadow-xl hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-60 sm:ml-2"
            >
                <span wire:loading.remove wire:target="subscribeNewsletter">Subscribe</span>
                <span wire:loading.flex wire:target="subscribeNewsletter" class="items-center justify-center gap-2">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"/></svg>
                </span>
            </button>
        </form>

        @error('newsletterEmail')
            <p role="alert" class="mt-3 text-sm font-medium text-rose-300">{{ $message }}</p>
        @enderror

        <p class="mt-4 text-xs text-white/60">We'll never share your email or spam you. Unsubscribe anytime.</p>
    </div>
</section>
