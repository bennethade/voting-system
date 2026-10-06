<x-mail::message>
# Reset your password

Hi {{ \Illuminate\Support\Str::before($user->name, ' ') }}, we received a request to reset the password for your {{ setting('site_name') }} account.

<x-mail::button :url="$url">
Choose a new password
</x-mail::button>

This link expires in {{ $expires }} minutes. If you didn't request a reset, no action is needed — your password won't change.

Thanks,<br>
The {{ setting('site_name') }} team

<x-slot:subcopy>
If the button doesn't work, copy and paste this link into your browser: <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
