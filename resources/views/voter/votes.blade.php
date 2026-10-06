<x-layouts.voter title="My votes">
    @if ($votes->isEmpty())
        <x-empty-state title="No votes yet" message="Your votes will appear here once you start voting.">
            <a href="{{ route('voting.index') }}" class="btn btn-gold">Start voting</a>
        </x-empty-state>
    @else
        <div class="card overflow-hidden">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Nominee</th><th>Category</th><th>Type</th><th class="text-right">Votes</th><th>Status</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($votes as $vote)
                            <tr>
                                <td class="font-medium text-ink-900"><a href="{{ route('nominees.show', $vote->nominee) }}" class="hover:text-gold-700">{{ $vote->nominee->name }}</a></td>
                                <td>{{ $vote->category->name }}<span class="block text-xs text-ink-400">{{ $vote->awardEvent->name }}</span></td>
                                <td>{{ $vote->vote_type->label() }}</td>
                                <td class="text-right font-semibold tabular-nums">{{ number_format($vote->votes) }}</td>
                                <td><x-status-badge :status="$vote->status" /></td>
                                <td class="whitespace-nowrap">{{ $vote->created_at->format('j M Y, g:ia') }}</td>
                                <td class="text-right">
                                    @if ($vote->voteOrder)
                                        <a href="{{ route('receipts.vote', $vote->voteOrder->reference) }}" class="text-sm font-medium text-gold-700">Receipt</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-6">{{ $votes->links() }}</div>
    @endif
</x-layouts.voter>
