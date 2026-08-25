@props(['teacher'])

@php
    $name = trim(collect([$teacher->first_name, $teacher->middle_name, $teacher->last_name])->filter()->implode(' '));
    $initials = strtoupper(collect(preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY))->take(2)->map(fn ($word) => mb_substr($word, 0, 1))->implode('')) ?: 'T';
    $imageUrl = $teacher->photo_path
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($teacher->photo_path)
        : null;
    $subjects = $teacher->relationLoaded('subjects') ? $teacher->subjects->pluck('name')->filter()->join(' · ') : null;
@endphp

<article {{ $attributes->class(['group flex h-full flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl']) }}>
    <div class="relative overflow-hidden bg-slate-100">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="Portrait of {{ $name }}" width="640" height="720" loading="lazy" decoding="async"
                class="aspect-[4/4.5] w-full object-cover object-top transition duration-500 group-hover:scale-[1.02]">
        @else
            <div class="flex aspect-[4/4.5] items-center justify-center" style="background: linear-gradient(145deg, color-mix(in srgb, var(--brand-primary), white 88%), color-mix(in srgb, var(--brand-accent), white 82%))">
                <span class="flex h-24 w-24 items-center justify-center rounded-3xl text-3xl font-black text-white shadow-xl" style="background: var(--brand-primary)">{{ $initials }}</span>
            </div>
        @endif
        <div aria-hidden="true" class="absolute inset-x-0 bottom-0 h-1/4 bg-gradient-to-t from-slate-950/20 to-transparent"></div>
    </div>
    <div class="flex flex-1 flex-col p-6 text-center">
        <h2 class="text-xl font-black tracking-tight text-slate-950">{{ $name ?: 'Faculty member' }}</h2>
        <p class="mt-1 text-sm font-bold text-[var(--brand-primary)]">{{ $subjects ?: 'Teaching faculty' }}</p>
        <p class="mt-4 line-clamp-3 text-sm leading-6 text-slate-600">{{ $teacher->public_bio ?: 'Dedicated to helping every learner grow in knowledge, confidence, and character.' }}</p>
    </div>
</article>
