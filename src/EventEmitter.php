<?php
namespace Grok\Socket;

/**
 * EventEmitter Class
 * 
 * This class provides an event system similar to Node.js EventEmitter.
 * Why: To handle asynchronous events like 'connection', 'message' in a decoupled way.
 * From: Inspired by event patterns in JavaScript; implemented in PHP for pub/sub.
 * Benefits: Loose coupling – listeners can be added/removed dynamically; scalable for real-time apps.
 * Usage: Extend or use in sockets for custom events.
 * 
 * @method static self on(string $event, callable $listener) Add persistent listener for event
 * @method static self once(string $event, callable $listener) Add one-time listener for event
 * @method static self off(string $event, ?callable $listener = null) Remove listener(s) for event
 * @method static self emit(string $event, ...$args) Trigger event with arguments
 */
class EventEmitter {
    private array $events = [];     // Persistent listeners
    private array $onceEvents = []; // One-time listeners

    /**
     * Add a persistent listener for an event.
     * Why: Allows multiple handlers for the same event.
     * Benefits: Reusability; no need for if-else chains.
     * 
     * @param string $event Event name (e.g., 'connection')
     * @param callable $listener Function to call: fn(...$args) => {}
     * @return self For chaining
     */
    public function on(string $event, callable $listener): self {
        $this->events[$event][] = $listener;
        return $this;
    }

    /**
     * Add a one-time listener.
     * Why: For events that should trigger only once (e.g., 'connect').
     * Benefits: Prevents memory leaks from unused listeners.
     * 
     * @param string $event Event name
     * @param callable $listener One-time function
     * @return self For chaining
     */
    public function once(string $event, callable $listener): self {
        $this->onceEvents[$event][] = $listener;
        return $this;
    }

    /**
     * Remove listeners.
     * Why: Cleanup to avoid memory issues.
     * Benefits: Developer control over event lifecycle.
     * 
     * @param string $event Event name
     * @param ?callable $listener Specific listener to remove (null removes all)
     * @return self For chaining
     */
    public function off(string $event, ?callable $listener = null): self {
        if ($listener) {
            $this->events[$event] = array_filter($this->events[$event] ?? [], fn($l) => $l !== $listener);
            $this->onceEvents[$event] = array_filter($this->onceEvents[$event] ?? [], fn($l) => $l !== $listener);
        } else {
            unset($this->events[$event], $this->onceEvents[$event]);
        }
        return $this;
    }

    /**
     * Trigger event with args.
     * Why: Central dispatch for all listeners.
     * Benefits: Efficient; supports variadic args for flexibility.
     * 
     * @param string $event Event name
     * @param mixed ...$args Arguments to pass to listeners
     * @return self For chaining
     */
    public function emit(string $event, ...$args): self {
        foreach ($this->onceEvents[$event] ?? [] as $listener) {
            $listener(...$args);
        }
        unset($this->onceEvents[$event]);

        foreach ($this->events[$event] ?? [] as $listener) {
            $listener(...$args);
        }
        return $this;
    }
}