<?php

declare(strict_types=1);

namespace App\Container;

use App\Container\Exception\ContainerException;
use Closure;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

final class Container
{
    private array $factories = [];

    private array $instances = [];

    private array $shared = [];

    public function instance(string $id, object $object): void
    {
        $this->instances[$id] = $object;
    }

    public function bind(string $id, Closure $factory): void
    {
        $this->factories[$id] = $factory;
        $this->shared[$id] = false;
    }

    public function singleton(string $id, Closure $factory): void
    {
        $this->factories[$id] = $factory;
        $this->shared[$id] = true;
    }

    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->factories[$id])) {
            $object = ($this->factories[$id])($this);

            if ($this->shared[$id]) {
                $this->instances[$id] = $object;
            }

            return $object;
        }

        return $this->build($id);
    }

    private function build(string $class): object
    {
        if (!class_exists($class)) {
            throw new ContainerException("Class [{$class}] not found and not registered in the container.");
        }

        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new ContainerException("Class [{$class}] cannot be instantiated directly — register a factory in config/services.php.");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $arguments[] = $this->resolveParameter($parameter, $class);
        }

        return $reflection->newInstanceArgs($arguments);
    }

    private function resolveParameter(ReflectionParameter $parameter, string $class): mixed
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $this->get($type->getName());
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new ContainerException(sprintf(
            'Cannot resolve parameter $%s of constructor [%s]: no class type and no default value.',
            $parameter->getName(),
            $class,
        ));
    }
}
