<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Imee\HyperfSocialite\Two;

use GuzzleHttp\RequestOptions;
use Hyperf\Collection\Arr;
use Hyperf\Context\ApplicationContext;
use Psr\Log\LoggerInterface;

class TikTokProvider extends AbstractProvider implements ProviderInterface
{
    public const IDENTIFIER = 'TIKTOK';

    /**
     * The scopes being requested.
     */
    protected array $scopes = [
        'user.info.basic',
    ];

    protected $codeVerifier;

    public function user(): User
    {
        if ($this->user) {
            return $this->user;
        }

        if ($this->hasInvalidState()) {
            throw new InvalidStateException();
        }
        $token = $this->request->input('token');
        $input = explode('/', $token);
        $this->codeVerifier = $input[0];
        $response = $this->getAccessTokenResponse($input[1]);
        if (isset($response['error'])) {
            ApplicationContext::getContainer()->get(LoggerInterface::class)->error('tiktok.getAccessTokenResponse', [$this->request->all(), $response]);
            throw new \Exception($response['error'] . ':' . $response['error_description']);
        }
        $token = Arr::get($response, 'access_token');
        $this->user = $this->mapUserToObject(
            $this->getUserByToken($token)
        );

        return $this->user->setToken($token)
            ->setExpiresIn(Arr::get($response, 'expires_in'))
            ->setRefreshToken(Arr::get($response, 'refresh_token'));
        // ->setApprovedScopes(explode($this->scopeSeparator, Arr::get($response, 'scope', '')));
    }

    /**
     * Get a Social User instance from a known access token.
     *
     * @param string $code
     */
    public function userFromToken($code): User
    {
        $input = explode('/', $code);
        $this->codeVerifier = $input[0];
        $response = $this->getAccessTokenResponse($input[1]);
        if (isset($response['error'])) {
            throw new \Exception($response['error'] . ':' . $response['error_description']);
        }
        $token = Arr::get($response, 'access_token');
        $this->user = $this->mapUserToObject($this->getUserByToken($token));

        return $this->user->setToken($token)
            ->setExpiresIn(Arr::get($response, 'expires_in'))
            ->setRefreshToken(Arr::get($response, 'refresh_token'));
        // ->setApprovedScopes(explode($this->scopeSeparator, Arr::get($response, 'scope', '')));
    }

    public function getTokenUrl(): string
    {
        return 'https://open.tiktokapis.com/v2/oauth/token/';
    }

    protected function getAuthUrl($state): string
    {
        $fields = [
            'client_key' => $this->clientId,
            'state' => $state,
            'response_type' => 'code',
            'scope' => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
            'redirect_uri' => $this->redirectUrl,
        ];

        $fields = array_merge($fields, $this->parameters);

        return 'https://www.tiktok.com/v2/auth/authorize/?' . http_build_query($fields);
    }

    protected function getTokenFields($code): array
    {
        //        $fields = parent::getTokenFields($code);
        //        $fields['client_key'] = $this->clientId;
        //        unset($fields['client_id']);
        return [
            'grant_type' => 'authorization_code',
            'client_key' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUrl,
            'code_verifier' => $this->codeVerifier,
        ];
    }

    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get('https://open.tiktokapis.com/v2/user/info/', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $token,
            ],
            RequestOptions::QUERY => [
                'fields' => 'open_id,union_id,display_name,avatar_url',
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    protected function mapUserToObject($user): User
    {
        $user = $user['data']['user'];

        return (new User())->setRaw($user)->map([
            'id' => $user['open_id'],
            'nickname' => $user['display_name'],
            'union_id' => $user['union_id'] ?? null,
            'name' => $user['display_name'] ?? null,
            'avatar' => $user['avatar_url'],
        ]);
    }

    protected function getTokenHeaders($code): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Cache-Control' => 'no-cache',
        ];
    }
}
