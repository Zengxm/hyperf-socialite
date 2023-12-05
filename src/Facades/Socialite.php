<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace OnixSystemsPHP\HyperfSocialite\Facades;

use OnixSystemsPHP\HyperfSocialite\Contracts\Factory;
use OnixSystemsPHP\HyperfSocialite\Contracts\Provider;

use function Hyperf\Support\call;
use function Hyperf\Support\make;

/**
 * @method static Provider driver(string $driver = null)
 * @method static Provider with(string $driver = null)
 * @method static Provider buildProvider(string $provider, array $config)
 * @see \OnixSystemsPHP\HyperfSocialite\SocialiteManager
 */
class Socialite
{
    protected Factory $manager;

    public function __construct()
    {
        $this->manager = make(Factory::class);
    }

    public function __call($name, $arguments)
    {
        return call([$this->manager, $name], $arguments);
    }

    public static function __callStatic($name, $arguments)
    {
        return (new static())->{$name}(...$arguments);
    }
}
