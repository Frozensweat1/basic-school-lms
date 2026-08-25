<dialog data-gallery-dialog aria-label="Image preview"
    class="m-auto max-h-[92dvh] w-[min(92vw,72rem)] overflow-hidden rounded-2xl border-0 bg-slate-950 p-0 text-white shadow-2xl backdrop:bg-slate-950/85 backdrop:backdrop-blur-sm">
    <div class="relative flex max-h-[92dvh] flex-col">
        <button data-gallery-close type="button"
            class="absolute right-3 top-3 z-10 inline-flex h-11 w-11 items-center justify-center rounded-full bg-slate-950/75 text-white ring-1 ring-white/20 backdrop-blur transition hover:bg-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
            style="outline-color: var(--brand-accent)" aria-label="Close image preview">
            <svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
        </button>
        <div class="flex min-h-0 flex-1 items-center justify-center bg-black">
            <img data-gallery-image alt="" class="max-h-[78dvh] w-auto max-w-full object-contain">
        </div>
        <p data-gallery-caption class="shrink-0 px-5 py-4 text-center text-sm text-slate-300"></p>
    </div>
</dialog>
