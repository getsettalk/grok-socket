<?php
require __DIR__ . '/../vendor/autoload.php'; // If using Composer

use Grok\Socket\Server;

$server = new Server();
// Encryption is disabled in this example because the browser client
// does not implement the corresponding decryption. If you need
// end-to-end encryption you'll need a client-side implementation
// that shares the same key and decrypts incoming messages.
// $server->setEncryptionKey('my_secret_key_32chars_long_enough');

// Basic event
$server->on('connection', function($socket) {
    echo "Client connected: " . $socket->getId() . "\n";
    $socket->emit('welcome', ['message' => 'Hello from GrokSocket!']);
});

// Example: handle chat messages from clients, log and broadcast
$server->on('chat', function($client, $data) use ($server) {
    echo "Message from " . $client->getId() . ": " . (is_string($data) ? $data : json_encode($data)) . "\n";
    // Broadcast to all clients (including sender); change as desired
    $server->of('/')->emit('chat', ['from' => $client->getId(), 'msg' => $data, 'timestamp' => time(), 'from Server' => true]);
});

$server->on('disconnect', function($client) {
    echo "Client disconnected: " . $client->getId() . "\n";
});

$server->run();