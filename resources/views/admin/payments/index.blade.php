<x-layouts.admin title="Payments">
    <x-admin.page-header title="Payments" subtitle="Every Paystack payment attempt across vote orders, donations and bookings.">
        <x-slot:actions>
            <a href="{{ route('admin.payments.export', request()->query()) }}" class="btn btn-ink">Export CSV</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filters :dates="true" placeholder="Payment reference, email, transaction ID…">
        <x-select name="type" label="Type" :options="$types" :value="request('type')" placeholder="All types" class="[&_label]:text-xs" />
        <x-select name="status" label="Status" :options="collect(\App\Enums\PaymentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" :value="request('status')" placeholder="All statuses" class="[&_label]:text-xs" />
    </x-admin.filters>

    <div class="mb-6 grid grid-cols-3 gap-3 sm:gap-4">
        <x-admin.stat label="Attempts" :value="number_format($totals->attempts)" />
        <x-admin.stat label="Collected" :value="money($totals->collected)" />
        <x-admin.stat label="Refunded" :value="money($totals->refunded)" />
    </div>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Payment reference</th>
                        <th>For</th>
                        <th>Email</th>
                        <th class="text-right"><x-admin.sort-link column="amount" label="Amount" /></th>
                        <th>Status</th>
                        <th>Via</th>
                        <th><x-admin.sort-link column="created_at" label="Created" /></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td><a href="{{ route('admin.payments.show', $payment) }}" class="font-mono text-xs font-medium text-ink-900 hover:text-gold-700">{{ $payment->reference }}</a></td>
                            <td class="text-xs">{{ $types[$payment->payable_type] ?? $payment->payable_type }}<span class="block font-mono text-ink-400">{{ $payment->payable?->reference }}</span></td>
                            <td class="text-xs">{{ $payment->email }}</td>
                            <td class="text-right font-semibold tabular-nums">{{ money($payment->amount, $payment->currency) }}</td>
                            <td>
                                <x-status-badge :status="$payment->status" />
                                @if ($payment->metadata['amount_mismatch'] ?? false)<span class="badge mt-1 block w-fit bg-rose-600 text-white">Amount mismatch</span>@endif
                                @if ($payment->metadata['duplicate_of_paid_order'] ?? false)<span class="badge mt-1 block w-fit bg-amber-500 text-white">Duplicate</span>@endif
                            </td>
                            <td class="text-xs">{{ $payment->verified_via ?? '—' }}</td>
                            <td class="text-xs whitespace-nowrap">{{ $payment->created_at->format('j M Y, g:ia') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state title="No payments match these filters" class="border-0" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $payments->links() }}</div>
</x-layouts.admin>
