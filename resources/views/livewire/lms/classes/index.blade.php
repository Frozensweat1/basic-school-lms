<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Academic structure</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Classes</h2>
            <p class="mt-1 text-sm text-slate-600">Set up the classes and streams available in each academic year.</p>
        </div>
        @can('create', App\Models\SchoolClass::class)
            <x-button wire:click="create" target="create" :loading="true" icon="plus">Add class</x-button>
        @endcan
    </div>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($classes as $schoolClass)
            <article wire:key="class-{{ $schoolClass->id }}"
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">{{ $schoolClass->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $schoolClass->stream?->name ?? 'No stream' }}</p>
                    </div><span
                        class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $schoolClass->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($schoolClass->status) }}</span>
                </div>
                <dl class="mt-5 space-y-2 text-sm text-slate-600">
                    <div class="flex justify-between gap-3">
                        <dt>Academic year</dt>
                        <dd class="font-medium text-slate-800">{{ $schoolClass->academicYear->name }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt>Code</dt>
                        <dd>{{ $schoolClass->code ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt>Students</dt>
                        <dd>{{ $schoolClass->enrollments_count }}</dd>
                    </div>
                </dl>
                <div class="mt-5 flex justify-end gap-2 border-t border-slate-100 pt-4">
                    @can('update', $schoolClass)
                        <x-ui.icon-button wire:click="edit({{ $schoolClass->id }})" icon="edit" label="Edit {{ $schoolClass->name }}" target="edit({{ $schoolClass->id }})" />
                        @endcan @can('delete', $schoolClass)
                        <x-ui.icon-button wire:click="confirmDelete({{ $schoolClass->id }})" icon="trash" variant="danger" label="Delete {{ $schoolClass->name }}" target="confirmDelete({{ $schoolClass->id }})" />
                    @endcan
                </div>
        </article>@empty<div
                class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500 md:col-span-2 xl:col-span-3">
                No classes configured yet. Add the first class for an academic year.</div>
        @endforelse
    </div>
    <x-pagination :paginator="$classes" />

    @if ($showFormModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center overflow-y-auto bg-slate-950/70 p-4 backdrop-blur-sm"
            style="background-color:rgba(2,6,23,.72)" role="dialog" aria-modal="true">
            <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl ring-1 ring-black/20">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <h3 class="text-lg font-semibold">{{ $editingId ? 'Edit class' : 'Add class' }}</h3><button
                        wire:click="closeModals" type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100"
                        aria-label="Close">×</button>
                </div>
                <form wire:submit="save" class="space-y-5 p-6">
                    <div><label for="year" class="block text-sm font-medium">Academic year</label><select
                            wire:model.blur="academicYearId" id="year"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                            <option value="">Select an academic year</option>
                            @foreach ($years as $year)
                                <option value="{{ $year->id }}">{{ $year->name }}</option>
                            @endforeach
                        </select>
                        @error('academicYearId')
                            <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><label for="class-name" class="block text-sm font-medium">Class name</label><input
                                wire:model.blur="name" id="class-name" type="text" placeholder="Basic 1"
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                            @error('name')
                                <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                            @enderror
                        </div>
                        <div><label for="class-code" class="block text-sm font-medium">Code <span
                                    class="text-slate-400">(optional)</span></label><input wire:model.blur="code"
                                id="class-code" type="text" placeholder="B1"
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                            @error('code')
                                <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div><label for="stream" class="block text-sm font-medium">Stream <span
                                class="text-slate-400">(optional)</span></label><select wire:model.blur="streamId"
                            id="stream" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                            <option value="">No stream</option>
                            @foreach ($streams as $stream)
                                <option value="{{ $stream->id }}">{{ $stream->name }}</option>
                            @endforeach
                        </select>
                        @error('streamId')
                            <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>
                    <div><label for="status" class="block text-sm font-medium">Status</label><select
                            wire:model.blur="status" id="status"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                            <option value="active">Active</option>
                            <option value="archived">Archived</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <x-button wire:click="closeModals" type="button" variant="ghost" target="closeModals" :loading="true">Cancel</x-button>
                        <x-button type="submit" icon="save" target="save" :loading="true">Save class</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm"
            style="background-color:rgba(2,6,23,.72)" role="dialog" aria-modal="true">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-black/20">
                <h3 class="text-lg font-semibold">Delete class?</h3>
                <p class="mt-2 text-sm text-slate-600">Classes with enrolments, subjects, or attendance records are
                    retained as historical records.</p>
                @error('delete')
                    <p class="mt-3 text-sm text-rose-700">{{ $message }}</p>
                @enderror
                <div class="mt-6 flex justify-end gap-3">
                    <x-button wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button>
                    <x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Delete class</x-button>
                </div>
            </div>
        </div>
    @endif
</div>
