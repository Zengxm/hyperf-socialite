<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Imee\HyperfSocialite\Two;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

class LineProvider extends AbstractProvider implements ProviderInterface
{
    public const IDENTIFIER = 'LINE';

    /**
     * The separating character for the requested scopes.
     */
    protected string $scopeSeparator = ' ';

    /**
     * The scopes being requested.
     */
    protected array $scopes = [
        'openid',
        'profile',
        'email',
    ];

    public function user(): User
    {
        if ($this->hasInvalidState()) {
            throw new InvalidStateException();
        }

        $response = $this->getAccessTokenResponse($this->request->input('token'));

        if ($jwt = $response['id_token'] ?? null) {
            $bodyb64 = explode('.', $jwt)[1];
            $user = $this->mapUserToObject(json_decode(base64_decode(strtr($bodyb64, '-_', '+/')), true));
        } else {
            $user = $this->mapUserToObject($this->getUserByToken(
                $token = $this->parseAccessToken($response)
            ));
        }
        //        $this->credentialsResponseBody = $response;
        //
        //        if ($user instanceof User) {
        //            $user->setAccessTokenResponseBody($this->credentialsResponseBody);
        //        }

        return $user->setToken($this->parseAccessToken($response))
            ->setRefreshToken($this->parseRefreshToken($response))
            ->setExpiresIn($this->parseExpiresIn($response));
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(
            'https://access.line.me/oauth2/v2.1/authorize',
            $state
        );
    }

    /**
     * Get the token URL for the provider.
     */
    protected function getTokenUrl(): string
    {
        return 'https://api.line.me/oauth2/v2.1/token';
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param string $token
     * @throws GuzzleException
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get(
            'https://api.line.me/v2/profile',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Map the raw user array to a Socialite User instance.
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['userId'] ?? $user['sub'] ?? null,
            'nickname' => $user['displayName'] ?? $user['name'] ?? null,
            'name' => $user['displayName'] ?? $user['name'] ?? null,
            'avatar' => $user['pictureUrl'] ?? $user['picture'] ?? null,
            'email' => $user['email'] ?? null,
        ]);
    }

    protected function parseAccessToken($response)
    {
        $data = $response->json();

        return $data['access_token'];
    }

    protected function parseRefreshToken($response)
    {
        $data = $response->json();

        return $data['refresh_token'];
    }

    protected function parseExpiresIn($response)
    {
        $data = $response->json();

        return $data['expires_in'];
    }
}
