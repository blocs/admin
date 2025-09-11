<?php

namespace Blocs;

use Illuminate\Support\Facades\Process;

trait VectorStoreTrait
{
    private static function updateDocument($targetData, $name)
    {
        Process::input(json_encode([$targetData], JSON_UNESCAPED_UNICODE))->run([config('openai.python_path'), base_path('vendor/blocs/admin/python/update.py'), database_path(), $name]);
    }

    private static function updateChunkDocument($targetData, $name, $chunkItem)
    {
        Process::input(json_encode(self::chunkDocument([$targetData], $chunkItem), JSON_UNESCAPED_UNICODE))->run([config('openai.python_path'), base_path('vendor/blocs/admin/python/update.py'), database_path(), $name]);
    }

    private static function deleteDocument($targetId, $name)
    {
        Process::input(json_encode([$targetId]))->run([config('openai.python_path'), base_path('vendor/blocs/admin/python/delete.py'), database_path(), $name]);
    }

    private function similarDocument($targetData, $name, $scoreThreshold = 0.6, $docsLimit = 5)
    {
        $process = Process::input(json_encode($targetData, JSON_UNESCAPED_UNICODE))->run([config('openai.python_path'), base_path('vendor/blocs/admin/python/similar.py'), database_path(), $name, $scoreThreshold, $docsLimit]);
        $jsonContent = $process->output();

        if (empty(trim($jsonContent))) {
            return [];
        }

        $result = [];
        $jsonContent = explode("\n", $jsonContent);
        foreach ($jsonContent as $data) {
            $data = json_decode($data, true);
            if (empty($data)) {
                continue;
            }
            $result[] = $data;
        }

        return $result;
    }

    private static function chunkDocument($targetData, $key, $chunkSize = 1000, $chunkOverlap = 0)
    {
        $chunkedDocuments = [];
        foreach ($targetData as $data) {
            // チャンクして追加
            $contents = self::chunkString($data[$key], $chunkSize, $chunkOverlap);
            foreach ($contents as $content) {
                $data[$key] = $content;
                $chunkedDocuments[] = $data;
            }
        }

        return $chunkedDocuments;
    }

    private static function chunkString($string, $chunkSize = 1000, $chunkOverlap = 0)
    {
        $process = Process::input(json_encode($string, JSON_UNESCAPED_UNICODE))->run([config('openai.python_path'), base_path('vendor/blocs/admin/python/chunk.py'), $chunkSize, $chunkOverlap]);
        $jsonContent = $process->output();

        $data = json_decode($jsonContent, true);
        if (empty($data)) {
            return [];
        }

        return $data;
    }
}
