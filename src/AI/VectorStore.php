<?php

namespace Blocs\AI;

use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;

class VectorStore
{
    /**
     * デフォルトのスコア閾値
     */
    private const DEFAULT_SCORE_THRESHOLD = 0.6;

    /**
     * デフォルトの検索結果数
     */
    private const DEFAULT_DOCS_LIMIT = 5;

    /**
     * デフォルトのベクトルサイズ
     */
    private const DEFAULT_VECTOR_SIZE = 1536;

    /**
     * 距離計算方法
     */
    private const DISTANCE_METHOD = 'Cosine';

    /**
     * @var array<string, bool>
     */
    private static array $collections = [];

    /**
     * ドキュメントを取得
     *
     * @param  array<string>|string  $docIds
     * @param  array<string, mixed>|null  $filter
     * @return array<string, mixed>|array<int, array<string, mixed>>
     */
    public static function get(string $collectionName, array|string $docIds, ?array $filter = null): array
    {
        self::ensureCollection($collectionName);

        $normalizedDocIds = self::normalizeDocIds($docIds);

        $requestData = [
            'ids' => $normalizedDocIds,
            'with_payload' => true,
            'with_vectors' => false,
        ];

        if ($filter !== null) {
            $requestData['filter'] = $filter;
        }

        $response = self::makeRequest('post', "/collections/{$collectionName}/points", $requestData);

        if (! $response) {
            return [];
        }

        $payloads = self::extractPayloads($response);

        return is_array($docIds) ? $payloads : ($payloads[0] ?? []);
    }

    /**
     * ドキュメントを追加または更新
     *
     * @param  array<string, mixed>  $targetData
     */
    public static function upsert(string $collectionName, string $docId, array $targetData): bool
    {
        self::ensureCollection($collectionName);

        $docId = self::hashDocId($docId);
        $embedding = self::getEmbeddingFromData($targetData);

        $response = self::makeRequest('put', "/collections/{$collectionName}/points", [
            'points' => [
                [
                    'id' => $docId,
                    'payload' => $targetData,
                    'vector' => $embedding,
                ],
            ],
        ]);

        return $response !== null;
    }

    /**
     * コレクション内のドキュメント数を取得
     */
    public static function count(string $collectionName): int
    {
        self::ensureCollection($collectionName);

        $response = self::makeRequest('post', "/collections/{$collectionName}/points/count", [
            'exact' => true,
        ]);

        if (! $response) {
            return 0;
        }

        return $response['result']['count'] ?? 0;
    }

    /**
     * コレクション内の全てのIDを取得
     *
     * @return array<string>
     */
    public static function getAllIds(string $collectionName): array
    {
        self::ensureCollection($collectionName);

        $allIds = [];
        $offset = null;
        $limit = 100;

        do {
            $requestData = [
                'limit' => $limit,
                'with_payload' => false,
                'with_vectors' => false,
            ];

            if ($offset !== null) {
                $requestData['offset'] = $offset;
            }

            $response = self::makeRequest('post', "/collections/{$collectionName}/points/scroll", $requestData);

            if (! $response || ! isset($response['result']['points'])) {
                break;
            }

            foreach ($response['result']['points'] as $point) {
                if (isset($point['id'])) {
                    $allIds[] = (string) $point['id'];
                }
            }

            $offset = $response['result']['next_page_offset'] ?? null;
        } while ($offset !== null);

        return $allIds;
    }

    /**
     * ドキュメントを削除
     *
     * @param  array<string>|string  $docIds
     * @param  array<string, mixed>|null  $filter
     */
    public static function delete(string $collectionName, array|string $docIds = [], ?array $filter = null): void
    {
        if (empty($docIds)) {
            self::deleteCollection($collectionName);

            return;
        }

        self::ensureCollection($collectionName);
        $normalizedDocIds = self::normalizeDocIds($docIds);

        $requestData = [
            'points' => $normalizedDocIds,
        ];

        if ($filter !== null) {
            $requestData['filter'] = $filter;
        }

        self::makeRequest('post', "/collections/{$collectionName}/points/delete", $requestData);
    }

    /**
     * 類似ドキュメントを検索
     *
     * @param  array<string, mixed>|string  $targetData
     * @param  array<string, mixed>|null  $filter
     * @return array<int, array<string, mixed>>
     */
    public static function similar(
        string $collectionName,
        array|string $targetData,
        int $docsLimit = self::DEFAULT_DOCS_LIMIT,
        float $scoreThreshold = self::DEFAULT_SCORE_THRESHOLD,
        ?array $filter = null
    ): array {
        self::ensureCollection($collectionName);

        $embedding = self::getEmbeddingFromData($targetData);

        $requestData = [
            'query' => $embedding,
            'limit' => $docsLimit,
            'with_payload' => true,
            'with_vectors' => false,
            'score_threshold' => $scoreThreshold,
        ];

        if ($filter !== null) {
            $requestData['filter'] = $filter;
        }

        $response = self::makeRequest('post', "/collections/{$collectionName}/points/query", $requestData);

        if (! $response) {
            return [];
        }

        return self::extractSimilarResults($response);
    }

    /**
     * API URLを生成
     */
    private static function getApiUrl(string $path): string
    {
        $host = config('qdrant.host', 'http://localhost');
        $port = config('qdrant.port', 6333);
        $baseUrl = rtrim($host, '/').':'.$port;

        return $baseUrl.$path;
    }

    /**
     * HTTPリクエストを実行
     *
     * @param  'get'|'post'|'put'|'delete'  $method
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private static function makeRequest(string $method, string $path, array $data = []): ?array
    {
        $url = self::getApiUrl($path);

        $response = match ($method) {
            'get' => Http::get($url),
            'post' => Http::post($url, $data),
            'put' => Http::put($url, $data),
            'delete' => Http::delete($url),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }

    /**
     * コレクションの存在確認と作成
     */
    private static function ensureCollection(string $collectionName): void
    {
        if (isset(self::$collections[$collectionName])) {
            return;
        }

        $collectionResponse = self::makeRequest('get', "/collections/{$collectionName}");

        if (! $collectionResponse) {
            self::createCollection($collectionName);
        }

        self::$collections[$collectionName] = true;
    }

    /**
     * コレクションを作成
     */
    private static function createCollection(string $collectionName): void
    {
        $embeddingModel = config('qdrant.embedding_model', 'text-embedding-ada-002');
        $vectorSize = self::getVectorSize($embeddingModel);

        $response = self::makeRequest('put', "/collections/{$collectionName}", [
            'vectors' => [
                'size' => $vectorSize,
                'distance' => self::DISTANCE_METHOD,
            ],
        ]);

        if (! $response) {
            throw new \RuntimeException("Failed to create collection: {$collectionName}");
        }
    }

    /**
     * コレクションを削除
     */
    private static function deleteCollection(string $collectionName): void
    {
        self::makeRequest('delete', "/collections/{$collectionName}");
        unset(self::$collections[$collectionName]);
    }

    /**
     * 埋め込みモデルからベクトルサイズを取得
     */
    private static function getVectorSize(string $model): int
    {
        return match ($model) {
            'text-embedding-ada-002' => 1536,
            'text-embedding-3-small' => 1536,
            'text-embedding-3-large' => 3072,
            default => self::DEFAULT_VECTOR_SIZE,
        };
    }

    /**
     * テキストから埋め込みベクトルを取得
     *
     * @return array<float>
     */
    private static function getEmbedding(string $text): array
    {
        $model = config('qdrant.embedding_model', 'text-embedding-ada-002');

        $response = OpenAI::embeddings()->create([
            'model' => $model,
            'input' => $text,
        ]);

        return $response->embeddings[0]->embedding;
    }

    /**
     * データから埋め込みベクトルを取得
     *
     * @param  array<string, mixed>|string  $targetData
     * @return array<float>
     */
    private static function getEmbeddingFromData(array|string $targetData): array
    {
        $pageContent = is_array($targetData)
            ? json_encode($targetData, JSON_UNESCAPED_UNICODE)
            : $targetData;

        return self::getEmbedding($pageContent);
    }

    /**
     * ドキュメントIDを正規化（配列に変換してハッシュ化）
     *
     * @param  array<string>|string  $docIds
     * @return array<string>
     */
    private static function normalizeDocIds(array|string $docIds): array
    {
        $ids = is_array($docIds) ? $docIds : [$docIds];

        return array_map([self::class, 'hashDocId'], $ids);
    }

    /**
     * ドキュメントIDをハッシュ化
     */
    private static function hashDocId(string $docId): string
    {
        // すでにハッシュ化されている場合はそのまま返す（MD5は32文字の16進数）
        if (strlen(str_replace('-', '', $docId)) === 32 && ctype_xdigit(str_replace('-', '', $docId))) {
            return $docId;
        }

        return md5($docId);
    }

    /**
     * レスポンスからペイロードを抽出
     *
     * @param  array<string, mixed>  $response
     * @return array<int, array<string, mixed>>
     */
    private static function extractPayloads(array $response): array
    {
        if (! isset($response['result']) || ! is_array($response['result'])) {
            return [];
        }

        $payloads = [];
        foreach ($response['result'] as $point) {
            if (isset($point['payload']) && is_array($point['payload'])) {
                $payloads[] = $point['payload'];
            }
        }

        return $payloads;
    }

    /**
     * 類似検索結果を抽出
     *
     * @param  array<string, mixed>  $response
     * @return array<int, array<string, mixed>>
     */
    private static function extractSimilarResults(array $response): array
    {
        if (! isset($response['result']['points']) || ! is_array($response['result']['points'])) {
            return [];
        }

        $results = [];
        foreach ($response['result']['points'] as $point) {
            if (! isset($point['payload'], $point['score']) || ! is_array($point['payload'])) {
                continue;
            }

            $content = $point['payload'];
            $content['similarity_score'] = $point['score'];
            $results[] = $content;
        }

        return $results;
    }
}
