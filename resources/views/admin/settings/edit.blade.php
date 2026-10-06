<x-layouts.admin title="Site settings">
    <x-admin.page-header title="Site settings" subtitle="Branding, contact details and home page content. Leave a field blank to use the default." />

    <form method="POST" action="{{ route('admin.settings.update') }}" class="grid gap-6 xl:grid-cols-3">
        @csrf
        @method('PUT')

        <div class="space-y-6 xl:col-span-2">
            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">General</h3>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="site_name" label="Site name" :value="$values['site_name']" maxlength="100" :placeholder="$appName" :hint="'Defaults to '.$appName.'.'" />
                    <x-input name="site_tagline" label="Tagline" :value="$values['site_tagline']" maxlength="200" />
                </div>
                <x-textarea name="footer_text" label="Footer text" :value="$values['footer_text']" rows="2" maxlength="500" hint="Short line shown in the site footer." />
            </section>

            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">SEO</h3>
                <x-textarea name="seo_description" label="Default meta description" :value="$values['seo_description']" rows="3" maxlength="300" hint="Used by search engines and link previews when a page has no description of its own (max 300 characters)." />
            </section>

            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Contact</h3>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="contact_email" type="email" label="Contact email" :value="$values['contact_email']" maxlength="150" />
                    <x-input name="contact_phone" label="Contact phone" :value="$values['contact_phone']" maxlength="50" />
                </div>
                <x-textarea name="contact_address" label="Address" :value="$values['contact_address']" rows="2" maxlength="300" />
            </section>

            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Social</h3>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="social_instagram" type="url" label="Instagram" :value="$values['social_instagram']" placeholder="https://instagram.com/…" />
                    <x-input name="social_x" type="url" label="X (Twitter)" :value="$values['social_x']" placeholder="https://x.com/…" />
                    <x-input name="social_tiktok" type="url" label="TikTok" :value="$values['social_tiktok']" placeholder="https://tiktok.com/@…" />
                    <x-input name="social_youtube" type="url" label="YouTube" :value="$values['social_youtube']" placeholder="https://youtube.com/@…" />
                    <x-input name="social_facebook" type="url" label="Facebook" :value="$values['social_facebook']" placeholder="https://facebook.com/…" />
                </div>
            </section>

            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Home page</h3>
                <x-input name="hero_title" label="Hero title" :value="$values['hero_title']" maxlength="150" />
                <x-textarea name="hero_subtitle" label="Hero subtitle" :value="$values['hero_subtitle']" rows="2" maxlength="300" />
                <x-textarea name="home_about" label="About section" :value="$values['home_about']" rows="5" maxlength="5000" hint="Plain text shown in the home page About section. Line breaks are kept." />
            </section>
        </div>

        <div class="space-y-6">
            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Donations</h3>
                <x-input name="donation_presets" label="Preset amounts" :value="$values['donation_presets']" required maxlength="200" hint="Comma-separated, up to 8 amounts, e.g. 1000, 5000, 10000." />
                <x-input name="donation_min" type="number" min="1" step="0.01" label="Minimum donation" :value="$values['donation_min']" required />
            </section>

            <section class="card space-y-4 p-5 text-sm sm:p-6">
                <h3 class="font-sans text-base font-semibold">Payment gateway</h3>
                <dl class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-ink-500">Paystack secret key</dt>
                        <dd>
                            @if ($paystack['secret_configured'])
                                <span class="badge bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Configured</span>
                            @else
                                <span class="badge bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20">Missing</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-ink-500">Public key</dt>
                        <dd class="font-mono text-xs">{{ $paystack['public_key_masked'] ?? 'Not set' }}</dd>
                    </div>
                    @if ($paystack['public_key_masked'])
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-ink-500">Mode</dt>
                            <dd><span class="badge {{ $paystack['live'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}">{{ $paystack['live'] ? 'Live' : 'Test' }}</span></dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-ink-500">Split / subaccount</dt>
                        <dd class="mt-1 font-mono text-xs">{{ $paystack['settlement'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Webhook URL</dt>
                        <dd class="mt-1 flex items-center gap-2" x-data="{ copied: false }">
                            <code class="block flex-1 overflow-x-auto rounded-lg bg-ink-50 px-3 py-2 text-xs whitespace-nowrap">{{ $paystack['webhook_url'] }}</code>
                            <button type="button" class="btn btn-outline btn-sm" @click="navigator.clipboard.writeText(@js($paystack['webhook_url'])); copied = true; setTimeout(() => copied = false, 2000)" x-text="copied ? 'Copied' : 'Copy'">Copy</button>
                        </dd>
                    </div>
                </dl>
                <p class="hint">Paste the webhook URL into Paystack Dashboard → Settings → API Keys &amp; Webhooks. Keys are set in the server's <code>.env</code> file (PAYSTACK_PUBLIC_KEY, PAYSTACK_SECRET_KEY) and are never shown here.</p>
            </section>

            <button class="btn btn-gold btn-lg w-full">Save settings</button>
        </div>
    </form>
</x-layouts.admin>
