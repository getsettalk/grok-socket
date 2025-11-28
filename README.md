# GrokSocket - Full PHP WebSocket Library

Pure PHP, Socket.IO-like WebSocket server with:
- Events, rooms, namespaces
- One-to-one & group chat
- Broadcasting
- JWT Auth middleware
- AES-256 encryption
- Database integration
- Auto-reconnect client
- Full PHPDoc (VS Code hover)
- Laravel ready

## Install
```bash
composer require grok-s/socket
```


## Why GrokSocket?
- **Easy to Use**: API like Socket.IO for quick setup.
- **Full Features**: Events, rooms (for chats), broadcasting, ACKs, middleware (auth), encryption (AES-256), DB examples.
- **Integrations**: Plain PHP, Laravel (service provider), others.
- **Secure**: WSS, message encryption.
- **Open-Source**: MIT – contribute on GitHub!


# Example code and image
<img width="2554" height="1076" alt="image" src="https://github.com/user-attachments/assets/df8902b8-9f69-42c8-ba7a-242129ae9cd8" />

here is example code running from `php ws/server.php start` you can copy these code and try to connect with same ip address or `localhost` with port

```php

<?php
require 'vendor/autoload.php';

use Grok\Socket\Server;

$server = new Server('127.0.0.1', 8282);
// $server->setEncryptionKey('your-secret-key-here');  // Optional: AES-256

// Basic event handling
$server->on('connection', function($socket) {
    echo "Client connected: " . $socket->getId() . "\n";
    $socket->emit('welcome', 'Hello from GrokSocket!');
});


$server->on('chat', function($client, $data) use ($server) {
    echo "Message from " . $client->getId() . ": " . (is_string($data) ? $data : json_encode($data)) . "\n";
});

$server->on('disconnect', function($client) {
    echo "Client disconnected: " . $client->getId() . "\n";
});


$server->run();  // Starts the server
```


---
to learn more explore docs and example folder, you can use it with any production application.
