<x-mail::message>
# Thank you, {{ \Illuminate\Support\Str::before($donation->donor_name, ' ') }}!

Your generous donation helps us celebrate and grow the creator community.

<x-mail::table>
| | |
|:--|--:|
| Amount | **{{ money($donation->amount, $donation->currency) }}** |
| Donation reference | {{ $donation->reference }} |
| Date | {{ $donation->paid_at?->format('j M Y, g:ia') }} |
</x-mail::table>

With gratitude,<br>
The {{ setting('site_name') }} team
</x-mail::message>
