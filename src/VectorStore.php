<?php

namespace Blocs;

use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;

class VectorStore
{
    /**
     * @var array<string, array{id: string, name: string}>
     */
    private static array $collections = [];

    /**
     * ドキュメントを取得
     *
     * @param  array<string>|string  $docIds
     * @return array<string, mixed>
     */
    public static function get(string $collectionName, array|string $docIds): array
    {
        $collectionId = self::ensureCollection($collectionName);

        $normalizedDocIds = is_array($docIds) ? $docIds : [$docIds];

        $response = Http::post(
            self::getApiUrl("/collections/{$collectionId}/get"),
            ['ids' => $normalizedDocIds]
        );

        if (! $response->successful()) {
            return [];
        }

        $data = $response->json();
        if (! isset($data['documents'][0])) {
            return [];
        }

        $decoded = json_decode($data['documents'][0], true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * ドキュメントを追加または更新
     *
     * @param  array<string, mixed>  $targetData
     */
    public static function upsert(string $collectionName, array $targetData): bool
    {
        if (empty($targetData['id'])) {
            return false;
        }

        $collectionId = self::ensureCollection($collectionName);
        $docId = strval($targetData['id']);

        $dataToStore = $targetData;

        $pageContent = json_encode($dataToStore, JSON_UNESCAPED_UNICODE);
        $embedding = self::getEmbedding($pageContent);

        $response = Http::post(
            self::getApiUrl("/collections/{$collectionId}/upsert"),
            [
                'ids' => [$docId],
                'documents' => [$pageContent],
                'embeddings' => [$embedding],
            ]
        );

        return $response->successful();
    }

    /**
     * コレクション内のドキュメント数を取得
     */
    public static function count(string $collectionName): int
    {
        $collectionId = self::ensureCollection($collectionName);

        $response = Http::get(self::getApiUrl("/collections/{$collectionId}/count"));

        if (! $response->successful()) {
            return 0;
        }

        $result = $response->json();

        return is_int($result) ? $result : 0;
    }

    /**
     * ドキュメントを削除
     *
     * @param  array<string>|string  $docIds
     */
    public static function delete(string $collectionName, array|string $docIds = []): void
    {
        // 全件削除の指定は空配列を渡す
        if (empty($docIds)) {
            Http::delete(self::getApiUrl("/collections/{$collectionName}"));
            unset(self::$collections[$collectionName]);

            return;
        }

        $collectionId = self::ensureCollection($collectionName);
        $normalizedDocIds = is_array($docIds) ? $docIds : [$docIds];

        // 一度のリクエストで複数のIDを削除
        Http::post(
            self::getApiUrl("/collections/{$collectionId}/delete"),
            ['ids' => $normalizedDocIds]
        );
    }

    /**
     * 類似ドキュメントを検索
     *
     * @param  array<string, mixed>|string  $targetData
     * @return array<int, array<string, mixed>>
     */
    public static function similar(string $collectionName, array|string $targetData, int $docsLimit = 5, float $scoreThreshold = 0.6): array
    {
        $collectionId = self::ensureCollection($collectionName);

        $pageContent = is_array($targetData) ? json_encode($targetData, JSON_UNESCAPED_UNICODE) : $targetData;
        $embedding = self::getEmbedding($pageContent);

        $response = Http::post(
            self::getApiUrl("/collections/{$collectionId}/query"),
            [
                'query_embeddings' => [$embedding],
                'n_results' => $docsLimit,
            ]
        );

        if (! $response->successful()) {
            return [];
        }

        $data = $response->json();
        if (empty($data['ids'][0]) || ! is_array($data['ids'][0])) {
            return [];
        }

        $results = [];
        $ids = $data['ids'][0];
        $distances = $data['distances'][0] ?? [];
        $documents = $data['documents'][0] ?? [];

        foreach ($ids as $index => $id) {
            $distance = $distances[$index] ?? null;

            // 距離が null の場合、または閾値以上の場合はスキップ
            if ($distance === null || $distance >= $scoreThreshold) {
                continue;
            }

            $document = $documents[$index] ?? null;
            if ($document === null) {
                continue;
            }

            $content = json_decode($document, true);
            if (! is_array($content)) {
                continue;
            }

            $content['distance'] = $distance;
            $results[] = $content;
        }

        return $results;
    }

    /**
     * API URLを生成
     */
    private static function getApiUrl(string $path): string
    {
        $host = config('chromadb.host', 'http://localhost');
        $port = config('chromadb.port', 8000);
        $baseUrl = rtrim($host, '/').':'.$port;

        $tenant = config('chromadb.tenant', 'default_tenant');
        $database = config('chromadb.database', 'default_database');

        return $baseUrl."/api/v2/tenants/{$tenant}/databases/{$database}{$path}";
    }

    /**
     * コレクションの存在確認と作成
     *
     * @return string コレクションID
     */
    private static function ensureCollection(string $collectionName): string
    {
        // キャッシュから取得
        if (isset(self::$collections[$collectionName])) {
            return self::$collections[$collectionName]['id'];
        }

        // コレクションの存在確認と作成
        $response = Http::post(
            self::getApiUrl('/collections'),
            [
                'name' => $collectionName,
                'get_or_create' => true,
            ]
        );

        if (! $response->successful()) {
            throw new \RuntimeException("Failed to ensure collection: {$collectionName}");
        }

        $collection = $response->json();
        if (! is_array($collection) || empty($collection['id'])) {
            throw new \RuntimeException("Invalid collection response for: {$collectionName}");
        }

        self::$collections[$collectionName] = $collection;

        return $collection['id'];
    }

    /**
     * テキストから埋め込みベクトルを取得
     *
     * @return array<float>
     */
    private static function getEmbedding(string $text): array
    {
        $response = OpenAI::embeddings()->create([
            'model' => config('chromadb.embedding_model', 'text-embedding-ada-002'),
            'input' => $text,
        ]);

        return $response->embeddings[0]->embedding;
    }
}
