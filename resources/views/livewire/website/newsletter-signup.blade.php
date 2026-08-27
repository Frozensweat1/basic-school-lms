@php
    $title = $compact ? 'Get updates' : 'Get school news and updates';
    $description = $compact ? null : 'Receive key updates, event news, and community highlights from ' . (app(\App\Support\SchoolBranding::class)->data()['name'] ?? 'our school') . '.';
@endphp

<section class="relative overflow-hidden {{ $compact ? '' : 'bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 py-10 sm:py-14 lg:py-16' }}">
    @if (! $compact)
        <div aria-hidden="true" class="absolute -right-20 -top-20 h-60 w-60 rounded-full opacity-10 blur-3xl" style="background: var(--brand-accent)"></div>
        <div aria-hidden="true" class="absolute -bottom-28 -left-20 h-72 w-72 rounded-full opacity-10 blur-3xl" style="background: var(--brand-primary)"></div>
    @endif

    <div class="relative {{ $compact ? '' : 'mx-auto max-w-4xl px-4 sm:px-6 lg:px-8' }}">
        <div class="{{ $compact ? '' : 'rounded-[2rem] border border-white/10 bg-white/5 p-6 shadow-2xl shadow-slate-950/20 backdrop-blur-sm sm:p-8 lg:p-10' }}">
            <div class="{{ $compact ? 'flex flex-col gap-3 sm:flex-row sm:items-center' : 'flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between' }}">
                @if (! $compact)
                    <div class="max-w-xl">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-white/75">Stay connected</p>
                        <h2 class="mt-3 text-2xl font-black tracking-tight text-white sm:text-3xl">{{ $title }}</h2>
                        @if ($description)
                            <p class="mt-3 text-sm leading-6 text-slate-300 sm:text-base">{{ $description }}</p>
                        @endif
                    </div>
                @endif

                <form wire:submit="subscribeNewsletter" class="w-full {{ $compact ? 'max-w-md' : 'max-w-xl' }}" novalidate>
                    <div class="flex flex-col gap-3 {{ $compact ? 'sm:flex-row' : 'sm:flex-row' }}">
                        <label class="sr-only" for="newsletter-signup-email">Email address</label>
                        <input
                            id="newsletter-signup-email"
                            type="email"
                            wire:model.blur="newsletterEmail"
                            placeholder="Your email address"
                            class="min-w-0 flex-1 rounded-full border {{ $compact ? 'border-white/15 bg-white/10 text-white placeholder-slate-300' : 'border-white/10 bg-white px-5 text-slate-900 placeholder-slate-500 shadow-inner' }} py-3.5 text-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-[var(--brand-accent)]"
                            aria-label="Email address"
                            required
                        />
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="subscribeNewsletter"
                            class="inline-flex min-h-12 items-center justify-center rounded-full {{ $compact ? 'bg-[var(--brand-primary)] px-5 text-white' : 'bg-[var(--brand-primary)] px-6 text-white shadow-lg' }} text-sm font-bold transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="subscribeNewsletter">Subscribe</span>
                            <span wire:loading.flex wire:target="subscribeNewsletter" class="items-center justify-center gap-2">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"/></svg>
                            </span>
                        </button>
                    </div>

                    @error('newsletterEmail')
                        <p role="alert" class="mt-3 text-sm font-medium {{ $compact ? 'text-rose-200' : 'text-rose-200' }}">{{ $message }}</p>
                    @enderror
                    @if (! $compact)
                        <p class="mt-3 text-xs text-slate-300">No spam. Unsubscribe whenever you want.</p>
                    @endif
                </form>
            </div>
        </div>
    </div>
</section>
