# Overview

GrokSocket is a lightweight WebSocket library for PHP. It uses pure PHP sockets for the core loop, providing a Socket.IO-like experience.

Core Components:
- EventEmitter: Base for events (on/emit/off).
- Socket: Per-client handler (send/join/emit).
- Room: Group broadcasting.
- Namespace: Isolated event spaces (/chat).
- Server: Main loop and management.
- Client: PHP client for testing.
- Utils: Encryption helpers.

Features:
- Auto-reconnect: In JS client example.
- Encryption: AES-256 for payloads.
- Auth: Middleware with JWT.
- DB: PDO examples for persistence.

For WSS: Extend Server with stream_socket_server and SSL context.