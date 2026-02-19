<?php

namespace Blocs\AI;

use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;

class VectorStore
{
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
     * 埋め込み API から取得したベクトル次元数のキャッシュ（getVectorSize 用）
     */
    private static ?int $cachedVectorSize = null;

    /**
     * ドキュメントを取得
     *
     * @param  array<string>|string  $docIds
     * @return array<string, mixed>|array<int, array<string, mixed>>
     */
    public static function get(string $collectionName, array|string $docIds): array
    {
        self::ensureCollection($collectionName);

        $normalizedDocIds = self::normalizeDocIds($docIds);

        $response = self::makeRequest('post', "/collections/{$collectionName}/points", [
            'ids' => $normalizedDocIds,
            'with_payload' => true,
            'with_vectors' => false,
        ]);

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
     * @param  array<string, mixed>|null  $filter
     * @return array<string>
     */
    public static function getAllIds(string $collectionName, ?array $filter = null): array
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

            if ($filter !== null) {
                $requestData['filter'] = $filter;
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
     */
    public static function delete(string $collectionName, array|string $docIds = []): void
    {
        if (empty($docIds)) {
            self::deleteCollection($collectionName);

            return;
        }

        self::ensureCollection($collectionName);
        $normalizedDocIds = self::normalizeDocIds($docIds);

        self::makeRequest('post', "/collections/{$collectionName}/points/delete", [
            'points' => $normalizedDocIds,
        ]);
    }

    /**
     * 類似ドキュメントを検索
     *
     * @param  array<string, mixed>|string  $targetData
     * @return array<int, array<string, mixed>>
     */
    public static function similar(
        string $collectionName,
        array|string $targetData,
        int $docsLimit = self::DEFAULT_DOCS_LIMIT,
        ?float $scoreThreshold = null
    ): array {
        self::ensureCollection($collectionName);

        $embedding = self::getEmbeddingFromData($targetData);

        $query = [
            'query' => $embedding,
            'limit' => $docsLimit,
            'with_payload' => true,
            'with_vectors' => false,
        ];
        isset($scoreThreshold) && $query['score_threshold'] = $scoreThreshold;

        $response = self::makeRequest('post', "/collections/{$collectionName}/points/query", $query);

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
     * Qdrant Cloud 用 API キーが設定されている場合のみ api-key ヘッダーを付与する。
     *
     * @param  'get'|'post'|'put'|'delete'  $method
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private static function makeRequest(string $method, string $path, array $data = []): ?array
    {
        $url = self::getApiUrl($path);
        $http = self::httpClient();

        $response = match ($method) {
            'get' => $http->get($url),
            'post' => $http->post($url, $data),
            'put' => $http->put($url, $data),
            'delete' => $http->delete($url),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }

    /**
     * API キーが設定されている場合に api-key ヘッダーを付与した HTTP クライアントを返す
     */
    private static function httpClient(): \Illuminate\Http\Client\PendingRequest
    {
        $request = Http::timeout(30);
        $apiKey = config('qdrant.api_key');

        if ($apiKey !== null && $apiKey !== '') {
            $request = $request->withHeaders(['api-key' => $apiKey]);
        }

        return $request;
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
        $embeddingModel = config('qdrant.embedding_model');
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
     * 埋め込み API のレスポンスからベクトルサイズを取得（1 回のみ API 呼び出しし、結果をキャッシュ）
     */
    private static function getVectorSize(string $embeddingModel): int
    {
        if (self::$cachedVectorSize !== null) {
            return self::$cachedVectorSize;
        }

        try {
            $embedding = self::getEmbedding('x');
            if ($embedding === []) {
                return self::DEFAULT_VECTOR_SIZE;
            }
            self::$cachedVectorSize = count($embedding);

            return self::$cachedVectorSize;
        } catch (\Throwable) {
            return self::DEFAULT_VECTOR_SIZE;
        }
    }

    /**
     * テキストから埋め込みベクトルを取得
     *
     * @return array<float>
     */
    private static function getEmbedding(string $text): array
    {
        $embeddingModel = config('qdrant.embedding_model');
        $baseUri = config('qdrant.embedding_base_uri');

        if ($baseUri !== null && $baseUri !== '') {
            $url = rtrim($baseUri, '/').'/v1/embeddings';
            $http = Http::timeout(30);
            $apiKey = config('qdrant.embedding_api_key');
            if ($apiKey !== null && $apiKey !== '') {
                $http = $http->withToken($apiKey);
            }
            $response = $http->post($url, [
                'model' => $embeddingModel,
                'input' => $text,
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException('Embedding API request failed: '.$response->status());
            }

            $json = $response->json();
            $embedding = $json['data'][0]['embedding'] ?? null;

            return is_array($embedding) ? $embedding : [];
        }

        $response = OpenAI::embeddings()->create([
            'model' => $embeddingModel,
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
