@php
    $phoneHref = 'tel:' . preg_replace('/[^+0-9]/', '', $branding['phone']);
    $hasMap = is_numeric($branding['map_latitude']) && is_numeric($branding['map_longitude']);
    $mapLink = $hasMap ? 'https://www.openstreetmap.org/?mlat=' . urlencode($branding['map_latitude']) . '&mlon=' . urlencode($branding['map_longitude']) . '#map=16/' . urlencode($branding['map_latitude']) . '/' . urlencode($branding['map_longitude']) : null;
@endphp

<div class="bg-white">
    <x-website.hero
        eyebrow="We would love to hear from you"
        :title="$page?->hero_title ?: 'Contact ' . $branding['name']"
        :description="$page?->hero_subtitle ?: 'Our admissions and school office teams are ready to help with your questions.'"
        :image="$page?->hero_image_path ? Storage::disk('public')->url($page->hero_image_path) : null"
        :image-alt="$page?->hero_title ?: $branding['name']"
    />

    <section class="py-16 sm:py-20 lg:py-24">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[0.85fr_1.15fr] lg:gap-14 lg:px-8">
            <div>
                <x-website.section-heading eyebrow="Contact details" title="Choose the easiest way to reach us" description="Ask about admissions, arrange a visit, or let the school office direct your enquiry to the right person." />

                <dl class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                        <dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Call the school</dt>
                        <dd class="mt-2"><a href="{{ $phoneHref }}" class="font-bold text-slate-950 hover:text-[var(--brand-primary)]">{{ $branding['phone'] }}</a></dd>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                        <dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Send an email</dt>
                        <dd class="mt-2"><a href="mailto:{{ $branding['email'] }}" class="break-all font-bold text-slate-950 hover:text-[var(--brand-primary)]">{{ $branding['email'] }}</a></dd>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6 sm:col-span-2 lg:col-span-1">
                        <dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Visit us</dt>
                        <dd class="mt-2 font-bold leading-7 text-slate-950">{{ $branding['address'] }}</dd>
                        @if ($mapLink)<a href="{{ $mapLink }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex text-sm font-bold text-[var(--brand-primary)] hover:underline">Open location on map <span class="ml-1" aria-hidden="true">↗</span></a>@endif
                    </div>
                </dl>

                <x-website.social-links :socials="$branding['socials']" class="mt-6" />
            </div>

            <form wire:submit="submit" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8" novalidate>
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-[var(--brand-primary)]">Send an enquiry</p>
                <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">How can we help?</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600">Complete the form and our team will respond as soon as possible during school office hours.</p>

                <div class="mt-7 grid gap-5">
                    <div>
                        <label for="contact-name" class="block text-sm font-bold text-slate-800">Your name</label>
                        <input id="contact-name" wire:model.blur="name" autocomplete="name" class="website-field mt-2" placeholder="Enter your full name" @error('name') aria-invalid="true" aria-describedby="contact-name-error" @enderror>
                        @error('name')<p id="contact-name-error" class="mt-2 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="contact-email" class="block text-sm font-bold text-slate-800">Email address</label>
                        <input id="contact-email" wire:model.blur="email" type="email" inputmode="email" autocomplete="email" class="website-field mt-2" placeholder="you@example.com" @error('email') aria-invalid="true" aria-describedby="contact-email-error" @enderror>
                        @error('email')<p id="contact-email-error" class="mt-2 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="contact-message" class="block text-sm font-bold text-slate-800">Your message</label>
                        <textarea id="contact-message" wire:model.blur="message" rows="6" class="website-field mt-2 resize-y" placeholder="Tell us what you would like to know" @error('message') aria-invalid="true" aria-describedby="contact-message-error" @enderror></textarea>
                        @error('message')<p id="contact-message-error" class="mt-2 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
                    </div>

                    <div class="absolute -left-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
                        <label for="contact-website">Website</label>
                        <input id="contact-website" wire:model="website" type="text" tabindex="-1" autocomplete="off">
                    </div>

                    @if (($retryAfterSeconds ?? 0) > 0)
                        <p role="alert" class="rounded-2xl bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900">Please wait about {{ ceil($retryAfterSeconds / 60) }} minute(s) before sending another enquiry.</p>
                    @endif

                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="max-w-sm text-xs leading-5 text-slate-500">By sending this form, you agree that the school may use these details to respond to your enquiry.</p>
                        <button type="submit" wire:loading.attr="disabled" wire:target="submit" class="website-button website-button-primary min-w-40 disabled:cursor-not-allowed disabled:opacity-60">
                            <span wire:loading.remove wire:target="submit">Send enquiry</span>
                            <span wire:loading.flex wire:target="submit" class="items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"/></svg>
                                Sending
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>
