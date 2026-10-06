<x-layouts.admin :title="'Booking '.$booking->reference">
    <x-admin.page-header :title="'Booking '.$booking->reference" :subtitle="$booking->contact_name.' · '.money($booking->amount, $booking->currency)">
        <x-slot:actions>
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline">Back to bookings</a>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($oversold)
        <div class="mb-6 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="alert">
            <strong>Oversold:</strong> this booking was paid after the package had sold out. Please contact the customer to arrange seating or a refund.
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card p-5 sm:p-6">
            <h3 class="font-sans text-base font-semibold">Booking details</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Status</dt><dd><x-status-badge :status="$booking->status" /></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Event</dt><dd class="text-right">{{ $booking->awardEvent?->name ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Package</dt><dd class="text-right">{{ $booking->eventPackage?->name ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Quantity</dt><dd class="tabular-nums">{{ $booking->quantity }} × {{ money($booking->unit_price, $booking->currency) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Seats</dt><dd class="tabular-nums">{{ $booking->seats }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Total</dt><dd class="font-semibold tabular-nums">{{ money($booking->amount, $booking->currency) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Payment reference</dt><dd class="font-mono text-xs">{{ $booking->payment_reference ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Created</dt><dd>{{ $booking->created_at?->format('j M Y, g:ia') }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Paid</dt><dd>{{ $booking->paid_at?->format('j M Y, g:ia') ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">IP address</dt><dd class="font-mono text-xs">{{ $booking->ip_address ?: '—' }}</dd></div>
            </dl>

            <h3 class="mt-6 border-t border-ink-100 pt-5 font-sans text-base font-semibold">Contact</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Name</dt><dd class="text-right">{{ $booking->contact_name }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Email</dt><dd class="text-right break-all">{{ $booking->contact_email }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Phone</dt><dd>{{ $booking->contact_phone ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Organization</dt><dd class="text-right">{{ $booking->organization ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-500">Account</dt>
                    <dd class="text-right">
                        @if ($booking->user && ! $booking->user->isStaff())
                            @can('voters.manage')
                                <a href="{{ route('admin.voters.show', $booking->user) }}" class="hover:text-gold-700">{{ $booking->user->name }}</a>
                            @else
                                {{ $booking->user->name }}
                            @endcan
                        @else
                            {{ $booking->user?->name ?? 'Guest' }}
                        @endif
                    </dd>
                </div>
            </dl>
            @if ($booking->notes)
                <div class="mt-5 border-t border-ink-100 pt-4">
                    <p class="text-xs font-medium text-ink-500">Notes</p>
                    <p class="mt-1 text-sm whitespace-pre-line text-ink-800">{{ $booking->notes }}</p>
                </div>
            @endif
        </section>

        <div class="space-y-6 xl:col-span-2">
            <section class="card overflow-hidden">
                <h3 class="px-5 pt-5 font-sans text-base font-semibold">Attendees ({{ $attendees->count() }})</h3>
                <div class="table-wrap mt-3">
                    <table class="table">
                        <thead><tr><th>#</th><th>Name</th><th>Email</th></tr></thead>
                        <tbody>
                            @forelse ($attendees as $i => $attendee)
                                <tr>
                                    <td class="tabular-nums text-ink-400">{{ $i + 1 }}</td>
                                    <td>{{ $attendee['name'] ?? '—' }}</td>
                                    <td class="text-sm">{{ $attendee['email'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-sm text-ink-500">No attendee names were provided.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card overflow-hidden">
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
    </div>
</x-layouts.admin>
