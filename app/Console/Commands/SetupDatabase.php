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
     */
    public function handle(): bool
    {
        try {
            if (! $this->confirm(question: 'This command will (re)create and (re)set the entire database. Do you wish to continue?', default: true)) {
                $this->info(string: 'Process terminated by user');
                return false;
            }
            ini_set(option: 'memory_limit', value: '-1');
            DB::unprepared(query: (string)file_get_contents(filename: 'docker/mysql/init.sql'));
            $this->info(string: 'Database was (re)created and provided successfully from "docker/mysql/init.sql"');
            return true;
        } catch (Exception $e) {
            $this->error(string: 'Error: '.$e->getMessage());
            return false;
        }
    }
}
