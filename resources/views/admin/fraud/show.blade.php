<x-layouts.admin :title="'Fraud flag #'.$flag->id">
    <x-admin.page-header :title="$flag->typeLabel()" :subtitle="$flag->description">
        <x-slot:actions><a href="{{ route('admin.fraud.index') }}" class="btn btn-outline">Back</a></x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card">
                <dl class="grid text-sm sm:grid-cols-2">
                    @foreach ([
                        'Status' => null,
                        'Severity' => ucfirst($flag->severity),
                        'Event' => $flag->awardEvent?->name ?? '—',
                        'Nominee' => $flag->nominee?->name ?? '—',
                        'IP address' => $flag->ip_address ?? '—',
                        'Email' => $flag->email ?? '—',
                        'User' => $flag->user ? $flag->user->name.' ('.$flag->user->email.')' : '—',
                        'Raised' => $flag->created_at->format('j M Y, g:i:sa'),
                        'Reviewed' => $flag->reviewed_at ? $flag->reviewed_at->format('j M Y, g:ia').' by '.$flag->reviewer?->name : '—',
                        'Review notes' => $flag->review_notes ?? '—',
                    ] as $label => $value)
                        <div class="border-b border-ink-100 px-5 py-3.5">
                            <dt class="text-xs text-ink-500">{{ $label }}</dt>
                            <dd class="mt-0.5 font-medium break-words text-ink-900">@if ($label === 'Status')<x-status-badge :status="$flag->status" />@else{{ $value }}@endif</dd>
                        </div>
                    @endforeach
                </dl>
                @if ($flag->metadata)
                    <details class="px-5 py-4 text-sm">
                        <summary class="cursor-pointer font-medium text-ink-600">Detection data</summary>
                        <pre class="mt-2 overflow-x-auto rounded-lg bg-ink-50 p-3 text-xs">{{ json_encode($flag->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                @endif
            </section>

            <section class="card overflow-hidden">
                <h3 class="border-b border-ink-100 px-5 py-4 font-sans text-base font-semibold">Votes covered by this flag <span class="text-sm font-normal text-ink-400">({{ $related->count() }} valid entries, {{ number_format($related->sum('votes')) }} votes)</span></h3>
                <div class="table-wrap max-h-[28rem] overflow-y-auto">
                    <table class="table">
                        <thead><tr><th>#</th><th>Nominee</th><th>Voter</th><th class="text-right">Votes</th><th>IP</th><th>Time</th></tr></thead>
                        <tbody>
                            @forelse ($related->take(200) as $vote)
                                <tr>
                                    <td class="text-xs text-ink-400">{{ $vote->id }}</td>
                                    <td>{{ $vote->nominee->name }}</td>
                                    <td class="text-xs">{{ $vote->user?->email ?? 'Guest' }}</td>
                                    <td class="text-right tabular-nums">{{ number_format($vote->votes) }}</td>
                                    <td class="font-mono text-xs">{{ $vote->ip_address }}</td>
                                    <td class="text-xs whitespace-nowrap">{{ $vote->created_at->format('j M, g:i:sa') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-6 text-center text-ink-400">No valid votes are linked to this flag.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <form method="POST" action="{{ route('admin.fraud.resolve', $flag) }}" class="card h-fit space-y-4 p-5" x-data="{ status: '{{ old('status', 'reviewing') }}' }"
              @submit="if (status === 'invalidated' && !confirm('Invalidate {{ $related->count() }} vote entries? They will be removed from the totals (kept in the ledger).')) $event.preventDefault()">
            @csrf
            <h3 class="font-sans text-base font-semibold">Review decision</h3>
            <fieldset class="space-y-2">
                @foreach ([
                    'reviewing' => ['Under review', 'Mark as being investigated.'],
                    'approved' => ['Approve', 'Activity is legitimate; keep the votes.'],
                    'rejected' => ['Dismiss', 'False positive; close the flag.'],
                    'invalidated' => ['Invalidate votes', 'Remove the covered votes from the totals.'],
                ] as $value => [$label, $hint])
                    @if ($value !== 'invalidated' || auth()->user()->can('votes.invalidate'))
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-ink-100 p-3 has-[:checked]:border-gold-400 has-[:checked]:bg-gold-50">
                            <input type="radio" name="status" value="{{ $value }}" x-model="status" class="mt-1">
                            <span><span class="block text-sm font-medium">{{ $label }}</span><span class="block text-xs text-ink-500">{{ $hint }}</span></span>
                        </label>
                    @endif
                @endforeach
            </fieldset>
            @error('status') <p class="field-error">{{ $message }}</p> @enderror
            <x-textarea name="notes" label="Notes" rows="4" hint="Required when invalidating votes. Saved to the audit log." />
            <button class="btn btn-ink w-full">Save decision</button>
        </form>
    </div>
</x-layouts.admin>
