<x-mail::message>
# Your vote has been recorded

Thank you for supporting **{{ $vote->nominee->name }}**!

<x-mail::table>
| | |
|:--|--:|
| Nominee | **{{ $vote->nominee->name }}** |
| Category | {{ $vote->category->name }} |
| Votes | **{{ number_format($vote->votes) }}** |
| Type | {{ $isPaid ? 'Paid' : 'Free' }} |
| Reference | {{ $reference }} |
| Date | {{ $vote->created_at->format('j M Y, g:ia') }} |
</x-mail::table>

@if ($receiptUrl)
<x-mail::button :url="$receiptUrl">
View receipt
</x-mail::button>
@endif

Want to do more? Share **{{ $vote->nominee->name }}**'s page with friends: [{{ $nomineeUrl }}]({{ $nomineeUrl }})

Thanks,<br>
The {{ setting('site_name') }} team
</x-mail::message>
