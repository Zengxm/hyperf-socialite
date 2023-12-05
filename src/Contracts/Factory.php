<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace OnixSystemsPHP\HyperfSocialite\Contracts;

interface Factory
{
    /**
     * Get an OAuth provider implementation.
     */
    public function driver(null|string $driver = null): Provider;

    /**
     * Make an OAuth provider implementation.
     */
    public function buildProvider(string $provider, array $config): Provider;
}
