<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace OnixSystemsPHP\HyperfSocialite\One;

use OnixSystemsPHP\HyperfSocialite\AbstractUser;

class User extends AbstractUser
{
    /**
     * The user's access token.
     */
    public string $token;

    /**
     * The user's access token secret.
     */
    public string $tokenSecret;

    /**
     * Set the token on the user.
     *
     * @return $this
     */
    public function setToken(string $token, string $tokenSecret): self
    {
        $this->token = $token;
        $this->tokenSecret = $tokenSecret;

        return $this;
    }
}
