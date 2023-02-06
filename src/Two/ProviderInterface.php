<?php

namespace OnixSystemsPHP\HyperfSocialite\Two;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

interface ProviderInterface
{
    /**
     * Redirect the user to the authentication page for the provider.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function redirect(): PsrResponseInterface;

    /**
     * Get the User instance for the authenticated user.
     *
     * @return \OnixSystemsPHP\HyperfSocialite\Two\User
     */
    public function user();
}
