<?php

namespace Tests;

use App\Models\AwardEvent;
use App\Models\Category;
use App\Models\Nominee;
use App\Models\VotingPackage;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected const PAYSTACK_SECRET = 'sk_test_secret_for_tests';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.paystack.secret_key' => self::PAYSTACK_SECRET,
            'services.paystack.public_key' => 'pk_test_public',
            'services.paystack.base_url' => 'https://api.paystack.co',
            'services.paystack.callback_url' => null,
        ]);
    }

    /**
     * An open event with one category, one nominee and one ₦1,000 / 10-vote package.
     *
     * @return array{event: AwardEvent, category: Category, nominee: Nominee, package: VotingPackage}
     */
    protected function votingSetup(array $eventSettings = [], array $eventAttributes = []): array
    {
        $event = AwardEvent::factory()->withSettings($eventSettings)->create($eventAttributes);
        $category = Category::factory()->for($event, 'awardEvent')->create();
        $nominee = Nominee::factory()->for($category)->create();
        $package = VotingPackage::factory()->for($event, 'awardEvent')->create(['amount' => 1000, 'votes' => 10]);

        return compact('event', 'category', 'nominee', 'package');
    }

    /** Sign a webhook body exactly as Paystack does. */
    protected function paystackSignature(string $body): string
    {
        return hash_hmac('sha512', $body, self::PAYSTACK_SECRET);
    }
}
