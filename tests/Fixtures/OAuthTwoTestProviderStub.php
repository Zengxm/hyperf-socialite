<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Imee\HyperfSocialite\Tests\Fixtures;

use GuzzleHttp\Client;
use Imee\HyperfSocialite\Two\AbstractProvider;
use Imee\HyperfSocialite\Two\User;
use Mockery as m;
use Mockery\MockInterface;

class OAuthTwoTestProviderStub extends AbstractProvider
{
    /**
     * @var Client|MockInterface
     */
    public $http;

    protected function getAuthUrl(?string $state): string
    {
        return $this->buildAuthUrlFromBase('http://auth.url', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'http://token.url';
    }

    protected function getUserByToken(string $token): array
    {
        return ['id' => 'foo'];
    }

    protected function mapUserToObject(array $user): User
    {
        return (new User())->map(['id' => $user['id']]);
    }

    /**
     * Get a fresh instance of the Guzzle HTTP client.
     *
     * @return Client|MockInterface
     */
    protected function getHttpClient(): Client
    {
        if ($this->http) {
            return $this->http;
        }

        return $this->http = m::mock(\stdClass::class);
    }
}
