<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Imee\HyperfSocialite\Two;

use Closure;
use Firebase\JWT\JWK;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Hyperf\Collection\Arr;
use Hyperf\Context\ApplicationContext;
use Hyperf\Stringable\Str;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Psr\SimpleCache\CacheInterface;

class AppleProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'APPLE';

    private const URL = 'https://appleid.apple.com';

    protected array $scopes = [
        'name',
        'email',
    ];

    protected int $encodingType = PHP_QUERY_RFC3986;

    /**
     * The separating character for the requested scopes.
     */
    protected string $scopeSeparator = ' ';

    public function getAuthUrl(?string $state): string
    {
        return $this->buildAuthUrlFromBase(self::URL . '/auth/authorize', $state);
    }

    public function getAccessTokenResponse(string $code): array
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => ['Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)],
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Verify Apple jwt.
     *
     * @param string $jwt
     *
     * @return bool
     *
     * @see https://appleid.apple.com/auth/keys
     */
    public static function verify($jwt)
    {
        $jwtContainer = Configuration::forSymmetricSigner(
            new AppleSignerNone(),
            AppleSignerInMemory::plainText('')
        );
        $token = $jwtContainer->parser()->parse($jwt);

        $data = self::cacheRemember('socialite:Apple-JWKSet', 5 * 60, function () {
            $response = (new Client())->get(self::URL . '/auth/keys');

            return json_decode((string) $response->getBody(), true);
        });

        $publicKeys = JWK::parseKeySet($data);
        $kid = $token->headers()->get('kid');

        if (isset($publicKeys[$kid])) {
            $publicKey = openssl_pkey_get_details($publicKeys[$kid]->getKeyMaterial());
            $constraints = [
                new SignedWith(new Sha256(), InMemory::plainText($publicKey['key'])),
                new IssuedBy(self::URL),
                new LooseValidAt(SystemClock::fromSystemTimezone()),
            ];

            try {
                $jwtContainer->validator()->assert($token, ...$constraints);

                return true;
            } catch (RequiredConstraintsViolated $e) {
                throw new \InvalidArgumentException($e->getMessage());
            }
        }

        throw new \InvalidArgumentException('Invalid JWT Signature');
    }

    public function user(): User
    {
        // Temporary fix to enable stateless
        $response = $this->getAccessTokenResponse($this->getCode());

        $appleUserToken = $this->getUserByToken(
            $token = Arr::get($response, 'id_token')
        );

        if ($this->usesState()) {
            $state = explode('.', $appleUserToken['nonce'])[1];
            if ($state === $this->request->input('state')) {
                $this->request->session()->put('state', $state);
                $this->request->session()->put('state_verify', $state);
            }

            if ($this->hasInvalidState()) {
                throw new \InvalidArgumentException();
            }
        }

        $user = $this->mapUserToObject($appleUserToken);

        if ($user instanceof User) {
            $user->setAccessTokenResponseBody($response);
        }

        return $user->setToken($token)
            ->setRefreshToken(Arr::get($response, 'refresh_token'))
            ->setExpiresIn(Arr::get($response, 'expires_in'));
    }

    protected function getCode(): string
    {
        $code = $this->request->input('identifier');
        if (empty($code)) {
            throw new InvalidCodeException();
        }

        return $code;
    }

    protected function getTokenUrl(): string
    {
        return self::URL . '/auth/token';
    }

    protected function getCodeFields(?string $state = null): array
    {
        $fields = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'scope' => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
            'response_type' => 'code',
            'response_mode' => 'form_post',
        ];

        if ($this->usesState()) {
            $fields['state'] = $state;
            $fields['nonce'] = Str::uuid() . '.' . $state;
        }

        return array_merge($fields, $this->parameters);
    }

    protected function getTokenFields(string $code): array
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    protected function getUserByToken(string $token): array
    {
        static::verify($token);
        $claims = explode('.', $token)[1];

        return json_decode(base64_decode($claims), true);
    }

    protected function mapUserToObject(array $user): User
    {
        $userRequest = $this->getUserRequest();

        if (isset($userRequest['name'])) {
            $user['name'] = $userRequest['name'];
            $fullName = trim(
                ($user['name']['firstName'] ?? '')
                . ' '
                . ($user['name']['lastName'] ?? '')
            );
        }

        return (new User())
            ->setRaw($user)
            ->map([
                'id' => $user['sub'],
                'name' => $fullName ?? null,
                'email' => $user['email'] ?? null,
            ]);
    }

    private static function cacheRemember($key, $ttl, \Closure $callback)
    {
        $cache = ApplicationContext::getContainer()->get(CacheInterface::class);

        $value = $cache->get($key);

        // If the item exists in the cache we will just return this immediately and if
        // not we will execute the given Closure and cache the result of that for a
        // given number of seconds so it's available for all subsequent requests.
        if (! is_null($value)) {
            return $value;
        }

        $cache->set($key, $value = $callback(), \Hyperf\Support\value($ttl));

        return $value;
    }

    private function getUserRequest(): array
    {
        $value = $this->request->input('user');

        if (is_array($value)) {
            return $value;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return [];
        }

        return json_decode($value, true);
    }
}
