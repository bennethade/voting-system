<x-mail::message>
# You're on the list!

Hi {{ \Illuminate\Support\Str::before($booking->contact_name, ' ') }}, your booking is confirmed. We can't wait to celebrate with you.

<x-mail::table>
| | |
|:--|--:|
| Event | **{{ $booking->awardEvent->name }}** |
@if ($booking->awardEvent->event_date)
| Date | {{ $booking->awardEvent->event_date->format('l, j F Y · g:ia') }} |
@endif
@if ($booking->awardEvent->venue)
| Venue | {{ $booking->awardEvent->venue }} |
@endif
| Package | {{ $booking->quantity }} × {{ $booking->eventPackage->name }} |
| Seats | **{{ $booking->seats }}** |
| Amount paid | {{ money($booking->amount, $booking->currency) }} |
| Booking reference | **{{ $booking->reference }}** |
</x-mail::table>

Please keep your booking reference handy — you'll need it at the door.

<x-mail::button :url="$receiptUrl">
View booking receipt
</x-mail::button>

See you there,<br>
The {{ setting('site_name') }} team
</x-mail::message>
