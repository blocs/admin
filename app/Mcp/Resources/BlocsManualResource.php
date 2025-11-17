<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class BlocsManualResource extends Resource
{
    protected string $mimeType = 'text/markdown';

    public function __construct(
        protected string $uri,
        protected string $filePath,
        string $relativePath,
        string $title = '',
        string $description = ''
    ) {
        $this->name = $relativePath;
        $this->title = $title;
        $this->description = $description;
    }

    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        if (! is_readable($this->filePath)) {
            return Response::error("Resource not found: {$this->uri}");
        }

        $content = @file_get_contents($this->filePath);

        if ($content === false) {
            return Response::error("Failed to read resource: {$this->uri}");
        }

        return Response::text($content);
    }
}
