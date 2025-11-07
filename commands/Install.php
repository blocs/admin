<?php

namespace Blocs\Commands;

use Illuminate\Console\Command;

class Install extends Command
{
    protected string $baseDir;

    public function handle(): void
    {
        // 言語設定を同期して翻訳リソースを最新化
        $this->synchronizeLanguageFiles($this->baseDir.'/lang');

        // メニュー設定を統合して最新の状態に反映
        $this->synchronizeMenuConfiguration($this->baseDir.'/config/menu.json');
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
