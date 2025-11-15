<?php

namespace Blocs\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class Install extends Command
{
    /**
     * コンソールコマンドの署名。
     *
     * @var string
     */
    protected $signature = 'blocs:install';

    /**
     * コンソールコマンドの説明。
     *
     * @var string
     */
    protected $description = 'Install blocs/admin package';

    /**
     * コマンド全体の実行フローを制御する。
     */
    public function handle(): void
    {
        // 言語設定を同期して翻訳リソースを最新化
        $this->synchronizeLanguageFiles(base_path('vendor/blocs/admin/lang'));

        // メニュー設定を統合して最新の状態に反映
        $this->synchronizeMenuConfiguration(base_path('vendor/blocs/admin/config/menu.json'));

        // 空の favicon が残っていれば削除して不要ファイルを排除
        $faviconPath = public_path('favicon.ico');
        if (file_exists($faviconPath) && ! filesize($faviconPath)) {
            unlink($faviconPath);
        }

        // 必要ファイルを publish してアセットを公開
        Artisan::call('vendor:publish', ['--provider' => 'Blocs\AdminServiceProvider']);

        // 初期ユーザーを登録してすぐにログインできる状態を用意
        Artisan::call('migrate');
        Artisan::call('db:seed', ['--class' => 'AdminSeeder']);

        Artisan::call('route:cache');

        $this->info('Admin has been installed successfully.');
        $this->info('Login URL is '.route('login').'.');
        $this->info('Initial ID/Pass is admin/admin.');
    }

    private function synchronizeLanguageFiles(string $sourceLanguageDirectory): void
    {
        if (! is_dir($sourceLanguageDirectory)) {
            return;
        }

        $targetLanguageDirectory = resource_path('lang');
        if (! is_dir($targetLanguageDirectory)) {
            mkdir($targetLanguageDirectory, 0777, true);
            chmod($targetLanguageDirectory, 0777);
        }

        foreach (scandir($sourceLanguageDirectory) as $fileName) {
            if ($this->isSkippableLanguageFile($fileName)) {
                continue;
            }

            $sourceFile = $sourceLanguageDirectory.'/'.$fileName;
            $targetFile = $targetLanguageDirectory.'/'.$fileName;

            if (! is_file($targetFile)) {
                copy($sourceFile, $targetFile);
                chmod($targetFile, 0666);

                continue;
            }

            $existingTranslations = json_decode(file_get_contents($targetFile), true) ?? [];
            $newTranslations = json_decode(file_get_contents($sourceFile), true) ?? [];

            $mergedTranslations = array_merge($existingTranslations, $newTranslations);
            ksort($mergedTranslations);

            file_put_contents(
                $targetFile,
                json_encode($mergedTranslations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n"
            );
            chmod($targetFile, 0666);
        }
    }

    private function isSkippableLanguageFile(string $fileName): bool
    {
        // 隠しファイルのうち、必要なファイルを除外
        return substr($fileName, 0, 1) === '.' && $fileName !== '.gitkeep' && $fileName !== '.htaccess';
    }

    private function synchronizeMenuConfiguration(string $menuConfigPath): void
    {
        if (! file_exists($menuConfigPath)) {
            return;
        }

        $menuDefinitions = json_decode(file_get_contents($menuConfigPath), true) ?? [];
        if (empty($menuDefinitions)) {
            return;
        }

        $currentMenu = config('menu') ?? [];

        foreach ($menuDefinitions as $menuName => $definitions) {
            if (empty($currentMenu[$menuName])) {
                $currentMenu[$menuName] = $definitions;

                continue;
            }

            $existingNames = array_column($currentMenu[$menuName], 'name');

            foreach ($definitions as $definition) {
                if (! in_array($definition['name'], $existingNames, true)) {
                    $currentMenu[$menuName][] = $definition;
                }
            }
        }

        $menuFilePath = config_path('menu.php');
        $code = "<?php\n\nreturn ".var_export($currentMenu, true).";\n";
        file_put_contents($menuFilePath, $code);
        chmod($menuFilePath, 0666);
    }
}
