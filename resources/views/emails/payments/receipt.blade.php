<x-mail::message>
# Payment successful

We've received your payment. Here are the details for your records.

<x-mail::table>
| | |
|:--|--:|
| Amount | **{{ money($payment->amount, $payment->currency) }}** |
| Votes purchased | {{ number_format($order->votes) }} for {{ $order->nominee->name }} |
| Order reference | {{ $order->reference }} |
| Payment reference | {{ $payment->reference }} |
| Payment method | {{ $payment->channel ? ucfirst(str_replace('_', ' ', $payment->channel)) : 'Paystack' }} |
| Status | Successful |
| Date | {{ $payment->paid_at?->format('j M Y, g:ia') }} |
</x-mail::table>

<x-mail::button :url="$receiptUrl">
Download receipt
</x-mail::button>

Thanks,<br>
The {{ setting('site_name') }} team
</x-mail::message>
