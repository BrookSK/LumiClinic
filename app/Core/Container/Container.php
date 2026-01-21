<?php

declare(strict_types=1);

namespace App\Core\Container;

final class Container
{
    /** @var array<string, callable(self):mixed> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    public function set(string $id, callable $factory): void
    {
        $this->bindings[$id] = $factory;
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (!array_key_exists($id, $this->bindings)) {
            throw new \RuntimeException("Service not found: {$id}");
        }

        $this->instances[$id] = ($this->bindings[$id])($this);

        return $this->instances[$id];
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->instances) || array_key_exists($id, $this->bindings);
    }
}
