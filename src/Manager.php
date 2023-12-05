<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace OnixSystemsPHP\HyperfSocialite;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Stringable\Str;
use OnixSystemsPHP\HyperfSocialite\Contracts\Provider;

abstract class Manager
{
    /**
     * The container instance.
     */
    protected ContainerInterface $container;

    /**
     * The configuration repository instance.
     */
    protected ConfigInterface $config;

    /**
     * The registered custom driver creators.
     */
    protected array $customCreators = [];

    /**
     * The array of created "drivers".
     */
    protected array $drivers = [];

    /**
     * Create a new manager instance.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->config = $container->get(ConfigInterface::class);
    }

    /**
     * Dynamically call the default driver instance.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->driver()->{$method}(...$parameters);
    }

    /**
     * Get the default driver name.
     */
    abstract public function getDefaultDriver(): string;

    /**
     * Get a driver instance.
     *
     * @throws \InvalidArgumentException
     */
    public function driver(null|string $driver = null): Provider
    {
        $driver = $driver ?: $this->getDefaultDriver();

        if (is_null($driver)) {
            throw new \InvalidArgumentException(sprintf(
                'Unable to resolve NULL driver for [%s].',
                static::class
            ));
        }

        // If the given driver has not been created before, we will create the instances
        // here and cache it so we can return it next time very quickly. If there is
        // already a driver created by this name, we'll just return that instance.
        if (! isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->createDriver($driver);
        }

        return $this->drivers[$driver];
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @return $this
     */
    public function extend(string $driver, \Closure $callback): self
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Get all of the created "drivers".
     */
    public function getDrivers(): array
    {
        return $this->drivers;
    }

    /**
     * Create a new driver instance.
     *
     * @throws \InvalidArgumentException
     */
    protected function createDriver(string $driver): mixed
    {
        // First, we will determine if a custom driver creator exists for the given driver and
        // if it does not we will check for a creator method for the driver. Custom creator
        // callbacks allow developers to build their own "drivers" easily using Closures.
        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver);
        }
        $method = 'create' . Str::studly($driver) . 'Driver';

        if (method_exists($this, $method)) {
            return $this->{$method}();
        }

        throw new \InvalidArgumentException("Driver [{$driver}] not supported.");
    }

    /**
     * Call a custom driver creator.
     */
    protected function callCustomCreator(string $driver): mixed
    {
        return $this->customCreators[$driver]($this->container);
    }
}
