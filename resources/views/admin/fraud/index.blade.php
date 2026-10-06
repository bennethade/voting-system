<x-layouts.admin title="Fraud monitoring">
    <x-admin.page-header title="Fraud monitoring" subtitle="Automated heuristics raise flags for human review. Nothing is removed automatically.">
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.fraud.scan') }}">
                @csrf
                <button class="btn btn-ink">Run scan now</button>
            </form>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mb-6 flex flex-wrap gap-2">
        @foreach (\App\Enums\FraudStatus::cases() as $status)
            <a href="{{ route('admin.fraud.index', ['status' => $status->value]) }}" @class(['rounded-full px-4 py-2 text-sm font-medium', 'bg-ink-900 text-white' => request('status', 'open') === $status->value, 'border border-ink-200 bg-white' => request('status', 'open') !== $status->value])>
                {{ $status->label() }} <span class="ml-1 opacity-60">{{ $counts[$status->value] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <x-admin.filters placeholder="Description, IP or email…">
        <input type="hidden" name="status" value="{{ request('status', 'open') }}">
        <x-select name="type" label="Type" :options="\App\Models\FraudFlag::TYPES" :value="request('type')" placeholder="All types" class="[&_label]:text-xs" />
        <x-select name="severity" label="Severity" :options="['low' => 'Low', 'medium' => 'Medium', 'high' => 'High']" :value="request('severity')" placeholder="Any" class="[&_label]:text-xs" />
    </x-admin.filters>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card overflow-hidden xl:col-span-2">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Flag</th><th>Severity</th><th>Subject</th><th>Raised</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($flags as $flag)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.fraud.show', $flag) }}" class="font-medium text-ink-900 hover:text-gold-700">{{ $flag->typeLabel() }}</a>
                                    <span class="block max-w-md text-xs text-ink-500">{{ $flag->description }}</span>
                                </td>
                                <td>
                                    <span @class(['badge', 'bg-rose-50 text-rose-700' => $flag->severity === 'high', 'bg-amber-50 text-amber-800' => $flag->severity === 'medium', 'bg-ink-50 text-ink-600' => $flag->severity === 'low'])>{{ ucfirst($flag->severity) }}</span>
                                </td>
                                <td class="text-xs">
                                    @if ($flag->ip_address)<span class="font-mono">{{ $flag->ip_address }}</span><br>@endif
                                    @if ($flag->email){{ $flag->email }}<br>@endif
                                    @if ($flag->nominee){{ $flag->nominee->name }}@endif
                                </td>
                                <td class="text-xs whitespace-nowrap">{{ $flag->created_at->diffForHumans() }}</td>
                                <td><x-status-badge :status="$flag->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty-state title="No flags here" message="Nothing suspicious detected for this filter." class="border-0" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card overflow-hidden">
            <h3 class="border-b border-ink-100 px-5 py-4 font-sans text-base font-semibold">Busiest IPs · last 24h</h3>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>IP</th><th class="text-right">Entries</th><th class="text-right">Accounts</th></tr></thead>
                    <tbody>
                        @forelse ($topIps as $row)
                            <tr>
                                <td class="font-mono text-xs">
                                    @can('votes.view')<a href="{{ route('admin.votes.index', ['ip' => $row->ip_address]) }}" class="hover:text-gold-700">{{ $row->ip_address }}</a>@else{{ $row->ip_address }}@endcan
                                </td>
                                <td class="text-right tabular-nums">{{ number_format($row->entries) }}</td>
                                <td @class(['text-right tabular-nums', 'font-semibold text-rose-700' => $row->accounts > config('awards.fraud.accounts_per_ip_per_day')])>{{ $row->accounts }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-center text-ink-400">No votes in the last 24 hours.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <div class="mt-6">{{ $flags->links() }}</div>
</x-layouts.admin>
