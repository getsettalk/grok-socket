# Method Breakdowns

## EventEmitter::on(string $event, callable $listener)
- What: Registers a callback for an event.
- Why: Enables reactive programming.
- From: Observer pattern (common in JS/PHP).
- How: Pushes callable to internal array; triggers on emit().
- Benefits: Decouples sender/receiver; easy testing.
- Example: $socket->on('message', fn($data) => echo $data;);

## Socket::join(string $room)
- What: Adds socket to room array.
- Why: Prepares for room broadcasts.
- From: Socket.IO rooms API.
- How: Array push if not exists.
- Benefits: O(1) join; fast lookups.
- Example: $socket->join('private-chat-123');

(Continue for all methods—add similar blocks for others in your editor.)