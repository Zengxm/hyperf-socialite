<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace OnixSystemsPHP\HyperfSocialite\Two;

class GitlabProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The scopes being requested.
     */
    protected array $scopes = ['read_user'];

    /**
     * The separating character for the requested scopes.
     */
    protected string $scopeSeparator = ' ';

    /**
     * The Gitlab instance host.
     */
    protected string $host = 'https://gitlab.com';

    /**
     * Set the Gitlab instance host.
     *
     * @return $this
     */
    public function setHost(?string $host): self
    {
        if (! empty($host)) {
            $this->host = rtrim($host, '/');
        }

        return $this;
    }

    protected function getAuthUrl(?string $state): string
    {
        return $this->buildAuthUrlFromBase($this->host . '/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->host . '/oauth/token';
    }

    protected function getUserByToken(string $token): array
    {
        $userUrl = $this->host . '/api/v3/user?access_token=' . $token;

        $response = $this->getHttpClient()->get($userUrl);

        return json_decode($response->getBody(), true);
    }

    protected function mapUserToObject(array $user): User
    {
        return (new User())->setRaw($user)->map([
            'id' => (string) $user['id'],
            'nickname' => $user['username'],
            'name' => $user['name'],
            'email' => $user['email'],
            'avatar' => $user['avatar_url'],
        ]);
    }
}
