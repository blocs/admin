<?php

namespace App\Mcp\Servers;

use App\Mcp\Resources\BlocsManualResource;
use App\Mcp\Tools\BlocsDevelopTool;
use Illuminate\Support\Str;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Resource;

class BlocsServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Blocs Server';

    /**
     * The MCP server's version.
     */
    protected string $version = '1.0.0';

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        BlocsDevelopTool::class,
    ];

    /**
     * Scan and register resources dynamically.
     *
     * @var array<int, resource>
     */
    protected array $resources = [];

    /**
     * Base path for Blocs manual resources.
     */
    private const RESOURCES_BASE_PATH = 'vendor/blocs/admin/mcp/resources';

    /**
     * URI scheme prefix for Blocs resources.
     */
    private const URI_SCHEME = 'blocs://';

    protected function boot(): void
    {
        $basePath = base_path(self::RESOURCES_BASE_PATH);

        if (! is_dir($basePath)) {
            return;
        }

        $resources = $this->scanResources($basePath);

        $this->resources = array_merge($this->resources, $resources);
        $this->updatePaginationLimits(count($resources));
    }

    /**
     * Scan directory for markdown resources.
     *
     * @return array<int, resource>
     */
    protected function scanResources(string $basePath): array
    {
        $resources = [];
        $this->scanDirectory($basePath, $basePath, $resources);

        usort($resources, static fn (Resource $a, Resource $b): int => strcmp($a->uri(), $b->uri()));

        return $resources;
    }

    /**
     * Recursively scan directory for markdown files.
     *
     * @param  array<int, resource>  $resources
     */
    protected function scanDirectory(string $basePath, string $currentPath, array &$resources): void
    {
        $items = @scandir($currentPath);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $currentPath.DIRECTORY_SEPARATOR.$item;

            if (is_dir($fullPath)) {
                $this->scanDirectory($basePath, $fullPath, $resources);

                continue;
            }

            if ($this->isMarkdownFile($fullPath, $item)) {
                $relativePath = Str::after($fullPath, $basePath.DIRECTORY_SEPARATOR);
                $uri = self::URI_SCHEME.$relativePath;
                [$title, $description] = $this->extractHeader($fullPath);

                $resources[] = new BlocsManualResource(
                    $uri,
                    $fullPath,
                    $relativePath,
                    $title,
                    $description
                );
            }
        }
    }

    /**
     * Check if the file is a markdown file.
     */
    protected function isMarkdownFile(string $fullPath, string $filename): bool
    {
        return is_file($fullPath) && Str::endsWith($filename, '.md');
    }

    /**
     * Update pagination limits to include all resources.
     */
    protected function updatePaginationLimits(int $resourceCount): void
    {
        if ($resourceCount > 0) {
            $this->defaultPaginationLength = max($this->defaultPaginationLength, $resourceCount);
            $this->maxPaginationLength = max($this->maxPaginationLength, $resourceCount);
        }
    }

    /**
     * Extract title and description from markdown file header.
     *
     * @return array{0:string,1:string}
     */
    protected function extractHeader(string $filePath): array
    {
        $content = @file_get_contents($filePath);

        if ($content === false) {
            return ['', ''];
        }

        $parts = explode("\n\n", $content, 2);

        if (count($parts) < 2) {
            return ['', ''];
        }

        [$header] = $parts;
        $headerLines = explode("\n", $header, 2);

        if (count($headerLines) < 2) {
            return ['', ''];
        }

        return [
            trim($headerLines[0]),
            trim($headerLines[1]),
        ];
    }
}
