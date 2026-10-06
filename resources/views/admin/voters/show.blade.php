<x-layouts.admin :title="$voter->name">
    <x-admin.page-header :title="$voter->name" :subtitle="$voter->email">
        <x-slot:actions>
            <a href="{{ route('admin.voters.index') }}" class="btn btn-outline">Back to voters</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-admin.stat label="Free votes" :value="number_format($stats['free'])" />
        <x-admin.stat label="Paid votes" :value="number_format($stats['paid'])" />
        <x-admin.stat label="Total valid votes" :value="number_format($stats['total'])" hint="Including any adjustments" />
        <x-admin.stat label="Amount spent" :value="money($stats['spent'])" hint="Paid vote orders" />
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6">
            <section class="card p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Profile</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Status</dt><dd><x-status-badge :status="$voter->status" /></dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Email</dt><dd class="text-right break-all">{{ $voter->email }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Email verified</dt><dd>{{ $voter->email_verified_at?->format('j M Y, g:ia') ?? 'Not verified' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Phone</dt><dd>{{ $voter->phone ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Registered</dt><dd>{{ $voter->created_at?->format('j M Y, g:ia') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Last sign-in</dt><dd>{{ $voter->last_login_at?->format('j M Y, g:ia') ?? 'Never' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-500">Last sign-in IP</dt><dd class="font-mono text-xs">{{ $voter->last_login_ip ?: '—' }}</dd></div>
                </dl>
            </section>

            <section class="card p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Change account status</h3>
                <p class="hint">Inactive or suspended voters are signed out immediately and cannot vote.</p>
                <form method="POST" action="{{ route('admin.voters.status', $voter) }}" class="mt-4 space-y-4" onsubmit="return confirm('Change this voter\'s status?')">
                    @csrf
                    <x-select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended']" :value="$voter->status" required />
                    <x-textarea name="reason" label="Reason" rows="3" required hint="Recorded in the audit log (max 500 characters)." maxlength="500" />
                    <button class="btn btn-ink w-full">Update status</button>
                </form>
            </section>

            <section class="card overflow-hidden">
                <h3 class="px-5 pt-5 font-sans text-base font-semibold">IP addresses used to vote</h3>
                <div class="table-wrap mt-3">
                    <table class="table">
                        <thead><tr><th>IP address</th><th class="text-right">Votes</th><th>Last used</th></tr></thead>
                        <tbody>
                            @forelse ($ips as $ip)
                                <tr>
                                    <td class="font-mono text-xs">{{ $ip->ip_address }}</td>
                                    <td class="text-right tabular-nums">{{ number_format($ip->uses) }}</td>
                                    <td class="text-xs whitespace-nowrap">{{ $ip->last_used_at }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-sm text-ink-500">No votes recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="space-y-6 xl:col-span-2">
            <section class="card overflow-hidden">
                <h3 class="px-5 pt-5 font-sans text-base font-semibold">Recent votes</h3>
                <div class="table-wrap mt-3">
                    <table class="table">
                        <thead><tr><th>Nominee</th><th>Type</th><th class="text-right">Votes</th><th>Status</th><th>IP</th><th>Date</th></tr></thead>
                        <tbody>
                            @forelse ($recentVotes as $vote)
                                <tr>
                                    <td>
                                        <span class="font-medium">{{ $vote->nominee?->name ?? '—' }}</span>
                                        <span class="block text-xs text-ink-400">{{ $vote->category?->name }}</span>
                                    </td>
                                    <td><x-status-badge :status="$vote->vote_type" /></td>
                                    <td class="text-right tabular-nums">{{ number_format($vote->votes) }}</td>
                                    <td><x-status-badge :status="$vote->status" /></td>
                                    <td class="font-mono text-xs">{{ $vote->ip_address ?: '—' }}</td>
                                    <td class="text-xs whitespace-nowrap">{{ $vote->created_at?->format('j M Y, g:ia') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-sm text-ink-500">No votes yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card overflow-hidden">
                <h3 class="px-5 pt-5 font-sans text-base font-semibold">Recent vote orders</h3>
                <div class="table-wrap mt-3">
                    <table class="table">
                        <thead><tr><th>Reference</th><th>Nominee</th><th class="text-right">Votes</th><th class="text-right">Amount</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                            @forelse ($recentOrders as $order)
                                <tr>
                                    <td class="font-mono text-xs">
                                        @can('orders.view')
                                            <a href="{{ route('admin.orders.show', $order) }}" class="hover:text-gold-700">{{ $order->reference }}</a>
                                        @else
                                            {{ $order->reference }}
                                        @endcan
                                    </td>
                                    <td>{{ $order->nominee?->name ?? '—' }}</td>
                                    <td class="text-right tabular-nums">{{ number_format($order->votes) }}</td>
                                    <td class="text-right tabular-nums">{{ money($order->amount, $order->currency) }}</td>
                                    <td><x-status-badge :status="$order->status" /></td>
                                    <td class="text-xs whitespace-nowrap">{{ $order->created_at?->format('j M Y, g:ia') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-sm text-ink-500">No orders.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card overflow-hidden">
                <h3 class="px-5 pt-5 font-sans text-base font-semibold">Recent payments</h3>
                <div class="table-wrap mt-3">
                    <table class="table">
                        <thead><tr><th>Reference</th><th>Channel</th><th class="text-right">Amount</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                            @forelse ($recentPayments as $payment)
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
                                    <td class="text-xs whitespace-nowrap">{{ $payment->created_at?->format('j M Y, g:ia') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-sm text-ink-500">No payments.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-layouts.admin>
