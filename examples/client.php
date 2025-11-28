<?php
require __DIR__ . '/../vendor/autoload.php';

use Grok\Socket\Client;

$client = new Client('ws://localhost:8080');
$client->on('connect', function() use ($client) {
    echo "Connected to server with ID: " . $client->getId() . "\n";
});
$client->on('disconnect', function() {
    echo "Disconnected from server\n";
});
$client->on('welcome', function($data) {
    echo "Server says: " . (is_string($data) ? $data : json_encode($data)) . "\n";
});

$client->connect();
$client->emit('chat', 'Hello from PHP client!', function($ack) {
    echo "Server ACK: " . (is_string($ack) ? $ack : json_encode($ack)) . "\n";
});
sleep(5);
$client->disconnect();