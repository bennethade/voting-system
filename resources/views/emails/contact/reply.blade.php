<x-mail::message>
Hi {{ \Illuminate\Support\Str::before($msg->name, ' ') }},

{!! nl2br(e($msg->reply)) !!}

Best regards,<br>
The {{ setting('site_name') }} team

<x-mail::panel>
**Your original message ({{ $msg->created_at->format('j M Y') }}):**<br>
{{ \Illuminate\Support\Str::limit($msg->message, 600) }}
</x-mail::panel>
</x-mail::message>
