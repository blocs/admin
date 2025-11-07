<?php

namespace Blocs\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class Knowledge extends Command
{
    /**
     * コンソールコマンドの署名。
     *
     * @var string
     */
    protected $signature = 'blocs:knowledge';

    /**
     * コンソールコマンドの説明。
     *
     * @var string
     */
    protected $description = 'Copy cursor rules';

    /**
     * カーソルルールを所定のディレクトリへコピーする。
     */
    public function handle(): void
    {
        $directoryPairs = [
            base_path('vendor/blocs/admin/resources/views/.cursor') => resource_path('views/.cursor'),
            base_path('vendor/blocs/admin/app/Http/Controllers/.cursor') => app_path('Http/Controllers/.cursor'),
        ];

        foreach ($directoryPairs as $sourcePath => $destinationPath) {
            $this->copyCursorDirectory($sourcePath, $destinationPath);
        }
    }

    private function copyCursorDirectory(string $sourcePath, string $destinationPath): void
    {
        if (! File::exists($sourcePath)) {
            return;
        }

        if (File::exists($destinationPath)) {
            File::deleteDirectory($destinationPath);
        }

        File::copyDirectory($sourcePath, $destinationPath);
        $this->info($sourcePath);
    }
}
