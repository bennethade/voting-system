<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PaymentException;
use App\Http\Requests\Admin\SettingRequest;
use App\Services\AuditLogger;
use App\Services\PaystackService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SettingController extends AdminController
{
    public function __construct(AuditLogger $audit, private readonly SettingsService $settings)
    {
        parent::__construct($audit);
    }

    public function edit(): View
    {
        $values = array_intersect_key($this->settings->all(), SettingsService::DEFAULTS);
        $values['donation_presets'] = implode(', ', (array) ($values['donation_presets'] ?? []));

        $publicKey = (string) config('services.paystack.public_key');

        return view('admin.settings.edit', [
            'values' => $values,
            'appName' => config('app.name'),
            'paystack' => [
                'secret_configured' => filled(config('services.paystack.secret_key')),
                'public_key_masked' => $this->maskKey($publicKey),
                'live' => str_contains($publicKey, '_live_'),
                'webhook_url' => route('webhooks.paystack'),
                'settlement' => $this->settlementSummary(),
            ],
        ]);
    }

    public function update(SettingRequest $request): RedirectResponse
    {
        $current = $this->settings->all();
        $new = $request->settings();

        $old = [];
        $changed = [];
        foreach ($new as $key => $value) {
            $before = $current[$key] ?? null;
            // A cleared value falls back to its default, so compare against that.
            $after = $value ?? SettingsService::DEFAULTS[$key] ?? null;

            if ($before !== $after) {
                $old[$key] = $before;
                $changed[$key] = $value;
            }
        }

        if ($changed === []) {
            return redirect()->route('admin.settings.edit')->with('success', 'No changes to save.');
        }

        $this->settings->set($changed);
        $this->audit->log('settings.updated', 'Updated site settings', null, $old, $changed);

        return redirect()->route('admin.settings.edit')->with('success', 'Settings saved.');
    }

    /** Human summary of the .env settlement routing, or the misconfiguration. */
    private function settlementSummary(): string
    {
        try {
            $params = app(PaystackService::class)->splitParameters();
        } catch (PaymentException) {
            return 'Misconfigured — payments are blocked. Check PAYSTACK_SPLIT_CODE / PAYSTACK_SUBACCOUNT_CODE in .env and the log.';
        }

        if (isset($params['split_code'])) {
            return "Split {$params['split_code']}";
        }

        if (isset($params['subaccount'])) {
            return "Subaccount {$params['subaccount']}"
                .(isset($params['transaction_charge']) ? ' · main account keeps '.money($params['transaction_charge'] / 100).' per payment' : '')
                .(isset($params['bearer']) ? " · fees borne by {$params['bearer']}" : '');
        }

        return 'None — all payments settle to the main Paystack account.';
    }

    /** "pk_test_abc...1234" => "pk_test_••••1234"; never exposes the full key. */
    private function maskKey(string $key): ?string
    {
        if ($key === '') {
            return null;
        }

        $prefix = preg_match('/^((?:pk|sk)_(?:test|live)_)/', $key, $m) ? $m[1] : '';

        return $prefix.'••••'.(strlen($key) > strlen($prefix) + 8 ? Str::substr($key, -4) : '');
    }
}
