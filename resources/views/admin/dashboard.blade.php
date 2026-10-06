@php
    $chart = fn (array $config) => json_encode($config);
@endphp

<x-layouts.admin title="Dashboard">
    <x-admin.page-header title="Dashboard" :subtitle="$event ? 'Showing '.$event->name : 'All events'">
        <x-slot:actions>
            <form method="GET" class="flex gap-2">
                <select name="event" class="input w-auto" onchange="this.form.submit()" aria-label="Event">
                    <option value="">All events</option>
                    @foreach ($events as $e)
                        <option value="{{ $e->id }}" @selected($event?->id === $e->id)>{{ $e->name }}</option>
                    @endforeach
                </select>
            </form>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($openFlags)
        <a href="{{ route('admin.fraud.index') }}" class="mb-6 flex items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 hover:bg-amber-100">
            <span><strong>{{ $openFlags }}</strong> open fraud {{ \Illuminate\Support\Str::plural('flag', $openFlags) }} waiting for review.</span>
            <span aria-hidden="true">Review →</span>
        </a>
    @endif

    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <x-admin.stat label="Total votes" :value="number_format($totals['total_votes'])" :href="auth()->user()->can('votes.view') ? route('admin.votes.index') : null" />
        <x-admin.stat label="Free votes" :value="number_format($totals['free_votes'])" />
        <x-admin.stat label="Paid votes" :value="number_format($totals['paid_votes'])" />
        @if ($canSeeMoney)
            <x-admin.stat label="Total revenue" :value="money($totals['revenue'])" hint="Successful payments" :href="route('admin.payments.index')" />
            <x-admin.stat label="Vote orders" :value="number_format($totals['orders'])" :href="auth()->user()->can('orders.view') ? route('admin.orders.index') : null" />
            <x-admin.stat label="Successful payments" :value="number_format($totals['successful_payments'])" />
            <x-admin.stat label="Failed payments" :value="number_format($totals['failed_payments'])" />
        @endif
        <x-admin.stat label="Registered voters" :value="number_format($totals['voters'])" />
        <x-admin.stat label="Nominees" :value="number_format($totals['nominees'])" />
        <x-admin.stat label="Categories" :value="number_format($totals['categories'])" />
        <x-admin.stat label="Active events" :value="number_format($totals['active_events'])" hint="Upcoming, voting or paused" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="card p-5 xl:col-span-2">
            <h3 class="font-sans text-base font-semibold">Votes per day <span class="text-sm font-normal text-ink-400">· last 30 days</span></h3>
            <div class="mt-4 h-72">
                <canvas data-chart="{{ $chart(['type' => 'bar', 'labels' => $charts['days'], 'datasets' => [
                    ['label' => 'Free', 'data' => $charts['votes_free'], 'stack' => 'v'],
                    ['label' => 'Paid', 'data' => $charts['votes_paid'], 'stack' => 'v'],
                ]]) }}"></canvas>
            </div>
        </section>
        <section class="card p-5">
            <h3 class="font-sans text-base font-semibold">Free vs paid</h3>
            <div class="mt-4 h-72">
                <canvas data-chart="{{ $chart(['type' => 'doughnut', 'labels' => ['Free', 'Paid'], 'datasets' => [['data' => [$totals['free_votes'], $totals['paid_votes']]]]]) }}"></canvas>
            </div>
        </section>

        @if ($canSeeMoney)
            <section class="card p-5 xl:col-span-2">
                <h3 class="font-sans text-base font-semibold">Revenue per day <span class="text-sm font-normal text-ink-400">· all payment types</span></h3>
                <div class="mt-4 h-72">
                    <canvas data-chart="{{ $chart(['type' => 'line', 'labels' => $charts['days'], 'datasets' => [['label' => 'Revenue', 'data' => $charts['revenue']]]]) }}"></canvas>
                </div>
            </section>
            <section class="card p-5">
                <h3 class="font-sans text-base font-semibold">Payment status</h3>
                <div class="mt-4 h-72">
                    <canvas data-chart="{{ $chart(['type' => 'doughnut', 'labels' => $charts['payment_status']['labels'], 'datasets' => [['data' => $charts['payment_status']['data']]]]) }}"></canvas>
                </div>
            </section>
        @endif

        <section class="card p-5">
            <h3 class="font-sans text-base font-semibold">Votes by category</h3>
            <div class="mt-4 h-80">
                <canvas data-chart="{{ $chart(['type' => 'bar', 'horizontal' => true, 'labels' => $charts['by_category']['labels'], 'datasets' => [['label' => 'Votes', 'data' => $charts['by_category']['data']]]]) }}"></canvas>
            </div>
        </section>
        <section class="card p-5">
            <h3 class="font-sans text-base font-semibold">Top nominees</h3>
            <div class="mt-4 h-80">
                <canvas data-chart="{{ $chart(['type' => 'bar', 'horizontal' => true, 'labels' => $charts['by_nominee']['labels'], 'datasets' => [['label' => 'Votes', 'data' => $charts['by_nominee']['data']]]]) }}"></canvas>
            </div>
        </section>
        <section class="card p-5">
            <h3 class="font-sans text-base font-semibold">Voting activity <span class="text-sm font-normal text-ink-400">· last 24h</span></h3>
            <div class="mt-4 h-80">
                <canvas data-chart="{{ $chart(['type' => 'line', 'labels' => $charts['activity']['labels'], 'datasets' => [['label' => 'Vote transactions', 'data' => $charts['activity']['data']]]]) }}"></canvas>
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @if ($recentVotes->isNotEmpty())
            <section class="card overflow-hidden">
                <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
                    <h3 class="font-sans text-base font-semibold">Latest votes</h3>
                    <a href="{{ route('admin.votes.index') }}" class="text-sm font-medium text-gold-700">View ledger</a>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <tbody>
                            @foreach ($recentVotes as $vote)
                                <tr>
                                    <td><span class="font-medium text-ink-900">{{ $vote->nominee->name }}</span><span class="block text-xs text-ink-400">{{ $vote->category->name }}</span></td>
                                    <td>{{ $vote->vote_type->label() }}</td>
                                    <td class="text-right font-semibold tabular-nums">{{ $vote->votes > 0 ? '+' : '' }}{{ number_format($vote->votes) }}</td>
                                    <td class="text-right text-xs whitespace-nowrap text-ink-400">{{ $vote->created_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
        @if ($recentPayments->isNotEmpty())
            <section class="card overflow-hidden">
                <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
                    <h3 class="font-sans text-base font-semibold">Latest payments</h3>
                    <a href="{{ route('admin.payments.index') }}" class="text-sm font-medium text-gold-700">View all</a>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <tbody>
                            @foreach ($recentPayments as $payment)
                                <tr>
                                    <td><a href="{{ route('admin.payments.show', $payment) }}" class="font-mono text-xs text-ink-900 hover:text-gold-700">{{ $payment->reference }}</a><span class="block text-xs text-ink-400">{{ $payment->email }}</span></td>
                                    <td class="text-right font-semibold tabular-nums">{{ money($payment->amount, $payment->currency) }}</td>
                                    <td><x-status-badge :status="$payment->status" /></td>
                                    <td class="text-right text-xs whitespace-nowrap text-ink-400">{{ $payment->created_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-layouts.admin>
