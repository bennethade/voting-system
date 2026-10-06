<x-layouts.admin title="Contact messages">
    <x-admin.page-header title="Contact messages" :subtitle="$unread === 1 ? '1 unread message.' : number_format($unread).' unread messages.'">
        @if ($unread > 0)
            <x-slot:actions>
                <a href="{{ route('admin.messages.index', ['status' => 'new']) }}" class="btn btn-outline">Show unread ({{ number_format($unread) }})</a>
            </x-slot:actions>
        @endif
    </x-admin.page-header>

    <x-admin.filters placeholder="Search name, email or subject…" :dates="true">
        <x-select name="status" label="Status" :options="collect(\App\Models\ContactMessage::STATUSES)->mapWithKeys(fn ($s) => [$s => ucfirst($s)])" :value="request('status')" placeholder="All statuses" class="[&_label]:text-xs" />
    </x-admin.filters>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th><x-admin.sort-link column="name" label="From" /></th>
                        <th><x-admin.sort-link column="subject" label="Subject" /></th>
                        <th>Status</th>
                        <th><x-admin.sort-link column="created_at" label="Received" /></th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($messages as $message)
                        <tr @class(['bg-amber-50/40' => $message->status === 'new'])>
                            <td>
                                <span @class(['block text-ink-900', 'font-semibold' => $message->status === 'new'])>{{ $message->name }}</span>
                                <span class="block text-xs text-ink-400">{{ $message->email }}</span>
                            </td>
                            <td class="max-w-md">
                                <a href="{{ route('admin.messages.show', $message) }}" @class(['hover:text-gold-700', 'font-semibold text-ink-900' => $message->status === 'new'])>{{ \Illuminate\Support\Str::limit($message->subject, 80) }}</a>
                            </td>
                            <td><x-status-badge :status="$message->status" /></td>
                            <td class="text-xs whitespace-nowrap">{{ $message->created_at->format('j M Y, g:ia') }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.messages.show', $message) }}" class="btn btn-outline btn-sm">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state title="No messages" message="Messages sent through the contact form will appear here." class="border-0" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $messages->links() }}</div>
</x-layouts.admin>
