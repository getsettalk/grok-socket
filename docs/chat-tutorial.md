# Chat App Tutorial

## Step 1: Basic Server Setup
```php
$server = new Server();
$server->setEncryptionKey(env('ENCRYPT_KEY'));
$server->run();