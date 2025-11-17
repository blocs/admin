<?php

namespace App\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Artisan;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class BlocsDevelopTool extends Tool
{
    /**
     * The tool's name exposed to MCP.
     */
    protected string $name = 'blocs_develop';

    /**
     * The tool's description.
     */
    protected string $description = 'Execute artisan blocs:develop command to generate admin panel from JSON definition file.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $path = $this->getPathFromRequest($request);

        if ($path === '') {
            return Response::error('Path parameter is required.');
        }

        $validationResponse = $this->validatePath($path);

        if ($validationResponse !== null) {
            return $validationResponse;
        }

        return $this->executeCommand($path);
    }

    /**
     * Get path from request.
     */
    protected function getPathFromRequest(Request $request): string
    {
        return (string) ($request->get('path') ?? '');
    }

    /**
     * Validate the provided path.
     */
    protected function validatePath(string $path): ?Response
    {
        $fullPath = base_path($path);

        if (! is_readable($fullPath)) {
            return Response::error("File not found or not readable: {$fullPath}");
        }

        return null;
    }

    /**
     * Execute the artisan command.
     */
    protected function executeCommand(string $path): Response
    {
        try {
            Artisan::call('blocs:develop', [
                'path' => $path,
            ]);
        } catch (\Throwable $e) {
            return Response::error('Command failed: '.$e->getMessage());
        }

        $output = (string) Artisan::output();

        return Response::text($output !== '' ? $output : 'Command executed successfully');
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'path' => $schema->string()->description('Path to the JSON definition file (e.g., docs/develop/sample.json)'),
        ];
    }
}
