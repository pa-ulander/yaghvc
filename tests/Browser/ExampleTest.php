<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ExampleTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * A basic browser test example.
     */
    public function test_basic_example(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('http://localhost/?username=dusk-test')
                ->pause(2000) // Add a pause to ensure page loads fully
                ->waitForTextIn('body', 'Visits', 10)
                ->assertSee('Visits');
        });
    }
}
