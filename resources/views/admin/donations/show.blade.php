<x-layouts.admin :title="'Donation '.$donation->reference">
    <x-admin.page-header :title="'Donation '.$donation->reference" :subtitle="money($donation->amount, $donation->currency).' from '.$donation->donor_name">
        <x-slot:actions>
            <a href="{{ route('admin.donations.index') }}" class="btn btn-outline">Back to donations</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card p-5 sm:p-6">
            <h3 class="font-sans text-base font-semibold">Details</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Status</dt><dd><x-status-badge :status="$donation->status" /></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Amount</dt><dd class="font-semibold tabular-nums">{{ money($donation->amount, $donation->currency) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Donor</dt><dd class="text-right">{{ $donation->donor_name }} @if ($donation->is_anonymous)<span class="badge ml-1 bg-ink-50 text-ink-600">Anonymous</span>@endif</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Email</dt><dd class="text-right break-all">{{ $donation->email }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Account</dt>
                    <dd class="text-right">
                        @if ($donation->user && ! $donation->user->isStaff())
                            @can('voters.manage')
                                <a href="{{ route('admin.voters.show', $donation->user) }}" class="hover:text-gold-700">{{ $donation->user->name }}</a>
                            @else
                                {{ $donation->user->name }}
                            @endcan
                        @else
                            {{ $donation->user?->name ?? 'Guest' }}
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Event</dt><dd class="text-right">{{ $donation->awardEvent?->name ?? 'General' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Payment reference</dt><dd class="font-mono text-xs">{{ $donation->payment_reference ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Created</dt><dd>{{ $donation->created_at?->format('j M Y, g:ia') }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Paid</dt><dd>{{ $donation->paid_at?->format('j M Y, g:ia') ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">IP address</dt><dd class="font-mono text-xs">{{ $donation->ip_address ?: '—' }}</dd></div>
            </dl>
            @if ($donation->message)
                <div class="mt-5 border-t border-ink-100 pt-4">
                    <p class="text-xs font-medium text-ink-500">Message</p>
                    <p class="mt-1 text-sm whitespace-pre-line text-ink-800">{{ $donation->message }}</p>
                </div>
            @endif
        </section>

        <section class="card overflow-hidden xl:col-span-2">
            <h3 class="px-5 pt-5 font-sans text-base font-semibold">Payment attempts</h3>
            <div class="table-wrap mt-3">
                <table class="table">
                    <thead><tr><th>Reference</th><th>Channel</th><th class="text-right">Amount</th><th>Status</th><th>Gateway response</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr>
                                <td class="font-mono text-xs">
                                    @can('payments.view')
                                        <a href="{{ route('admin.payments.show', $payment) }}" class="hover:text-gold-700">{{ $payment->reference }}</a>
                                    @else
                                        {{ $payment->reference }}
                                    @endcan
                                </td>
                                <td class="text-sm">{{ $payment->channel ?: '—' }}</td>
                                <td class="text-right tabular-nums">{{ money($payment->amount, $payment->currency) }}</td>
                                <td><x-status-badge :status="$payment->status" /></td>
                                <td class="text-xs">{{ $payment->gateway_response ?: '—' }}</td>
                                <td class="text-xs whitespace-nowrap">{{ $payment->created_at?->format('j M Y, g:ia') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-sm text-ink-500">No payment attempts recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.admin>
