<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\Page;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;

/** Starter CMS content. All of it is editable from the admin panel. */
class ContentSeeder extends Seeder
{
    public function run(SettingsService $settings): void
    {
        $pages = [
            'about' => ['About the awards', <<<'HTML'
<p>We celebrate the creators, storytellers and entrepreneurs who shape culture online. Each season, fans nominate and vote for the people whose work has entertained, informed and inspired them.</p>
<h2>How winners are chosen</h2>
<p>Nominees are shortlisted by our editorial panel. Public votes, both free and paid, determine the rankings in each category. Every vote is recorded in an auditable ledger, and suspicious activity is reviewed by our team before results are confirmed.</p>
<h2>Our promise</h2>
<ul><li>Transparent, auditable voting</li><li>Secure payments processed by Paystack</li><li>A celebration that puts creators first</li></ul>
HTML],
            'terms' => ['Terms of use', <<<'HTML'
<p>By using this website you agree to these terms. Please read them carefully.</p>
<h2>Voting</h2>
<p>Each registered voter may cast free votes according to the rules published for each event. Paid votes are credited only after payment is confirmed by our payment provider. We may invalidate votes that we reasonably believe were obtained through fraud, automation or abuse.</p>
<h2>Accounts</h2>
<p>You are responsible for keeping your login details secure. Accounts created to manipulate results may be suspended.</p>
<h2>Changes</h2>
<p>We may update these terms from time to time. Continued use of the site means you accept the updated terms.</p>
HTML],
            'privacy' => ['Privacy policy', <<<'HTML'
<p>We collect only the information needed to run the awards: your name, email address, optional phone number, and records of your votes and payments.</p>
<h2>How we use your data</h2>
<ul><li>To create and secure your account</li><li>To record votes and send confirmations and receipts</li><li>To detect and prevent voting fraud (including IP address and device information)</li></ul>
<h2>Payments</h2>
<p>Card and bank details are handled by Paystack. We never see or store your full card details.</p>
<h2>Your rights</h2>
<p>You may request a copy or deletion of your personal data by contacting us. Some records must be retained for financial and audit purposes.</p>
HTML],
            'refund-policy' => ['Refund policy', <<<'HTML'
<p>Paid votes are a contribution towards the awards and are generally non-refundable once credited.</p>
<h2>When we refund</h2>
<ul><li>You were charged but your votes were not credited and we cannot credit them</li><li>You were charged more than once for the same order</li><li>An event is cancelled before voting closes</li></ul>
<p>Table bookings may be refunded up to 14 days before the event. To request a refund, contact us with your order or booking reference.</p>
HTML],
        ];

        foreach ($pages as $slug => [$title, $content]) {
            $page = Page::firstOrNew(['slug' => $slug]);

            if (! $page->exists) {
                $page->fill(['title' => $title, 'content' => $content, 'status' => 'published', 'meta_description' => strip_tags(explode('</p>', $content)[0])]);
                $page->forceFill(['is_system' => true])->save();
            }
        }

        $faqs = [
            ['Voting', 'How do I vote for free?', 'Create an account, verify your email address, then open any category and tap "Free vote" on your favourite nominee. The number of free votes you get is shown on each event.'],
            ['Voting', 'How do paid votes work?', 'Tap "Buy votes", choose a package, confirm the amount, and pay securely with Paystack. Your votes are credited automatically as soon as the payment is confirmed.'],
            ['Voting', 'Can I vote for more than one nominee?', 'Yes. You can buy votes for as many nominees as you like. Free vote limits are set per event and shown on the voting page.'],
            ['Payments', 'Is payment secure?', 'Yes. Payments are processed by Paystack. We never see or store your card details.'],
            ['Payments', 'I paid but my votes are not showing', 'Most payments are confirmed within seconds. If your votes do not appear within 30 minutes, contact us with your order reference and we will investigate.'],
            ['Payments', 'Can I get a receipt?', 'A receipt is emailed to you after every successful payment and is also available from your dashboard.'],
            ['Results', 'When are winners announced?', 'Winners are announced live at the awards night. Depending on the event settings, live rankings may be visible on the leaderboard during voting.'],
            ['Results', 'How do you prevent cheating?', 'Every vote is recorded with its source. Automated checks flag unusual activity for our team to review, and fraudulent votes are removed from the totals before results are confirmed.'],
        ];

        foreach ($faqs as $i => [$category, $question, $answer]) {
            Faq::firstOrCreate(['question' => $question], ['answer' => $answer, 'category' => $category, 'sort_order' => $i, 'status' => 'active']);
        }

        $settings->set([
            'home_about' => "Every year we shine a light on the creators who keep us laughing, learning and inspired. Nominations are curated by our panel — the rest is up to you.\n\nCast your free vote, rally your community, and join us on awards night to celebrate the winners.",
        ]);
    }
}
