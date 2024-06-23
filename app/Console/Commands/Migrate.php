<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Migrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blocs:migrate {path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $migrationPath = $this->argument('path').'_table.php';
        if (!($migrations = glob(database_path('migrations/*_'.$migrationPath)))) {
            return;
        }

        $migrationPath = $migrations[0];
        echo 'Migrate "'.basename($migrationPath).'" ? ';

        if ('y' !== trim(strtolower(fgets(STDIN)))) {
            return;
        }

        // テーブル再作成
        \Artisan::call('migrate:refresh', ['--path' => 'database/migrations/'.basename($migrationPath)]);
    }
}
