<?php

namespace App\Services;

class BlocsMcpServer
{
    public function run(): void
    {
        while (true) {
            $input = trim(fgets(STDIN));

            if (empty($input)) {
                continue;
            }

            $request = json_decode($input, true);
            if (! $request) {
                continue;
            }

            $this->handleRequest($request);
        }
    }

    private function handleRequest(array $request): void
    {
        $method = $request['method'] ?? '';
        $params = $request['params'] ?? [];
        $id = $request['id'] ?? null;

        switch ($method) {
            case 'initialize':
                $this->handleInitialize($params, $id);
                break;
            case 'notifications/initialized':
                break;
            case 'resources/list':
                $this->handleResourcesList($id);
                break;
            case 'resources/read':
                $this->handleResourceRead($params, $id);
                break;
            case 'tools/list':
                $this->handleToolsList($id);
                break;
            case 'tools/call':
                $this->handleToolsCall($params, $id);
                break;
            default:
                $this->sendError($id, -32601, 'Method not found');
        }
    }

    private function handleInitialize(array $params, $id): void
    {
        $this->sendResponse([
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => [
                    'resources' => [
                        'listChanged' => false,
                    ],
                    'tools' => [
                        'listChanged' => false,
                    ],
                ],
                'serverInfo' => [
                    'name' => 'Laravel Blocs',
                    'version' => '1.0.0',
                ],
            ],
        ]);
    }

    private function sendResponse(array $response): void
    {
        echo json_encode($response)."\n";
    }

    private function handleResourcesList($id): void
    {
        $resources = $this->getBlocsResources();
        $this->sendResponse([
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'resources' => $resources,
            ],
        ]);
    }

    private function handleResourceRead(array $params, $id): void
    {
        $uri = $params['uri'] ?? '';

        try {
            $content = $this->readResource($uri);
            $this->sendResponse([
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'contents' => [
                        [
                            'uri' => $uri,
                            'mimeType' => 'text/markdown',
                            'text' => $content,
                        ],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            $this->sendError($id, -32603, $e->getMessage());
        }
    }

    private function getBlocsResources(): array
    {
        $adminPath = base_path('vendor/blocs/admin/mcp/resources/');
        $resources = [];

        $this->scanDirectory($adminPath, $adminPath, $resources);

        return $resources;
    }

    private function scanDirectory(string $basePath, string $currentPath, array &$resources): void
    {
        $items = scandir($currentPath);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            // 対象外のファイル
            if (in_array(basename($item), ['developer.md'])) {
                continue;
            }

            $fullPath = $currentPath.'/'.$item;
            $relativePath = str_replace($basePath.'/', '', $fullPath);

            if (is_dir($fullPath)) {
                $this->scanDirectory($basePath, $fullPath, $resources);
            } elseif (is_file($fullPath) && str_ends_with($item, '.md')) {
                $uri = 'blocs-admin://'.$relativePath;
                [$name, $description] = $this->getResourceHeader($fullPath);
                $resources[] = [
                    'uri' => $uri,
                    'mimeType' => 'text/markdown',
                    'name' => $name,
                    'description' => $description,
                ];
            }
        }
    }

    private function readResource(string $uri): string
    {
        if (! str_starts_with($uri, 'blocs-admin://')) {
            throw new \Exception("Invalid resource URI: $uri");
        }

        $relativePath = str_replace('blocs-admin://', '', $uri);
        $fullPath = base_path('vendor/blocs/admin/mcp/resources/'.$relativePath);

        if (! file_exists($fullPath)) {
            throw new \Exception("Resource not found: $uri");
        }

        $content = file_get_contents($fullPath);
        if ($content === false) {
            throw new \Exception("Failed to read resource: $uri");
        }

        return $content;
    }

    private function getResourceHeader(string $fullPath): array
    {
        $content = file_get_contents($fullPath);
        if ($content === false) {
            return ['', ''];
        }

        $parts = explode("\n\n", $content, 2);
        if (count($parts) < 2) {
            return ['', ''];
        }
        [$header, $content] = $parts;

        $parts = explode("\n", $header, 2);
        if (count($parts) < 2) {
            return ['', ''];
        }
        [$name, $description] = $parts;

        return [$name, $description];
    }

    private function handleToolsList($id): void
    {
        $this->sendResponse([
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'tools' => [
                    [
                        'name' => 'blocs_develop',
                        'description' => 'Execute artisan blocs:develop command to generate admin interface from JSON definition file',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'path' => [
                                    'type' => 'string',
                                    'description' => 'Path to the JSON definition file (e.g., docs/develop/sample.json)',
                                ],
                            ],
                            'required' => ['path'],
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function handleToolsCall(array $params, $id): void
    {
        $name = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];

        try {
            switch ($name) {
                case 'blocs_develop':
                    $result = $this->executeBlocsDevelop($arguments);
                    $this->sendResponse([
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'result' => [
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => $result,
                                ],
                            ],
                        ],
                    ]);
                    break;
                default:
                    $this->sendError($id, -32601, "Unknown tool: $name");
            }
        } catch (\Exception $e) {
            $this->sendError($id, -32603, $e->getMessage());
        }
    }

    private function executeBlocsDevelop(array $arguments): string
    {
        $path = $arguments['path'] ?? null;

        if (empty($path)) {
            throw new \Exception('Path parameter is required');
        }

        $fullPath = base_path($path);

        if (! file_exists($fullPath)) {
            throw new \Exception("File not found: $fullPath");
        }

        // Artisanコマンドを実行
        $command = base_path('artisan');
        $output = [];
        $returnVar = 0;

        exec(
            escapeshellarg(PHP_BINARY).' '.escapeshellarg($command).' blocs:develop '.escapeshellarg($path).' 2>&1',
            $output,
            $returnVar
        );

        $result = implode("\n", $output);

        if ($returnVar !== 0) {
            throw new \Exception("Command failed: $result");
        }

        return $result ?: 'Command executed successfully';
    }

    private function sendError($id, int $code, string $message): void
    {
        $this->sendResponse([
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ]);
    }
}
