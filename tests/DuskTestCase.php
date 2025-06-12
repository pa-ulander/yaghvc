<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication, DatabaseMigrations;

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            // Start ChromeDriver if not using Laravel Sail
            static::startChromeDriver();
        }
    }

    /**
     * Setup the test environment.
     * 
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Explicitly set the database connection to mysql_testing for Dusk tests
        $this->app['config']->set('database.default', 'mysql_testing');

        // Clear configuration cache to ensure fresh environment variables are used
        $this->artisan('config:clear');
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver()
    {
        $server = 'http://selenium:4444';

        $options = (new ChromeOptions)->addArguments([
            '--disable-gpu',
            '--headless=new',
            '--window-size=1920,1080',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-extensions',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ]);

        return RemoteWebDriver::create(
            $server,
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY,
                $options
            )
        );
    }

    /**
     * Determine whether the Dusk command has disabled headless mode.
     */
    protected function hasHeadlessDisabled(): bool
    {
        return isset($_SERVER['DUSK_HEADLESS_DISABLED']) ||
            isset($_ENV['DUSK_HEADLESS_DISABLED']);
    }

    /**
     * Determine if the browser window should start maximized.
     */
    protected function shouldStartMaximized(): bool
    {
        return isset($_SERVER['DUSK_START_MAXIMIZED']) ||
            isset($_ENV['DUSK_START_MAXIMIZED']);
    }

    protected function captureFailuresFor($browsers)
    {
        parent::captureFailuresFor($browsers);

        $browsers->each(function ($browser, $key) {
            $browser->storeSource('failure-' . $this->getCallerName() . '-' . $key);
            $browser->storeConsoleLog('failure-' . $this->getCallerName() . '-' . $key);
        });
    }
}
