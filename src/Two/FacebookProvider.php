<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace OnixSystemsPHP\HyperfSocialite\Two;

use Hyperf\Collection\Arr;

class FacebookProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The base Facebook Graph URL.
     */
    protected string $graphUrl = 'https://graph.facebook.com';

    /**
     * The Graph API version for the request.
     */
    protected string $version = 'v3.3';

    /**
     * The user fields being requested.
     */
    protected array $fields = ['name', 'email', 'gender', 'verified', 'link'];

    /**
     * The scopes being requested.
     */
    protected array $scopes = ['email'];

    /**
     * Display the dialog in a popup view.
     */
    protected bool $popup = false;

    /**
     * Re-request a declined permission.
     */
    protected bool $reRequest = false;

    /**
     * The access token that was last used to retrieve a user.
     */
    protected ?string $lastToken;

    public function getAccessTokenResponse(string $code): array
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'form_params' => $this->getTokenFields($code),
        ]);

        $data = json_decode((string) $response->getBody(), true);

        return Arr::add($data, 'expires_in', Arr::pull($data, 'expires'));
    }

    /**
     * Set the user fields to request from Facebook.
     *
     * @return $this
     */
    public function fields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Set the dialog to be displayed as a popup.
     *
     * @return $this
     */
    public function asPopup(): self
    {
        $this->popup = true;

        return $this;
    }

    /**
     * Re-request permissions which were previously declined.
     *
     * @return $this
     */
    public function reRequest(): self
    {
        $this->reRequest = true;

        return $this;
    }

    /**
     * Get the last access token used.
     */
    public function lastToken(): ?string
    {
        return $this->lastToken;
    }

    /**
     * Specify which graph version should be used.
     *
     * @return $this
     */
    public function usingGraphVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    protected function getAuthUrl(?string $state): string
    {
        return $this->buildAuthUrlFromBase('https://www.facebook.com/' . $this->version . '/dialog/oauth', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->graphUrl . '/' . $this->version . '/oauth/access_token';
    }

    protected function getUserByToken(string $token): array
    {
        $this->lastToken = $token;

        $meUrl = $this->graphUrl . '/' . $this->version . '/me?access_token=' . $token . '&fields=' . implode(',', $this->fields);

        if (! empty($this->clientSecret)) {
            $appSecretProof = hash_hmac('sha256', $token, $this->clientSecret);

            $meUrl .= '&appsecret_proof=' . $appSecretProof;
        }

        $response = $this->getHttpClient()->get($meUrl, [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    protected function mapUserToObject(array $user): User
    {
        $avatarUrl = $this->graphUrl . '/' . $this->version . '/' . $user['id'] . '/picture';

        return (new User())->setRaw($user)->map([
            'id' => (string) $user['id'],
            'nickname' => null,
            'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
            'avatar' => $avatarUrl . '?type=normal',
            'avatar_original' => $avatarUrl . '?width=1920',
            'profileUrl' => $user['link'] ?? null,
        ]);
    }

    protected function getCodeFields(?string $state = null): array
    {
        $fields = parent::getCodeFields($state);

        if ($this->popup) {
            $fields['display'] = 'popup';
        }

        if ($this->reRequest) {
            $fields['auth_type'] = 'rerequest';
        }

        return $fields;
    }
}
