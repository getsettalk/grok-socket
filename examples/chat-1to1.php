<?php
require __DIR__ . '/../vendor/autoload.php';

use Grok\Socket\Server;

$server = new Server();
$server->on('connection', function($socket) {
    $userId = $socket->getData('userId') ?? 'guest'; // Set in auth middleware
    $socket->on('private_message', function($data) use ($socket, $userId) {
        $toUser = $data['to'];
        $room = min($userId, $toUser) . '-' . max($userId, $toUser); // Unique private room
        $socket->join($room);
        $socket->server->of('/')->to($room)->emit('private_message', [
            'from' => $userId,
            'to' => $toUser,
            'msg' => $data['msg']
        ]);
    });
});
$server->run();