<?php
// Requires: composer require firebase/php-jwt
require __DIR__ . '/../vendor/autoload.php';

use Grok\Socket\Server;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$server = new Server();

// Auth middleware
$server->use(function($socket) {
    // Simulate token from handshake (in prod, parse from headers)
    $token = $_GET['token'] ?? 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c'; // Example JWT
    try {
        $decoded = JWT::decode($token, new Key('your_secret_key', 'HS256'));
        $socket->setData('userId', $decoded->sub);
        $socket->setData('name', $decoded->name);
        $socket->emit('auth_success', 'Logged in as ' . $decoded->name);
    } catch (Exception $e) {
        $socket->emit('auth_fail', 'Invalid token');
        $socket->close();
    }
});

$server->run();