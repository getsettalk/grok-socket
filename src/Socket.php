<?php
namespace Grok\Socket;

use Grok\Socket\EventEmitter;
use Grok\Socket\Utils;

/**
 * Socket Class
 * 
 * Wraps a raw socket resource with WebSocket protocol handling.
 * Why: Abstracts low-level socket ops for high-level API.
 * From: Based on PHP socket extension and RFC 6455 (WebSocket protocol).
 * Benefits: Developer focuses on logic, not framing/masking; handles disconnects gracefully.
 * Usage: Created internally by Server; use for send/emit on per-client basis.
 * 
 * @method string getId() Get unique socket ID
 * @method mixed getData(string $key) Get custom data (e.g. user)
 * @method void setData(string $key, mixed $value) Store user/auth data
 * @method void join(string $room) Join a chat room (group or private)
 * @method void leave(string $room) Leave a room
 * @method bool inRoom(string $room) Check if in room
 * @method array rooms() Get joined rooms
 * @method void emit(string $event, mixed $data, ?callable $ack = null) Send event with optional ACK
 * @method void send(mixed $data, bool $binary = false, ?callable $ack = null) Send raw data
 * @method void close() Disconnect client
 */
class Socket extends EventEmitter {
    private $resource;          // Raw socket
    private string $id;         // Unique ID
    private array $rooms = [];  // Joined rooms
    private bool $connected = true;
    private array $data = [];   // Custom data (e.g., user info)

    public function __construct($resource, string $id) {
        $this->resource = $resource;
        $this->id = $id;
    }

    /**
     * Get unique socket ID.
     * Why: Identify clients for targeting.
     * @return string ID
     */
    public function getId(): string { return $this->id; }

    /**
     * Get raw resource.
     * @return resource Socket resource
     */
    public function getResource() { return $this->resource; }

    /**
     * Set custom data.
     * Why: Store user/session info.
     * @param string $key Key
     * @param mixed $value Value
     */
    public function setData(string $key, $value): void { $this->data[$key] = $value; }

    /**
     * Get custom data.
     * @param string $key Key
     * @return mixed|null Value or null
     */
    public function getData(string $key) { return $this->data[$key] ?? null; }

    /**
     * Join a room for group/1-1 chat.
     * Why: Enables targeted broadcasting.
     * Benefits: Efficient – no need to loop all clients.
     * @param string $room Room name
     */
    public function join(string $room): void {
        if (!in_array($room, $this->rooms)) $this->rooms[] = $room;
    }

    /**
     * Leave a room.
     * @param string $room Room name
     */
    public function leave(string $room): void {
        $key = array_search($room, $this->rooms);
        if ($key !== false) unset($this->rooms[$key]);
    }

    /**
     * Check if in room.
     * @param string $room Room name
     * @return bool True if joined
     */
    public function inRoom(string $room): bool {
        return in_array($room, $this->rooms);
    }

    /**
     * Get joined rooms.
     * @return array Room names
     */
    public function rooms(): array { return $this->rooms; }

    /**
     * Send data (with optional encryption, binary, ACK).
     * Why: Handles framing, masking per RFC 6455.
     * Benefits: Secure (encryption), reliable (ACKs), versatile (binary for files).
     * @param mixed $data Data to send
     * @param bool $binary True for binary data
     * @param ?callable $ack ACK callback
     */
    public function send(mixed $data, bool $binary = false, ?callable $ack = null): void {
        if (!$this->connected || !$this->resource) return;
        $packet = $this->encode($data, $binary, $ack ? uniqid() : null);
        @socket_write($this->resource, $packet, strlen($packet));
    }

    /**
     * Emit an event to this socket (send over network) and trigger local listeners.
     * @param string $event
     * @param mixed $data
     * @param ?callable $ack
     */
    public function emit(string $event, ...$args): self {
        $data = $args[0] ?? null;
        $ack = $args[1] ?? null;

        $packet = ['event' => $event, 'data' => $data];
        $this->send($packet, false, $ack);

        // Trigger any local listeners attached to this socket object
        parent::emit($event, $data);
        return $this;
    }

    /**
     * Close connection.
     * Why: Graceful disconnect.
     */
    public function close(): void {
        // Trigger local disconnect listeners first, avoid sending over closed socket
        parent::emit('disconnect');
        $this->connected = false;
        if ($this->resource) {
            @socket_close($this->resource);
        }
    }

    // Encode: RFC 6455 framing with masking (client-to-server requires mask)
    private function encode(mixed $data, bool $binary, ?string $ackId): string {
        $payload = json_encode(['data' => $data, 'ack' => $ackId, 'event' => $data['event'] ?? null]);
        if (Utils::getEncryptionKey()) { // Encrypt if key set
            $payload = Utils::encrypt($payload);
        }
        $b1 = 0x81; // FIN + Text
        if ($binary) $b1 = 0x82;
        $length = strlen($payload);
        // Server MUST NOT mask frames sent to clients. Mask bit = 0.
        if ($length <= 125) {
            $header = pack('CC', $b1, $length);
        } elseif ($length <= 65535) {
            $header = pack('CCn', $b1, 126, $length);
        } else {
            // 64-bit length: pack high/low 32-bit (best-effort)
            $header = pack('CCNN', $b1, 127, 0, $length);
        }

        // No mask for server frames; payload appended directly
        return $header . $payload;
    }

    // Decode: Unmask and decrypt
    public function decode(string $data): array {
        $b1 = ord($data[0]);
        $opcode = $b1 & 0x0F;
        if ($opcode === 0x8) return ['type' => 'close'];

        $b2 = ord($data[1]);
        $masked = ($b2 & 0x80) === 0x80;
        $offset = 2;
        $length = $b2 & 0x7F;
        if ($length === 126) { $length = unpack('n', substr($data, 2, 2))[1]; $offset += 2; }
        elseif ($length === 127) { $length = unpack('J', substr($data, 2, 8))[1]; $offset += 8; }

        $mask = $masked ? substr($data, $offset, 4) : '';
        $payload = substr($data, $offset + ($masked ? 4 : 0), $length);

        if ($masked) {
            $unmasked = '';
            for ($i = 0; $i < $length; $i++) {
                $unmasked .= chr(ord($payload[$i]) ^ ord($mask[$i % 4]));
            }
            $payload = $unmasked;
        }

        if (Utils::getEncryptionKey()) {
            $payload = Utils::decrypt($payload);
        }

        $decoded = json_decode($payload, true);
        return [
            'type' => $opcode === 0x1 ? 'text' : ($opcode === 0x2 ? 'binary' : 'other'),
            'data' => $decoded['data'] ?? $payload,
            'ack' => $decoded['ack'] ?? null,
            'event' => $decoded['event'] ?? 'message'
        ];
    }
}