<?php

namespace Blocs;

use Illuminate\Process\ProcessResult;
use Illuminate\Support\Facades\Process;

trait VectorStoreTrait
{
    private static function updateDocument(string $collectionName, array $targetData): void
    {
        $payload = json_encode([$targetData], JSON_UNESCAPED_UNICODE);

        self::runPythonScript('update.py', [database_path(), $collectionName], $payload);
    }

    private static function updateChunkDocument(string $collectionName, array $targetData, string $chunkItem, int $chunkSize = 1000, int $chunkOverlap = 0): void
    {
        $chunkedDocuments = self::chunkDocument([$targetData], $chunkItem, $chunkSize, $chunkOverlap);
        $payload = json_encode($chunkedDocuments, JSON_UNESCAPED_UNICODE);

        self::runPythonScript('update.py', [database_path(), $collectionName], $payload);
    }

    private static function deleteDocument(string $collectionName, array|string $targetIds = []): void
    {
        // 全件削除の指定は空配列を渡す
        if (empty($targetIds)) {
            $targetIds = [];
        } elseif (! is_array($targetIds)) {
            $targetIds = [$targetIds];
        }

        $payload = json_encode($targetIds, JSON_UNESCAPED_UNICODE);

        self::runPythonScript('delete.py', [database_path(), $collectionName], $payload)->output();
    }

    private function similarDocument(string $collectionName, array $targetData, int $docsLimit = 5, float $scoreThreshold = 0.6): array
    {
        $payload = json_encode($targetData, JSON_UNESCAPED_UNICODE);
        $processResult = self::runPythonScript('similar.py', [database_path(), $collectionName, $scoreThreshold, $docsLimit], $payload);
        $jsonContent = trim($processResult->output());

        if ($jsonContent === '') {
            return [];
        }

        $documents = array_filter(explode("\n", $jsonContent));

        $result = [];
        foreach ($documents as $document) {
            $decodedDocument = json_decode($document, true);

            if (! empty($decodedDocument)) {
                $result[] = $decodedDocument;
            }
        }

        return $result;
    }

    private static function chunkDocument(array $targetData, string $chunkItem, int $chunkSize = 1000, int $chunkOverlap = 0): array
    {
        $chunkedDocuments = [];
        foreach ($targetData as $document) {
            // チャンクした内容を順次追加
            $contents = self::chunkString($document[$chunkItem], $chunkSize, $chunkOverlap);
            foreach ($contents as $content) {
                $chunkedDocument = $document;
                $chunkedDocument[$chunkItem] = $content;
                $chunkedDocuments[] = $chunkedDocument;
            }
        }

        return $chunkedDocuments;
    }

    private static function chunkString(string $string, int $chunkSize = 1000, int $chunkOverlap = 0): array
    {
        $payload = json_encode($string, JSON_UNESCAPED_UNICODE);
        $processResult = self::runPythonScript('chunk.py', [$chunkSize, $chunkOverlap], $payload);
        $data = json_decode($processResult->output(), true);

        return is_array($data) ? $data : [];
    }

    private static function runPythonScript(string $script, array $arguments, string $input = ''): ProcessResult
    {
        $command = array_merge(
            [
                config('openai.python_path'),
                base_path("vendor/blocs/admin/python/{$script}"),
            ],
            $arguments,
        );

        return Process::input($input)->run($command);
    }
}
