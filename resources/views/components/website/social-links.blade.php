@props([
    'socials',
    'inverse' => false,
])

@php
    $availableSocials = collect($socials)->filter();
@endphp

@if ($availableSocials->isNotEmpty())
    <div {{ $attributes->class(['flex flex-wrap items-center gap-2']) }} aria-label="Social media links">
        @foreach ($availableSocials as $network => $url)
            @php
                $href = $network === 'whatsapp'
                    ? 'https://wa.me/'.preg_replace('/\D+/', '', $url)
                    : $url;
                $label = match ($network) {
                    'x' => 'X',
                    'youtube' => 'YouTube',
                    'whatsapp' => 'WhatsApp',
                    default => str($network)->headline(),
                };
            @endphp
            <a href="{{ $href }}" target="_blank" rel="noopener noreferrer"
                @class([
                    'inline-flex h-10 w-10 items-center justify-center rounded-full border transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2',
                    'border-white/15 bg-white/5 text-slate-300 hover:border-white/30 hover:bg-white/10 hover:text-white' => $inverse,
                    'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-950' => ! $inverse,
                ])
                style="outline-color: var(--brand-accent)" aria-label="Visit our school on {{ $label }}">
                @switch($network)
                    @case('facebook')
                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 22v-9h3l.5-3.5h-3.5V7.2c0-1 .3-1.7 1.8-1.7H17V2.4c-.3 0-1.4-.1-2.7-.1-2.7 0-4.6 1.7-4.6 4.8v2.6H7V13h2.7v9h3.8Z" /></svg>
                        @break
                    @case('instagram')
                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5" /><circle cx="12" cy="12" r="4" /><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none" /></svg>
                        @break
                    @case('youtube')
                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31 31 0 0 0 0 12a31 31 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31 31 0 0 0 24 12a31 31 0 0 0-.5-5.8ZM9.6 15.6V8.4l6.3 3.6-6.3 3.6Z" /></svg>
                        @break
                    @case('x')
                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 2H22l-6.8 7.8L23.2 22h-6.3l-5-6.5L6.2 22H3l7.4-8.5L2.7 2H9l4.5 6 5.4-6Zm-1.1 17.8h1.7L8 4H6.2l11.6 15.8Z" /></svg>
                        @break
                    @case('whatsapp')
                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M20.5 3.5A11.8 11.8 0 0 0 12.1 0C5.6 0 .3 5.3.3 11.8c0 2.1.6 4.1 1.6 5.9L.2 24l6.5-1.7c1.7.9 3.5 1.4 5.4 1.4 6.5 0 11.8-5.3 11.8-11.8 0-3.2-1.2-6.1-3.4-8.4Zm-8.4 18.2c-1.7 0-3.4-.5-4.9-1.4l-.4-.2-3.8 1 1-3.7-.2-.4a9.8 9.8 0 1 1 8.3 4.7Zm5.4-7.3c-.3-.2-1.8-.9-2.1-1-.3-.1-.5-.2-.7.2-.2.3-.8 1-.9 1.2-.2.2-.3.2-.7.1-1.8-.9-3-1.6-4.2-3.7-.3-.5.3-.5.9-1.7.1-.2 0-.4 0-.6l-1-2.4c-.3-.6-.6-.5-.8-.5h-.7c-.2 0-.6.1-.9.4-.3.3-1.2 1.2-1.2 2.9s1.2 3.3 1.4 3.5c.2.2 2.4 3.7 5.9 5.2.8.4 1.5.6 2 .7.8.3 1.6.2 2.2.1.7-.1 1.8-.7 2.1-1.4.3-.7.3-1.3.2-1.4-.1-.3-.3-.4-.6-.6Z" /></svg>
                        @break
                    @default
                        <svg aria-hidden="true" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.8 10.2a4 4 0 0 0-5.6 0l-4 4a4 4 0 0 0 5.6 5.6l2.2-2.2m-1.8-3.8a4 4 0 0 0 5.6 0l4-4a4 4 0 0 0-5.6-5.6L12 6.4" /></svg>
                @endswitch
                <span class="sr-only">{{ $label }}</span>
            </a>
        @endforeach
    </div>
@endif
