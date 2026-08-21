@php($branding = app(\App\Support\SchoolBranding::class)->data())
<div class="bg-white">
    <x-website.hero eyebrow="Join our community" :title="$page?->hero_title ?: 'Admissions'" :description="$page?->hero_subtitle ?: 'Begin your child’s educational journey. Learn about our enrollment process, requirements, and next steps.'" :action="route('website.contact')" action-label="Talk to admissions" />
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        <div class="mt-12 grid gap-8 lg:grid-cols-2">
            <div class="rounded-2xl bg-slate-50 p-8 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-2xl font-bold text-slate-900">Application Process</h2>
                <ol class="mt-6 space-y-4 text-slate-700">
                    @if($page?->content['steps'] ?? null)
                        @foreach($page->content['steps'] as $step)<li class="flex gap-4"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">{{ $loop->iteration }}</span><div><p class="font-semibold">{{ $step }}</p></div></li>@endforeach
                    @else
                    <li class="flex gap-4">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">1</span>
                        <div>
                            <p class="font-semibold">Submit online application</p>
                            <p class="text-sm text-slate-600">Complete the admission form and upload required documents.</p>
                        </div>
                    </li>
                    <li class="flex gap-4">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">2</span>
                        <div>
                            <p class="font-semibold">Application review</p>
                            <p class="text-sm text-slate-600">Our admissions team will review your application and contacts.</p>
                        </div>
                    </li>
                    <li class="flex gap-4">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">3</span>
                        <div>
                            <p class="font-semibold">Interview & assessment</p>
                            <p class="text-sm text-slate-600">Schedule a campus visit and student assessment.</p>
                        </div>
                    </li>
                    <li class="flex gap-4">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">4</span>
                        <div>
                            <p class="font-semibold">Acceptance & enrollment</p>
                            <p class="text-sm text-slate-600">Complete enrollment paperwork and pay fees.</p>
                        </div>
                    </li>
                    @endif</ol>
            </div>

            <div class="rounded-2xl bg-slate-50 p-8 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-2xl font-bold text-slate-900">Requirements</h2>
                <ul class="mt-6 space-y-3 text-slate-700">
                    <li class="flex items-start gap-3">
                        <svg class="h-5 w-5 text-emerald-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Completed application form</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="h-5 w-5 text-emerald-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Recent photograph (passport size)</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="h-5 w-5 text-emerald-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Birth certificate or ID</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="h-5 w-5 text-emerald-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Previous school records (if applicable)</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="h-5 w-5 text-emerald-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Proof of residency</span>
                    </li>
                </ul>

                <div class="mt-8">
                    <a href="mailto:{{ $branding['email'] }}" class="inline-flex w-full justify-center rounded-full bg-blue-900 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Start application</a>
                </div>
            </div>
        </div>

        <div class="mt-12 rounded-2xl bg-blue-50 p-8 text-center">
            <h3 class="text-xl font-semibold text-slate-900">Questions about admissions?</h3>
            <p class="mt-2 text-slate-700">Contact our admissions office for personalized assistance.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-4">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-blue-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                    <span class="text-sm font-medium">+234 800 000 0000</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-blue-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.07 5.222a2 2 0 002.86 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    <span class="text-sm font-medium">{{ $branding['email'] }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
