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

class SnapchatProvider extends AbstractProvider implements ProviderInterface
{
    public const IDENTIFIER = 'SNAPCHAT';

    protected array $scopes = [
        'https://auth.snapchat.com/oauth2/api/user.display_name',
        'https://auth.snapchat.com/oauth2/api/user.bitmoji.avatar',
    ];

    protected string $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://accounts.snapchat.com/accounts/oauth2/auth', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://accounts.snapchat.com/accounts/oauth2/token';
    }

    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get('https://kit.snapchat.com/v1/me', [
            RequestOptions::QUERY => [
                'query' => '{me { externalId displayName bitmoji { avatar id } } }',
            ],
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    protected function mapUserToObject(array $user): User
    {
        return (new User())->setRaw($user)->map([
            'id' => Arr::get($user, 'data.me.externalId'),
            'name' => Arr::get($user, 'data.me.displayName'),
            'avatar' => Arr::get($user, 'data.me.bitmoji.avatar'),
        ]);
    }
}
