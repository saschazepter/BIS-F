<?php

namespace App\DataProviders;

use InvalidArgumentException;

class DataProviderFactory
{
    /**
     * @template T of DataProviderInterface
     * @param class-string<T> $class
     *
     * @return T
     * @throws InvalidArgumentException
     */
    public static function createDataProvider(string $class) {
        $self = new self();

        return $self->create($class);
    }

    /**
     * @template T of DataProviderInterface
     * @param class-string<T> $class
     *
     * @return T
     * @throws InvalidArgumentException
     */
    public function create(string $class) {
        $this->checkClass($class);

        return new $class();
    }

    private function checkClass(string $class): void {
        // check instance of DataProviderInterface
        if (!class_implements($class, DataProviderInterface::class)) {
            throw new InvalidArgumentException('Class must implement DataProviderInterface');
        }
    }
}
