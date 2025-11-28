<?php
namespace Grok\Socket;

use Grok\Socket\EventEmitter;
use Grok\Socket\Utils;

/**
 * Client Class
 * 
 * PHP-based WebSocket client for testing or server-to-server comms.
 * Why: Allows PHP apps to connect as clients.
 * From: PHP sockets for connection; RFC 6455 for handshake.
 * Benefits: Consistent API with server; supports encryption.
 * Usage: For bots, integrations; poll-based loop for simplicity (extend for async).
 * 
 * @method self connect() Establish connection and start listening
 * @method self emit(string $event, mixed $data, ?callable $ack = null) Send event
 * @method self disconnect() Close connection
 */
class Client extends EventEmitter {
    private $socket;
    private bool $connected = false;
    private string $url;

    public function __construct(string $url) {
        $this->url = $url;
    }

    /**
     * Connect and listen.
     * Why: Handles handshake and polling.
     * @return self For chaining
     */
    public function connect(): self {
        $parts = parse_url($this->url);
        $scheme = $parts['scheme'] ?? 'ws';
        $host = $parts['host'];
        $port = $parts['port'] ?? ($scheme === 'wss' ? 443 : 80);

        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_connect($this->socket, $host, $port);

        $key = base64_encode(random_bytes(16));
        $request = "GET / HTTP/1.1\r\nHost: $host\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Key: $key\r\nSec-WebSocket-Version: 13\r\n\r\n";
        socket_write($this->socket, $request);

        $response = socket_read($this->socket, 1024);
        if (strpos($response, '101') !== false) {
            $this->connected = true;
            $this->emit('connect');
        }

        // Simple poll loop (for demo; use in thread for prod)
        while ($this->connected) {
            $data = @socket_read($this->socket, 1024);
            if ($data) {
                $decoded = $this->decode($data);
                $this->emit($decoded['event'] ?? 'message', $decoded['data']);
            }
            usleep(100000);
        }

        return $this;
    }

    /**
     * Emit event.
     * @param string $event Event
     * @param mixed $data Data
     * @param ?callable $ack ACK
     * @return self For chaining
     */
    public function emit(string $event, mixed $data, ?callable $ack = null): self {
        if (!$this->connected) return $this;
        $packet = json_encode(['event' => $event, 'data' => $data, 'ack' => $ack ? uniqid() : null]);
        if (Utils::getEncryptionKey()) $packet = Utils::encrypt($packet);
        $frame = $this->encode($packet);
        socket_write($this->socket, $frame, strlen($frame));
        return $this;
    }

    /**
     * Disconnect.
     * @return self For chaining
     */
    public function disconnect(): self {
        $this->connected = false;
        socket_close($this->socket);
        $this->emit('disconnect');
        return $this;
    }

    private function encode(string $payload): string {
        $b1 = 0x81; // Text
        $length = strlen($payload);
        $header = $length <= 125 ? pack('CC', $b1, $length | 0x80) :
                  pack('CCn', $b1, 126 | 0x80, $length);
        $mask = pack('CCCC', rand(0,255), rand(0,255), rand(0,255), rand(0,255));
        $masked = '';
        for ($i = 0; $i < $length; $i++) {
            $masked .= chr(ord($payload[$i]) ^ ord($mask[$i % 4]));
        }
        return $header . $mask . $masked;
    }

    private function decode(string $data): array {
        $b1 = ord($data[0]);
        $opcode = $b1 & 0x0F;
        $b2 = ord($data[1]);
        $length = $b2 & 0x7F;
        $offset = 2;
        if ($length === 126) { $length = unpack('n', substr($data, 2, 2))[1]; $offset += 2; }
        elseif ($length === 127) { $length = unpack('J', substr($data, 2, 8))[1]; $offset += 8; }
        $payload = substr($data, $offset, $length);
        if (Utils::getEncryptionKey()) $payload = Utils::decrypt($payload);
        return json_decode($payload, true) ?? ['data' => $payload];
    }
}