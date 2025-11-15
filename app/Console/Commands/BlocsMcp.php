<?php

namespace App\Console\Commands;

use App\Services\BlocsMcpServer;
use Illuminate\Console\Command;

class BlocsMcp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blocs:mcp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the MCP server for Blocs documentation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $server = new BlocsMcpServer;
        $server->run();

        return Command::SUCCESS;
    }
}
