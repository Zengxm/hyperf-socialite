<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Imee\HyperfSocialite\Two;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

interface ProviderInterface
{
    /**
     * Redirect the user to the authentication page for the provider.
     */
    public function redirect(): PsrResponseInterface;

    /**
     * Get the User instance for the authenticated user.
     *
     * @return User
     */
    public function user();
}
