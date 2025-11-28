<?php
declare(strict_types=1);

namespace Grok\Socket;

use Grok\Socket\EventEmitter;

class SocketNamespace extends EventEmitter
{
	private string $name;
	private array $sockets = [];

	public function __construct(string $name = '/')
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

	public function emit(string $event, ...$args): EventEmitter
	{
		$data = $args[0] ?? null;
		$except = $args[1] ?? null;
		foreach ($this->sockets as $socket) {
			if ($socket === $except) continue;
			$socket->emit($event, $data);
		}
		return $this;
	}

	public function getSockets(): array
	{
		return array_values($this->sockets);
	}
}
