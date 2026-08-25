<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Website CMS</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">Branding and contact settings</h1>
            <p class="mt-1 max-w-2xl text-sm text-slate-600">Keep the public website identity, contact routes, location, and social profiles accurate.</p>
        </div>
        <span wire:dirty class="inline-flex w-fit items-center gap-2 rounded-full bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-800">
            <span class="h-2 w-2 rounded-full bg-amber-500"></span> Unsaved changes
        </span>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="website-identity-heading">
                <div>
                    <h2 id="website-identity-heading" class="text-lg font-semibold text-slate-900">Identity</h2>
                    <p class="mt-1 text-sm text-slate-500">Used in the website header, footer, browser metadata, and contact areas.</p>
                </div>

                <div class="mt-5 grid gap-5 sm:grid-cols-[8rem_1fr] sm:items-start">
                    <div class="aspect-square overflow-hidden rounded-2xl bg-gradient-to-br from-blue-950 to-cyan-700 ring-1 ring-slate-200">
                        @if ($logo)
                            <img src="{{ $logo->temporaryUrl() }}" alt="New logo preview" class="h-full w-full object-contain bg-white p-2">
                        @elseif ($currentLogoPath)
                            <img src="{{ Storage::disk('public')->url($currentLogoPath) }}" alt="Current logo" class="h-full w-full object-contain bg-white p-2">
                        @else
                            <div class="flex h-full items-center justify-center text-3xl font-black text-white">{{ str($siteName)->explode(' ')->filter()->take(2)->map(fn ($word) => str($word)->substr(0, 1))->implode('') ?: 'SC' }}</div>
                        @endif
                    </div>
                    <div>
                        <label for="website-logo" class="block text-sm font-medium text-slate-700">School logo</label>
                        <input id="website-logo" wire:model="logo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-600 file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:font-semibold file:text-slate-700">
                        <p class="mt-1 text-xs text-slate-500">JPG, PNG, or WebP up to 4 MB. A school-initial avatar is used when no logo is set.</p>
                        <p wire:loading wire:target="logo" class="mt-1 text-xs font-semibold text-blue-700">Preparing logo preview...</p>
                        @error('logo') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        @if ($currentLogoPath)
                            <label class="mt-3 inline-flex items-center gap-2 text-sm text-slate-600"><input wire:model="removeLogo" type="checkbox" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500"> Remove current logo</label>
                        @endif
                    </div>
                </div>

                <div class="mt-5 space-y-4">
                    <div><label for="site-name" class="block text-sm font-medium text-slate-700">Site name</label><input id="site-name" wire:model.blur="siteName" class="mt-1 w-full rounded-lg border-slate-300">@error('siteName')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    <div><label for="site-tagline" class="block text-sm font-medium text-slate-700">Tagline</label><textarea id="site-tagline" wire:model.blur="tagline" rows="2" class="mt-1 w-full rounded-lg border-slate-300"></textarea>@error('tagline')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="website-contact-heading">
                <div>
                    <h2 id="website-contact-heading" class="text-lg font-semibold text-slate-900">Contact and location</h2>
                    <p class="mt-1 text-sm text-slate-500">Displayed on the contact page and in the site footer.</p>
                </div>
                <div class="mt-5 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label for="site-email" class="block text-sm font-medium text-slate-700">Email</label><input id="site-email" wire:model.blur="email" type="email" class="mt-1 w-full rounded-lg border-slate-300" placeholder="hello@school.edu">@error('email')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        <div><label for="site-phone" class="block text-sm font-medium text-slate-700">Phone</label><input id="site-phone" wire:model.blur="phone" type="tel" class="mt-1 w-full rounded-lg border-slate-300" placeholder="+233 ...">@error('phone')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    </div>
                    <div><label for="site-address" class="block text-sm font-medium text-slate-700">Address</label><textarea id="site-address" wire:model.blur="address" rows="3" class="mt-1 w-full rounded-lg border-slate-300"></textarea>@error('address')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label for="site-latitude" class="block text-sm font-medium text-slate-700">Map latitude</label><input id="site-latitude" wire:model.blur="latitude" inputmode="decimal" class="mt-1 w-full rounded-lg border-slate-300" placeholder="5.6037">@error('latitude')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        <div><label for="site-longitude" class="block text-sm font-medium text-slate-700">Map longitude</label><input id="site-longitude" wire:model.blur="longitude" inputmode="decimal" class="mt-1 w-full rounded-lg border-slate-300" placeholder="-0.1870">@error('longitude')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    </div>
                    <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">Coordinates allow the public contact page to provide a reliable map and directions link.</p>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="website-social-heading">
                <div>
                    <h2 id="website-social-heading" class="text-lg font-semibold text-slate-900">Social profiles</h2>
                    <p class="mt-1 text-sm text-slate-500">Only completed profiles are shown publicly.</p>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div><label for="social-facebook" class="block text-sm font-medium text-slate-700">Facebook</label><input id="social-facebook" wire:model.blur="facebook" type="url" class="mt-1 w-full rounded-lg border-slate-300" placeholder="https://facebook.com/...">@error('facebook')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    <div><label for="social-instagram" class="block text-sm font-medium text-slate-700">Instagram</label><input id="social-instagram" wire:model.blur="instagram" type="url" class="mt-1 w-full rounded-lg border-slate-300" placeholder="https://instagram.com/...">@error('instagram')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    <div><label for="social-youtube" class="block text-sm font-medium text-slate-700">YouTube</label><input id="social-youtube" wire:model.blur="youtube" type="url" class="mt-1 w-full rounded-lg border-slate-300" placeholder="https://youtube.com/@...">@error('youtube')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    <div><label for="social-x" class="block text-sm font-medium text-slate-700">X (Twitter)</label><input id="social-x" wire:model.blur="x" type="url" class="mt-1 w-full rounded-lg border-slate-300" placeholder="https://x.com/...">@error('x')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    <div class="sm:col-span-2"><label for="social-whatsapp" class="block text-sm font-medium text-slate-700">WhatsApp link</label><input id="social-whatsapp" wire:model.blur="whatsapp" type="url" class="mt-1 w-full rounded-lg border-slate-300" placeholder="https://wa.me/233...">@error('whatsapp')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="website-colours-heading">
                <div>
                    <h2 id="website-colours-heading" class="text-lg font-semibold text-slate-900">Brand palette</h2>
                    <p class="mt-1 text-sm text-slate-500">Applied to key accents and calls to action throughout the public site.</p>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    @foreach ([['primaryColor', 'Primary'], ['secondaryColor', 'Secondary'], ['accentColor', 'Accent']] as [$property, $label])
                        <label class="rounded-xl border border-slate-200 p-3 text-sm font-medium text-slate-700">
                            <span>{{ $label }}</span>
                            <span class="mt-2 flex items-center gap-2"><input wire:model.blur="{{ $property }}" type="color" class="h-10 w-12 cursor-pointer rounded border-0 bg-transparent p-0"><span class="font-mono text-xs">{{ ${$property} }}</span></span>
                            @error($property)<span class="mt-1 block text-sm text-rose-700">{{ $message }}</span>@enderror
                        </label>
                    @endforeach
                </div>
                <div class="mt-5 overflow-hidden rounded-xl" style="background: linear-gradient(120deg, {{ $primaryColor }}, {{ $secondaryColor }});">
                    <div class="p-5 text-white"><p class="text-xs font-semibold uppercase tracking-widest opacity-70">Live palette preview</p><p class="mt-2 text-lg font-bold">{{ $siteName ?: 'School name' }}</p><span class="mt-3 inline-flex rounded-lg px-3 py-2 text-xs font-bold text-slate-950" style="background-color: {{ $accentColor }};">Primary action</span></div>
                </div>
            </section>
        </div>

        <div class="sticky bottom-4 z-10 flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur">
            <p class="hidden text-sm text-slate-500 sm:block">Changes appear on the public website after saving.</p>
            <div class="ml-auto">
                <x-button type="submit" icon="save" target="save" :loading="true">Save website settings</x-button>
            </div>
        </div>
    </form>
</div>
