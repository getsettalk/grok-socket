<?php
declare(strict_types=1);

namespace Grok\Socket;

use Grok\Socket\EventEmitter;
use Grok\Socket\Utils;

/**
 * GrokSocket Server - Full-featured WebSocket server
 */
class Server extends EventEmitter
{
    private $serverSocket;
    private string $host;
    private int $port;
    private array $clients = [];
    private array $namespaces = [];
    private array $middleware = [];

    public function __construct(string $host = '0.0.0.0', int $port = 8080)
    {
        $this->host = $host;
        $this->port = $port;
        $this->namespaces['/'] = new SocketNamespace('/');
    }

    public function setEncryptionKey(string $key): self
    {
        Utils::setEncryptionKey($key);
        return $this;
    }

    public function use(callable $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    public function of(string $name = '/'): SocketNamespace
    {
        if (!isset($this->namespaces[$name])) {
            $this->namespaces[$name] = new SocketNamespace($name);
        }
        return $this->namespaces[$name];
    }

    public function run(): void
    {
        $this->serverSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->serverSocket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->serverSocket, $this->host, $this->port);
        socket_listen($this->serverSocket);
        socket_set_nonblock($this->serverSocket);

        echo "GrokSocket Server started → ws://{$this->host}:{$this->port}\n";
        echo "Open examples/reconnect-client.html in your browser\n\n";

        $clients = [$this->serverSocket];

        while (true) {
            $read = $clients;
            $write = $except = null;

            if (socket_select($read, $write, $except, null) === false) {
                continue;
            }

            // New connection
            if (in_array($this->serverSocket, $read, true)) {
                if ($newSocket = socket_accept($this->serverSocket)) {
                    $this->handleHandshake($newSocket);
                    $clients[] = $newSocket;
                }
                unset($read[array_search($this->serverSocket, $read, true)]);
            }

            // Read from existing clients
            foreach ($read as $socket) {
                $data = @socket_read($socket, 2048);
                if ($data === false || $data === '') {
                    $this->disconnectClient($socket);
                    unset($clients[array_search($socket, $clients, true)]);
                    continue;
                }
                $this->handleMessage($socket, $data);
            }
        }
    }

    private function handleHandshake($socket): void
    {
        $buffer = '';
        while (!str_contains($buffer, "\r\n\r\n")) {
            $buffer .= socket_read($socket, 1024) ?: '';
        }

        if (preg_match('/Sec-WebSocket-Key: (.*)\r\n/', $buffer, $matches)) {
            $key = trim($matches[1]);
            $accept = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

            $response = "HTTP/1.1 101 Switching Protocols\r\n" .
                        "Upgrade: websocket\r\n" .
                        "Connection: Upgrade\r\n" .
                        "Sec-WebSocket-Accept: $accept\r\n\r\n";

            socket_write($socket, $response);

            $id = uniqid('client_', true);
            $client = new Socket($socket, $id);
            $this->clients[$id] = $client;

            // Run middleware (e.g. auth)
            foreach ($this->middleware as $mw) {
                $mw($client);
            }

            $this->of('/')->add($client);
            $this->emit('connection', $client);
            $client->emit('connect', ['status' => 'connected']);
        }
    }

    private function handleMessage($socket, string $data): void
    {
        $clientId = $this->findClientId($socket);
        if (!$clientId || !isset($this->clients[$clientId])) return;

        $client = $this->clients[$clientId];
        $decoded = $client->decode($data);

        if (isset($decoded['event'])) {
            // Emit on the client object (triggers client-local listeners)
            $client->emit($decoded['event'], $decoded['data'] ?? null);
            // Emit on the server-level so application code can listen: fn($client, $data)
            $this->emit($decoded['event'], $client, $decoded['data'] ?? null);
            // Broadcast to namespace (other clients), excluding sender
            $this->of('/')->emit($decoded['event'], $decoded['data'] ?? null, $client);
        }
    }

    private function disconnectClient($socket): void
    {
        $id = $this->findClientId($socket);
        if ($id && isset($this->clients[$id])) {
            $client = $this->clients[$id];
            $this->of('/')->remove($client);
            $client->close();
            unset($this->clients[$id]);
            $this->emit('disconnect', $client);
        }
    }

    private function findClientId($socket): ?string
    {
        foreach ($this->clients as $id => $client) {
            if ($client->getResource() === $socket) {
                return $id;
            }
        }
        return null;
    }
}