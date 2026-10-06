<x-layouts.receipt title="Vote receipt">
    <dl class="divide-y divide-ink-100 px-6 text-sm">
        <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Event</dt><dd class="text-right font-medium">{{ $order->awardEvent->name }}</dd></div>
        <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Nominee</dt><dd class="text-right font-semibold">{{ $order->nominee->name }}</dd></div>
        <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Category</dt><dd class="text-right font-medium">{{ $order->category->name }}</dd></div>
        <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Votes</dt><dd class="font-display text-xl font-semibold">{{ number_format($order->votes) }}</dd></div>
        <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Amount</dt><dd class="font-display text-xl font-semibold">{{ money($order->amount, $order->currency) }}</dd></div>
        <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Order reference</dt><dd class="font-mono font-medium">{{ $order->reference }}</dd></div>
        <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Payment reference</dt><dd class="font-mono text-xs font-medium break-all">{{ $order->payment_reference }}</dd></div>
        <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Voter</dt><dd class="text-right font-medium">{{ $order->voter_name }}<br><span class="text-ink-500">{{ $order->voter_email }}</span></dd></div>
        <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Date</dt><dd class="font-medium">{{ ($order->paid_at ?? $order->created_at)->format('d F Y, g:ia') }}</dd></div>
        <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Status</dt><dd>
            @if ($order->status === \App\Enums\OrderStatus::Paid)
                <span class="badge bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20">Successful</span>
            @else
                <x-status-badge :status="$order->status" />
            @endif
        </dd></div>
    </dl>
</x-layouts.receipt>
