<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\DonationRequest;
use App\Models\AwardEvent;
use App\Models\Donation;
use App\Services\DonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class DonationController extends Controller
{
    public function create(): View
    {
        return view('donate', [
            'presets' => array_map('floatval', (array) setting('donation_presets', [])),
            'minimum' => (float) setting('donation_min', 100),
        ]);
    }

    public function store(DonationRequest $request, DonationService $donations): RedirectResponse
    {
        $donation = $donations->createDonation(
            (string) $request->validated('amount'),
            [
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'message' => $request->validated('message'),
                'anonymous' => $request->boolean('anonymous'),
            ],
            $request->user(),
            AwardEvent::current(),
            $request->ip(),
        );

        return redirect(URL::temporarySignedRoute('donate.review', now()->addHours(2), ['donation' => $donation->reference]));
    }

    public function show(Donation $donation): View|RedirectResponse
    {
        if ($donation->status === OrderStatus::Paid) {
            return redirect()->route('donate')->with('success', 'This donation has already been completed. Thank you!');
        }

        return view('donate-review', [
            'donation' => $donation,
            'payUrl' => URL::temporarySignedRoute('checkout.pay', now()->addHours(2), [
                'type' => 'donation',
                'reference' => $donation->reference,
            ]),
        ]);
    }
}
