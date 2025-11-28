<?php
namespace Grok\Socket;

class Room
{
    private string $name;
    private array $sockets = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function add(Socket $socket): void
    {
        $this->sockets[$socket->getId()] = $socket;
    }

    public function remove(Socket $socket): void
    {
        unset($this->sockets[$socket->getId()]);
    }

    public function broadcast($data, ?Socket $exclude = null): void
    {
        foreach ($this->sockets as $socket) {
            if ($socket === $exclude) continue;
            $socket->send($data);
        }
    }
}