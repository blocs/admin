<?php

use App\Mcp\Servers\BlocsServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('blocs-mcp', BlocsServer::class);
