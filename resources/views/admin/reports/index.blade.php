@php
    $filters = request()->only(['event', 'from', 'to']);
    $exportUrl = fn (string $report) => route('admin.reports.export', ['report' => $report] + $filters);
@endphp

<x-layouts.admin title="Reports">
    <x-admin.page-header title="Reports" subtitle="Voting, financial and voter figures for the selected event and period." />

    <x-admin.filters :search="false" :dates="true">
        <x-select name="event" label="Event" :options="$events" :value="request('event')" placeholder="All events" class="[&_label]:text-xs lg:col-span-2" />
    </x-admin.filters>

    @if ($voting)
        <section class="mb-10">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-sans text-lg font-semibold">Voting</h3>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ $exportUrl('votes-by-nominee') }}" class="btn btn-outline btn-sm">Export votes by nominee</a>
                    <a href="{{ $exportUrl('votes-by-category') }}" class="btn btn-outline btn-sm">Export votes by category</a>
                </div>
            </div>

            <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <x-admin.stat label="Total valid votes" :value="number_format($voting['total'])" />
                <x-admin.stat label="Free votes" :value="number_format($voting['free'])" />
                <x-admin.stat label="Paid votes" :value="number_format($voting['paid'])" />
                <x-admin.stat label="Admin adjustments" :value="number_format($voting['adjustments'])" hint="Net of positive and negative adjustments" />
            </div>

            <div class="mb-6 grid gap-6 xl:grid-cols-2">
                <div class="card p-5">
                    <h4 class="font-sans text-sm font-semibold">Daily valid votes</h4>
                    <div class="mt-4 h-72"><canvas data-chart="{{ $voting['dailyChart'] }}"></canvas></div>
                </div>
                <div class="card p-5">
                    <h4 class="font-sans text-sm font-semibold">Top categories</h4>
                    <div class="mt-4 h-72"><canvas data-chart="{{ $voting['categoryChart'] }}"></canvas></div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <div class="card overflow-hidden">
                    <h4 class="px-5 pt-5 font-sans text-sm font-semibold">Votes by event</h4>
                    <div class="table-wrap mt-3">
                        <table class="table">
                            <thead><tr><th>Event</th><th class="text-right">Votes</th></tr></thead>
                            <tbody>
                                @forelse ($voting['byEvent'] as $row)
                                    <tr><td>{{ $row->name }}</td><td class="text-right tabular-nums">{{ number_format((int) $row->total) }}</td></tr>
                                @empty
                                    <tr><td colspan="2" class="text-sm text-ink-500">No votes in this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card overflow-hidden xl:col-span-2">
                    <h4 class="px-5 pt-5 font-sans text-sm font-semibold">Top 20 categories</h4>
                    <div class="table-wrap mt-3">
                        <table class="table">
                            <thead><tr><th>Category</th><th>Event</th><th class="text-right">Free</th><th class="text-right">Paid</th><th class="text-right">Adj.</th><th class="text-right">Total</th></tr></thead>
                            <tbody>
                                @forelse ($voting['byCategory'] as $row)
                                    <tr>
                                        <td class="font-medium">{{ $row->category }}</td>
                                        <td class="text-xs text-ink-500">{{ $row->event }}</td>
                                        <td class="text-right tabular-nums">{{ number_format((int) $row->free_votes) }}</td>
                                        <td class="text-right tabular-nums">{{ number_format((int) $row->paid_votes) }}</td>
                                        <td class="text-right tabular-nums">{{ number_format((int) $row->adjustments) }}</td>
                                        <td class="text-right font-semibold tabular-nums">{{ number_format((int) $row->total) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-sm text-ink-500">No votes in this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card overflow-hidden xl:col-span-3">
                    <h4 class="px-5 pt-5 font-sans text-sm font-semibold">Top 25 nominees</h4>
                    <div class="table-wrap mt-3">
                        <table class="table">
                            <thead><tr><th>#</th><th>Nominee</th><th>Category</th><th class="text-right">Free</th><th class="text-right">Paid</th><th class="text-right">Adj.</th><th class="text-right">Total</th></tr></thead>
                            <tbody>
                                @forelse ($voting['byNominee'] as $row)
                                    <tr>
                                        <td class="tabular-nums text-ink-400">{{ $loop->iteration }}</td>
                                        <td class="font-medium">{{ $row->nominee }}</td>
                                        <td class="text-sm text-ink-500">{{ $row->category }}</td>
                                        <td class="text-right tabular-nums">{{ number_format((int) $row->free_votes) }}</td>
                                        <td class="text-right tabular-nums">{{ number_format((int) $row->paid_votes) }}</td>
                                        <td class="text-right tabular-nums">{{ number_format((int) $row->adjustments) }}</td>
                                        <td class="text-right font-semibold tabular-nums">{{ number_format((int) $row->total) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-sm text-ink-500">No votes in this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if ($financial)
        <section class="mb-10">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-sans text-lg font-semibold">Financial</h3>
                <a href="{{ $exportUrl('revenue-daily') }}" class="btn btn-outline btn-sm">Export daily revenue</a>
            </div>

            <div class="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <x-admin.stat label="Revenue" :value="money($financial['revenue'], $currency)" :hint="number_format($financial['successCount']).' successful payments'" />
                <x-admin.stat label="Refunded" :value="money($financial['refunded'], $currency)" :hint="number_format($financial['refundedCount']).' refunded payments'" />
                <x-admin.stat label="Failed / abandoned" :value="number_format($financial['failedCount'])" :hint="number_format($financial['pendingCount']).' still pending'" />
                <x-admin.stat label="Vote orders (paid)" :value="money($financial['voteOrders'], $currency)" />
            </div>
            <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <x-admin.stat label="Donations (paid)" :value="money($financial['donations'], $currency)" />
                <x-admin.stat label="Bookings (paid)" :value="money($financial['bookings'], $currency)" />
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <div class="card p-5 xl:col-span-2">
                    <h4 class="font-sans text-sm font-semibold">Daily revenue</h4>
                    <div class="mt-4 h-72"><canvas data-chart="{{ $financial['dailyChart'] }}"></canvas></div>
                </div>
                <div class="card overflow-hidden">
                    <h4 class="px-5 pt-5 font-sans text-sm font-semibold">Revenue by source</h4>
                    @if ($financial['byType']->isNotEmpty())
                        <div class="mt-4 h-48 px-5"><canvas data-chart="{{ $financial['typeChart'] }}"></canvas></div>
                    @endif
                    <div class="table-wrap mt-3">
                        <table class="table">
                            <thead><tr><th>Source</th><th class="text-right">Payments</th><th class="text-right">Revenue</th></tr></thead>
                            <tbody>
                                @forelse ($financial['byType'] as $row)
                                    <tr>
                                        <td>{{ $row->label }}</td>
                                        <td class="text-right tabular-nums">{{ number_format($row->payments) }}</td>
                                        <td class="text-right tabular-nums">{{ money($row->total, $currency) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-sm text-ink-500">No successful payments in this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if ($voters)
        <section class="mb-10">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-sans text-lg font-semibold">Voters</h3>
                <a href="{{ $exportUrl('voters') }}" class="btn btn-outline btn-sm">Export voters</a>
            </div>

            <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <x-admin.stat label="Total voters" :value="number_format($voters['total'])" hint="All time" />
                <x-admin.stat label="Verified voters" :value="number_format($voters['verified'])" hint="All time" />
                <x-admin.stat label="New registrations" :value="number_format($voters['newInRange'])" hint="In selected period" />
                <x-admin.stat label="Voters who voted" :value="number_format($voters['votedInRange'])" hint="Distinct accounts in selected period" />
            </div>

            <div class="card p-5">
                <h4 class="font-sans text-sm font-semibold">New registrations per day</h4>
                <div class="mt-4 h-72"><canvas data-chart="{{ $voters['dailyChart'] }}"></canvas></div>
            </div>
        </section>
    @endif
</x-layouts.admin>
