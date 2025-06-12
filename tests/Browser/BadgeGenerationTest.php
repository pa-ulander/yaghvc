<?php

namespace Tests\Browser;

use App\Models\ProfileViews;
use App\Repositories\ProfileViewsRepository;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use PHPUnit\Framework\Attributes\Test;

class BadgeGenerationTest extends DuskTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Clear any existing test data
        ProfileViews::where('username', 'like', 'dusk-test%')->delete();
    }

    #[Test]
    public function it_displays_basic_visitor_badge()
    {
        $username = 'dusk-test';

        // Create a profile view with a specific count
        ProfileViews::factory()->create([
            'username' => $username,
            'visit_count' => 42
        ]);

        $this->browse(function (Browser $browser) use ($username) {
            $browser->visit("http://yagvc-app/?username={$username}")
                ->assertSee('Visits')
                ->assertSee('42')
                ->assertSourceHas('<svg');
        });
    }

    #[Test]
    public function it_increments_visitor_count_on_visit()
    {
        $username = 'test-user-increment';

        // Create a profile view with initial count
        $profileView = ProfileViews::factory()->create([
            'username' => $username,
            'visit_count' => 10
        ]);

        // First visit should show the initial count
        $this->browse(function (Browser $browser) use ($username) {
            $browser->visit("http://yagvc-app/?username={$username}")
                ->assertSee('Profile Views')
                ->assertSee('10');

            // Second visit should increment the count
            $browser->visit("http://yagvc-app/?username={$username}")
                ->assertSee('Profile Views')
                ->assertSee('11');
        });
    }

    #[Test]
    public function it_applies_different_style_customizations()
    {
        $username = 'test-user-styles';

        // Create a profile view
        ProfileViews::factory()->create([
            'username' => $username,
            'visit_count' => 100
        ]);

        $this->browse(function (Browser $browser) use ($username) {
            // Test flat style
            $browser->visit("http://yagvc-app/?username={$username}&style=flat")
                ->assertSourceHas('flat.svg');

            // Test flat-square style
            $browser->visit("http://yagvc-app/?username={$username}&style=flat-square")
                ->assertSourceHas('flat-square.svg');

            // Test for-the-badge style
            $browser->visit("http://yagvc-app/?username={$username}&style=for-the-badge")
                ->assertSourceHas('for-the-badge.svg');

            // Test plastic style
            $browser->visit("http://yagvc-app/?username={$username}&style=plastic")
                ->assertSourceHas('plastic.svg');
        });
    }

    #[Test]
    public function it_applies_custom_label_and_color()
    {
        $username = 'test-user-customization';

        // Create a profile view
        ProfileViews::factory()->create([
            'username' => $username,
            'visit_count' => 50
        ]);

        $this->browse(function (Browser $browser) use ($username) {
            $browser->visit("http://yagvc-app/?username={$username}&label=Visitors&color=blue")
                ->assertSee('Visitors')
                ->assertSourceHas('blue')
                ->assertSourceHas('50');
        });
    }

    #[Test]
    public function it_abbreviates_large_numbers_when_requested()
    {
        $username = 'test-user-abbreviation';

        // Create a profile view with a large count
        ProfileViews::factory()->create([
            'username' => $username,
            'visit_count' => 1500
        ]);

        $this->browse(function (Browser $browser) use ($username) {
            // Without abbreviation
            $browser->visit("http://yagvc-app/?username={$username}")
                ->assertSee('1500');

            // With abbreviation
            $browser->visit("http://yagvc-app/?username={$username}&abbreviated=true")
                ->assertSee('1.5k');
        });
    }
}
