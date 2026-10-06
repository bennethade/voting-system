<x-mail::message>
# Welcome, {{ \Illuminate\Support\Str::before($user->name, ' ') }}!

Thanks for joining **{{ setting('site_name') }}**. Please confirm your email address to activate your account and unlock your free vote.

<x-mail::button :url="$url">
Verify my email
</x-mail::button>

This link expires in 60 minutes. If you didn't create an account, you can safely ignore this email.

Thanks,<br>
The {{ setting('site_name') }} team

<x-slot:subcopy>
If the button doesn't work, copy and paste this link into your browser: <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
