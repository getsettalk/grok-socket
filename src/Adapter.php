<?php
namespace Grok\Socket;

/**
 * Adapter Class
 * 
 * Framework-specific integrations.
 * Why: Ease integration into existing apps.
 * From: Laravel service provider pattern.
 * Benefits: Plug-and-play; developer control over config.
 * Usage: Adapter::laravel($server) in Laravel boot.
 */
class Adapter {
    /**
     * Laravel integration.
     * Why: Bind to container, artisan command.
     * Benefits: Seamless with Laravel queues/events.
     * @param Server $server Server instance
     */
    public static function laravel(Server $server): void {
        // Example: In AppServiceProvider::boot()
        // app()->singleton('grok.socket', fn() => $server);
        // Artisan command: php artisan websocket:serve => $server->run();
        // For DB: Use Laravel's DB facade in events.
        echo "Laravel Adapter: Register in config/services.php\n";
    }

    /**
     * Standalone server.
     * @return Server New server
     */
    public static function standalone(): Server {
        return new Server();
    }
}