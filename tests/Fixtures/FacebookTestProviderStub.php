<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Imee\HyperfSocialite\Tests\Fixtures;

use GuzzleHttp\Client;
use Imee\HyperfSocialite\Two\FacebookProvider;
use Mockery as m;
use Mockery\MockInterface;

class FacebookTestProviderStub extends FacebookProvider
{
    /**
     * @var Client|MockInterface
     */
    public $http;

    protected function getUserByToken(string $token): array
    {
        return ['id' => 'foo'];
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
