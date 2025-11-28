# Best Practices

- **Scaling**: For 1000+ users, use Redis pub/sub (extend Adapter with RedisAdapter class).
- **Security**: Always use WSS in prod: `$context = stream_context_create(['ssl' => ['local_cert' => 'cert.pem']]); stream_socket_server("ssl://0.0.0.0:8443", $context);`
- **Encryption**: Use env vars for keys; rotate every 90 days.
- **Auth**: JWT for stateless; validate in middleware, store user in $socket->setData().
- **Deployment**: Run as daemon with Supervisor; use Nginx proxy for WSS: `proxy_pass http://localhost:8080; proxy_http_version 1.1; proxy_set_header Upgrade $http_upgrade;`
- **Testing**: Use `examples/client.php` for unit tests; mock sockets with PHPUnit.
- **WS vs WSS**: WS for local dev; WSS for prod (TLS 1.3+).
- **Control**: All methods chainable (e.g., `$socket->join('room')->emit('hi', $data);`); extend classes for custom (e.g., add Redis to Room).
- **Performance**: Limit middleware to fast ops; use binary for large files.