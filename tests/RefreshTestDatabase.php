<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Symfony\Component\Finder\Finder;

trait RefreshTestDatabase
{
    use RefreshDatabase;

    /**
     * Path to checksum file
     */
    private $checksumFile = '.phpunit.database.checksum';

    /**
     * Migrate fresh only if needed
     *
     * @return void
     */
    protected function refreshTestDatabase()
    {
        config(['app.env' => 'testing']);
        if (! RefreshDatabaseState::$migrated) {

            if (! $this->identicalChecksum()) {
                dump('-- Refreshing TestDatabase --');
                $this->artisan('migrate:fresh', $this->migrateFreshUsing());
                $this->seed();
                $this->createChecksum();
            }

            $this->app[Kernel::class]->setArtisan(null);

            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }

    /**
     * Set checksum of the current migration files
     */
    private function calculateChecksum(): string
    {
        $files = Finder::create()
            ->files()
            ->exclude([
                'factories',
                'seeders',
            ])
            ->in(database_path())
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->getIterator();

        $files = array_keys(iterator_to_array($files));

        $checksum = collect($files)->map(function ($file) {
            return md5_file($file);
        })->implode('');

        return md5($checksum);
    }

    /**
     * Filepath to store the checksum
     */
    private function checksumFilePath(): string
    {
        return base_path($this->checksumFile);
    }

    /**
     * Creates the checksum file
     */
    private function createChecksum(): void
    {
        file_put_contents($this->checksumFilePath(), $this->calculateChecksum());
    }

    /**
     * Get checksum file content
     *
     * @return bool|string
     */
    private function checksumFileContents()
    {
        return file_get_contents($this->checksumFilePath());
    }

    /**
     * Check if checksum exists
     */
    private function isChecksumExists(): bool
    {
        return file_exists($this->checksumFilePath());
    }

    /**
     * Check if checksum of current database migration files
     * are identical to the one already stored
     */
    private function identicalChecksum(): bool
    {
        if (! $this->isChecksumExists()) {
            return false;
        }

        return $this->checksumFileContents() === $this->calculateChecksum();
    }
}
