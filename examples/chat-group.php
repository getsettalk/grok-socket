<?php
require __DIR__ . '/../vendor/autoload.php';

use Grok\Socket\Server;

$server = new Server();
$server->on('connection', function($socket) {
    $socket->join('group-chat'); // Auto-join group
    $socket->emit('joined_group', 'Welcome to group chat!');

    $socket->on('group_message', function($msg) use ($socket) {
        $socket->server->of('/')->to('group-chat')->emit('group_message', [
            'from' => $socket->getId(),
            'msg' => $msg
        ]);
    });
});
$server->run();