<div class="bg-white">
    <x-website.hero eyebrow="Meet our faculty" title="Experienced teachers, inspired learners" description="Our dedicated educators bring expertise, creativity, and care to every classroom." :action="route('website.contact')" action-label="Contact the school" />
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
            @forelse($teachers as $teacher)
                @php
                    $name = trim(collect([$teacher->first_name, $teacher->middle_name, $teacher->last_name])->filter()->implode(' '));
                    $initials = strtoupper(collect(preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY))->take(2)->map(fn ($word) => mb_substr($word, 0, 1))->implode(''));
                @endphp
                <article wire:key="teacher-{{ $teacher->id }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition-shadow hover:shadow-lg">
                    <div class="mx-auto mb-4 flex h-32 w-32 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-blue-100 to-blue-200">
                        @if($teacher->photo_path)
                            <img src="{{ Storage::disk('public')->url($teacher->photo_path) }}" alt="{{ $name }}" class="h-full w-full object-cover">
                        @else
                            <span class="text-4xl font-bold text-blue-700">{{ $initials ?: 'T' }}</span>
                        @endif
                    </div>
                    <h2 class="text-center text-xl font-bold text-slate-900">{{ $name ?: 'Faculty member' }}</h2>
                    <p class="mt-1 text-center text-sm text-slate-600">{{ $teacher->subjects->pluck('name')->join(' · ') ?: 'Teaching faculty' }}</p>
                    <p class="mt-4 line-clamp-2 text-center text-sm text-slate-600">{{ $teacher->public_bio ?: 'Our faculty team is here to help every learner thrive.' }}</p>
                </article>
            @empty
                <div class="md:col-span-2 lg:col-span-3 rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-600">Our faculty directory is being updated. Please contact the school office for assistance.</div>
            @endforelse
        </div>
        <div class="mt-16 rounded-2xl bg-slate-50 p-12 text-center">
            <h2 class="text-3xl font-bold text-slate-900">Why choose our teachers?</h2>
            <div class="mt-8 grid gap-8 md:grid-cols-3">
                <div><h3 class="text-lg font-semibold">Expert teachers</h3><p class="mt-2 text-sm text-slate-700">Highly qualified educators combine subject expertise with practical classroom experience.</p></div>
                <div><h3 class="text-lg font-semibold">Personalized attention</h3><p class="mt-2 text-sm text-slate-700">Our team works closely with families to support each learner’s progress.</p></div>
                <div><h3 class="text-lg font-semibold">Modern methods</h3><p class="mt-2 text-sm text-slate-700">Engaging lessons foster curiosity, critical thinking, and confidence.</p></div>
            </div>
        </div>
    </div>
</div>
