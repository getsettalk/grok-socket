# Usage in Apps

## Plain PHP
- Require: `require 'vendor/autoload.php';`
- Server: `$server = new Server(); $server->run();`
- Events: In `on('connection', fn($socket) => ...);`

## Laravel
1. Add to composer.json: `"repositories": [{"type": "path", "url": "./grok-socket"}]`, `"require": {"grok/socket": "*"}`
2. In `app/Providers/AppServiceProvider.php`: `app()->singleton(Server::class, fn() => new Server());`
3. Artisan command (`app/Console/Commands/WebSocketServe.php`): `$server = app(Server::class); $server->run();`
4. Use in controllers: `app(Server::class)->emit('notify', $data);`
5. DB: Use `DB::table('messages')->insert([...]);` in events.

## Other Frameworks (Symfony, etc.)
- Bind to DI container.
- Run server in background process.