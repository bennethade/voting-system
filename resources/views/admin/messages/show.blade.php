<x-layouts.admin :title="'Message: '.$msg->subject">
    <x-admin.page-header :title="$msg->subject" :subtitle="'From '.$msg->name.' · '.$msg->created_at->format('j M Y, g:ia')">
        <x-slot:actions>
            <a href="{{ route('admin.messages.index') }}" class="btn btn-outline">Back</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card p-5 sm:p-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <h3 class="font-sans text-base font-semibold">Message</h3>
                    <x-status-badge :status="$msg->status" />
                </div>
                <div class="text-sm leading-relaxed whitespace-pre-line text-ink-800">{{ $msg->message }}</div>
            </section>

            @if ($msg->reply)
                <section class="card p-5 sm:p-6">
                    <h3 class="mb-1 font-sans text-base font-semibold">Your reply</h3>
                    <p class="mb-4 text-xs text-ink-500">
                        Sent {{ $msg->replied_at?->format('j M Y, g:ia') ?? '' }}
                        @if ($msg->replier) by {{ $msg->replier->name }} @endif
                    </p>
                    <div class="text-sm leading-relaxed whitespace-pre-line text-ink-800">{{ $msg->reply }}</div>
                </section>
            @endif

            <section class="card p-5 sm:p-6">
                <h3 class="mb-4 font-sans text-base font-semibold">{{ $msg->reply ? 'Send another reply' : 'Reply' }}</h3>
                <form method="POST" action="{{ route('admin.messages.reply', $msg) }}" class="space-y-4" onsubmit="return confirm(@js('Send this reply to '.$msg->email.'?'))">
                    @csrf
                    <x-textarea name="reply" label="Reply" rows="8" required maxlength="5000" :hint="'Emailed to '.$msg->email.' with the original message quoted below it.'" />
                    <button class="btn btn-gold">Send reply</button>
                </form>
            </section>
        </div>

        <div class="space-y-6">
            <section class="card space-y-3 p-5 text-sm sm:p-6">
                <h3 class="font-sans text-base font-semibold">Sender</h3>
                <dl class="space-y-2">
                    <div><dt class="text-xs text-ink-500">Name</dt><dd class="font-medium text-ink-900">{{ $msg->name }}</dd></div>
                    <div><dt class="text-xs text-ink-500">Email</dt><dd><a href="mailto:{{ $msg->email }}" class="text-gold-700 hover:underline">{{ $msg->email }}</a></dd></div>
                    @if ($msg->phone)
                        <div><dt class="text-xs text-ink-500">Phone</dt><dd><a href="tel:{{ $msg->phone }}" class="text-gold-700 hover:underline">{{ $msg->phone }}</a></dd></div>
                    @endif
                    <div><dt class="text-xs text-ink-500">Received</dt><dd>{{ $msg->created_at->format('j M Y, g:ia') }}</dd></div>
                </dl>
                <div class="border-t border-ink-100 pt-3 text-xs text-ink-400">
                    <p>IP address: {{ $msg->ip_address ?? 'unknown' }}</p>
                    <p class="mt-1 break-all">User agent: {{ $msg->user_agent ?? 'unknown' }}</p>
                </div>
            </section>

            <section class="card space-y-3 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Status</h3>
                <form method="POST" action="{{ route('admin.messages.status', $msg) }}" class="flex gap-2">
                    @csrf
                    <x-select name="status" :options="collect(\App\Models\ContactMessage::STATUSES)->mapWithKeys(fn ($s) => [$s => ucfirst($s)])" :value="$msg->status" class="flex-1" aria-label="Status" />
                    <button class="btn btn-ink">Update</button>
                </form>
                @if ($msg->status !== 'archived')
                    <form method="POST" action="{{ route('admin.messages.status', $msg) }}">
                        @csrf
                        <input type="hidden" name="status" value="archived">
                        <button class="text-sm font-medium text-ink-600 hover:text-ink-900">Archive this message</button>
                    </form>
                @endif
            </section>
        </div>
    </div>
</x-layouts.admin>
