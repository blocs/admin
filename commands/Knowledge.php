<?php

namespace Blocs\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class Knowledge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blocs:knowledge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy cursor rules';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sourcePath = base_path('vendor/blocs/admin/resources/views/.cursor');
        $destinationPath = resource_path('views/.cursor');

        if (File::exists($sourcePath)) {
            if (File::exists($destinationPath)) {
                File::deleteDirectory($destinationPath);
            }
            File::copyDirectory($sourcePath, $destinationPath);
            $this->info($sourcePath);
        }

        $sourcePath = base_path('vendor/blocs/admin/app/Http/Controllers/.cursor');
        $destinationPath = app_path('Http/Controllers/.cursor');

        if (File::exists($sourcePath)) {
            if (File::exists($destinationPath)) {
                File::deleteDirectory($destinationPath);
            }
            File::copyDirectory($sourcePath, $destinationPath);
            $this->info($sourcePath);
        }
    }
}
