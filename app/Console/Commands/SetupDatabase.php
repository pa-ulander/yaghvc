<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:setup-database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup or recreate database. Imports from "docker/mysql/db.sql".';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            if (! $this->confirm('This command will (re)create and (re)set the entire database. Do you wish to continue?', true)) {
                $this->info('Process terminated by user');

                return;
            }
            ini_set('memory_limit', '-1');
            DB::unprepared(file_get_contents('docker/mysql/init.sql'));
            $this->info('Database was (re)created and provided successfully from "docker/mysql/init.sql"');
        } catch (Exception $e) {
            $this->error('Error: '.$e->getMessage());
        }
    }
}
