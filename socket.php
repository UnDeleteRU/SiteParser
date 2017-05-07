<?php

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Undelete\SiteStat\SocketComponent;

require_once 'vendor/autoload.php';

$server = IoServer::factory(
    new HttpServer(new WsServer(new SocketComponent)),
    8080
);

try {
    $server->run();
} catch (\Exception $e) {
    echo 'exception: ' . $e->getMessage();
}
