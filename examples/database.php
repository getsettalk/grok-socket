<?php
require __DIR__ . '/../vendor/autoload.php';

use Grok\Socket\Server;

$server = new Server();

// DB setup (MySQL example)
try {
    $pdo = new PDO('mysql:host=localhost;dbname=chat_db', 'root', 'password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Create table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (id INT AUTO_INCREMENT PRIMARY KEY, user_id VARCHAR(255), msg TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

$server->on('connection', function($socket) use ($pdo) {
    $userId = $socket->getData('userId') ?? 'guest';

    $socket->on('message', function($msg) use ($pdo, $socket, $userId) {
        // Store to DB
        $stmt = $pdo->prepare("INSERT INTO messages (user_id, msg) VALUES (?, ?)");
        $stmt->execute([$userId, $msg]);
        
        // Broadcast with DB ID
        $messageId = $pdo->lastInsertId();
        $socket->server->emit('new_message', [
            'id' => $messageId,
            'from' => $userId,
            'msg' => $msg
        ]);
    });

    // Load history on connect
    $stmt = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 50");
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $socket->emit('chat_history', array_reverse($history)); // Newest last
});

$server->run();