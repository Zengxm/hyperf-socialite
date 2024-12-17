<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Imee\HyperfSocialite\Two;

use Imee\HyperfSocialite\AbstractUser;

class User extends AbstractUser
{
    /**
     * The user's access token.
     */
    public string $token;

    /**
     * The refresh token that can be exchanged for a new access token.
     */
    public ?string $refreshToken;

    /**
     * The number of seconds the access token is valid for.
     */
    public ?int $expiresIn;

    public $accessTokenResponseBody;

    public function setAccessTokenResponseBody(array $accessTokenResponseBody)
    {
        $this->accessTokenResponseBody = $accessTokenResponseBody;

        return $this;
    }

    /**
     * Set the token on the user.
     *
     * @return $this
     */
    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Set the refresh token required to obtain a new access token.
     *
     * @return $this
     */
    public function setRefreshToken(?string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    /**
     * Set the number of seconds the access token is valid for.
     *
     * @return $this
     */
    public function setExpiresIn(?int $expiresIn): self
    {
        $this->expiresIn = $expiresIn;

        return $this;
    }
}
