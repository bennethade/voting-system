<x-layouts.admin title="Adjust votes">
    <x-admin.page-header title="Adjust votes" subtitle="Corrections are recorded as new ledger entries with a reason, the old and new totals, and an audit-log entry. Existing votes are never edited.">
        <x-slot:actions><a href="{{ route('admin.votes.index') }}" class="btn btn-outline">Back to ledger</a></x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 xl:grid-cols-5">
        <form method="POST" action="{{ route('admin.votes.adjust.store') }}" class="card space-y-5 p-5 sm:p-6 xl:col-span-2"
              x-data="{ submitting: false }" @submit="if (!confirm('Record this adjustment? It will be permanently logged.')) { $event.preventDefault() } else { submitting = true }">
            @csrf
            <div>
                <label for="nominee_id" class="label">Nominee <span class="text-rose-500">*</span></label>
                <select id="nominee_id" name="nominee_id" class="input" required>
                    <option value="">Select a nominee…</option>
                    @foreach ($nominees->groupBy(fn ($n) => $n->category->awardEvent->name.' — '.$n->category->name) as $group => $items)
                        <optgroup label="{{ $group }}">
                            @foreach ($items as $nominee)
                                <option value="{{ $nominee->id }}" @selected((int) old('nominee_id', $selected) === $nominee->id)>
                                    {{ $nominee->name }} ({{ number_format($nominee->total_votes) }} votes){{ $nominee->category->awardEvent->results_locked_at ? ' — results locked' : '' }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('nominee_id') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <x-input name="adjustment" type="number" label="Adjustment" step="1" required hint="Positive to add votes, negative to remove (e.g. -250)." />
            <x-textarea name="reason" label="Reason" rows="4" required hint="Required. Explain why, with any ticket or reference number." />
            <button class="btn btn-gold w-full" :disabled="submitting">Record adjustment</button>
        </form>

        <section class="card overflow-hidden xl:col-span-3">
            <h3 class="border-b border-ink-100 px-5 py-4 font-sans text-base font-semibold">Recent adjustments</h3>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Nominee</th><th class="text-right">Change</th><th>Totals</th><th>Reason</th><th>By</th></tr></thead>
                    <tbody>
                        @forelse ($recent as $vote)
                            <tr @class(['opacity-60' => ! $vote->isValid()])>
                                <td class="font-medium text-ink-900">{{ $vote->nominee->name }}<span class="block text-xs font-normal text-ink-400">{{ $vote->created_at->format('j M Y, g:ia') }}</span></td>
                                <td @class(['text-right font-semibold tabular-nums', 'text-emerald-700' => $vote->votes > 0, 'text-rose-700' => $vote->votes < 0])>{{ sprintf('%+d', $vote->votes) }}</td>
                                <td class="text-xs whitespace-nowrap">{{ number_format($vote->metadata['old_total'] ?? 0) }} → {{ number_format($vote->metadata['new_total'] ?? 0) }}</td>
                                <td class="max-w-64 text-xs">{{ $vote->reason }}</td>
                                <td class="text-xs">{{ $vote->admin?->name }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-ink-400">No adjustments yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.admin>
