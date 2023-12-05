<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace OnixSystemsPHP\HyperfSocialite\Two;

use GuzzleHttp\Client;
use Hyperf\Collection\Arr;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\SessionInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\HttpServer\Request;
use Hyperf\Stringable\Str;
use OnixSystemsPHP\HyperfSocialite\Contracts\Provider as ProviderContract;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

use function Hyperf\Support\make;

abstract class AbstractProvider implements ProviderContract
{
    /**
     * Handle session redirect.
     */
    protected SessionInterface $session;

    /**
     * The HTTP request instance.
     *
     * @var Request
     */
    protected RequestInterface $request;

    /**
     * The HTTP Client instance.
     */
    protected ?Client $httpClient = null;

    /**
     * The client ID.
     */
    protected string $clientId;

    /**
     * The client secret.
     */
    protected string $clientSecret;

    /**
     * The redirect URL.
     */
    protected string $redirectUrl;

    /**
     * The custom parameters to be sent with the request.
     */
    protected array $parameters = [];

    /**
     * The scopes being requested.
     */
    protected array $scopes = [];

    /**
     * The separating character for the requested scopes.
     */
    protected string $scopeSeparator = ',';

    /**
     * The type of the encoding in the query.
     *
     * @var int can be either PHP_QUERY_RFC3986 or PHP_QUERY_RFC1738
     */
    protected int $encodingType = PHP_QUERY_RFC1738;

    /**
     * Indicates if the session state should be utilized.
     */
    protected bool $stateless = false;

    /**
     * Indicates if PKCE should be used.
     */
    protected bool $usesPKCE = false;

    /**
     * The custom Guzzle configuration options.
     */
    protected array $guzzle = [];

    /**
     * The cached user instance.
     */
    protected ?User $user = null;

    /**
     * Create a new provider instance.
     */
    public function __construct(RequestInterface $request, string $clientId, string $clientSecret, string $redirectUrl, array $guzzle = [])
    {
        $this->guzzle = $guzzle;
        $this->request = $request;
        $this->clientId = $clientId;
        $this->redirectUrl = $redirectUrl;
        $this->clientSecret = $clientSecret;
        $this->session = ApplicationContext::getContainer()->get(SessionInterface::class);
    }

    /**
     * Redirect the user of the application to the provider's authentication screen.
     */
    public function redirect(): PsrResponseInterface
    {
        $state = null;

        if ($this->usesState()) {
            $this->session->put('state', $state = $this->getState());
            $this->session->put($state, $this->parameters);
        }

        if ($this->usesPKCE()) {
            $this->session->put('code_verifier', $codeVerifier = $this->getCodeVerifier());
        }

        /** @var ResponseInterface $response */
        $response = make(ResponseInterface::class);

        return $response->redirect($this->getAuthUrl($state));
    }

    public function user(): User
    {
        if ($this->user) {
            return $this->user;
        }

        if ($this->hasInvalidState()) {
            throw new InvalidStateException();
        }

        $response = $this->getAccessTokenResponse($this->getCode());

        $this->user = $this->mapUserToObject($this->getUserByToken(
            $token = Arr::get($response, 'access_token')
        ));

        return $this->user->setToken($token)
            ->setRefreshToken(Arr::get($response, 'refresh_token'))
            ->setExpiresIn(Arr::get($response, 'expires_in'));
    }

    /**
     * Get a Social User instance from a known access token.
     */
    public function userFromToken(string $token): User
    {
        $user = $this->mapUserToObject($this->getUserByToken($token));

        return $user->setToken($token);
    }

    /**
     * Get the access token response for the given code.
     */
    public function getAccessTokenResponse(string $code): array
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Accept' => 'application/json'],
            'form_params' => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Merge the scopes of the requested access.
     *
     * @return $this
     */
    public function scopes(array|string $scopes): self
    {
        $this->scopes = array_unique(array_merge($this->scopes, (array) $scopes));

        return $this;
    }

    /**
     * Set the scopes of the requested access.
     *
     * @return $this
     */
    public function setScopes(array|string $scopes): self
    {
        $this->scopes = array_unique((array) $scopes);

        return $this;
    }

    /**
     * Get the current scopes.
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * Set the redirect URL.
     *
     * @return $this
     */
    public function redirectUrl(string $url): self
    {
        $this->redirectUrl = $url;

        return $this;
    }

    /**
     * Set the Guzzle HTTP client instance.
     *
     * @return $this
     */
    public function setHttpClient(Client $client): self
    {
        $this->httpClient = $client;

        return $this;
    }

    /**
     * Set the request instance.
     *
     * @return $this
     */
    public function setRequest(RequestInterface $request): self
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Indicates that the provider should operate as stateless.
     *
     * @return $this
     */
    public function stateless(): self
    {
        $this->stateless = true;

        return $this;
    }

    /**
     * Set the custom parameters of the request.
     *
     * @return $this
     */
    public function with(array $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Get the authentication URL for the provider.
     */
    abstract protected function getAuthUrl(?string $state): string;

    /**
     * Get the token URL for the provider.
     */
    abstract protected function getTokenUrl(): string;

    /**
     * Get the raw user for the given access token.
     */
    abstract protected function getUserByToken(string $token): array;

    /**
     * Map the raw user array to a Socialite User instance.
     */
    abstract protected function mapUserToObject(array $user): User;

    /**
     * Build the authentication URL for the provider from the given base URL.
     */
    protected function buildAuthUrlFromBase(string $url, ?string $state): string
    {
        return $url . '?' . http_build_query($this->getCodeFields($state), '', '&', $this->encodingType);
    }

    /**
     * Get the GET parameters for the code request.
     */
    protected function getCodeFields(?string $state = null): array
    {
        $fields = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'scope' => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
            'response_type' => 'code',
        ];

        if ($this->usesState()) {
            $fields['state'] = $state;
        }

        if ($this->usesPKCE()) {
            $fields['code_challenge'] = $this->getCodeChallenge();
            $fields['code_challenge_method'] = $this->getCodeChallengeMethod();
        }

        return array_merge($fields, $this->parameters);
    }

    /**
     * Format the given scopes.
     */
    protected function formatScopes(array $scopes, string $scopeSeparator): string
    {
        return implode($scopeSeparator, $scopes);
    }

    /**
     * Determine if the current request / session has a mismatching "state".
     */
    protected function hasInvalidState(): bool
    {
        if ($this->isStateless()) {
            return false;
        }

        $state = $this->session->get('state');
        $this->session->remove('state');

        return ! (strlen($state) > 0 && $this->request->input('state') === $state);
    }

    /**
     * Get the POST fields for the token request.
     */
    protected function getTokenFields(string $code): array
    {
        $fields = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUrl,
        ];

        if ($this->usesPKCE()) {
            $fields['code_verifier'] = $this->session->get('code_verifier');
            $this->session->remove('code_verifier');
        }

        return $fields;
    }

    /**
     * Get the code from the request.
     */
    protected function getCode(): string
    {
        $code = $this->request->input('code');
        if (empty($code)) {
            throw new InvalidCodeException();
        }

        return $code;
    }

    /**
     * Get a instance of the Guzzle HTTP client.
     */
    protected function getHttpClient(): Client
    {
        if (is_null($this->httpClient)) {
            $this->httpClient = new Client($this->guzzle);
        }

        return $this->httpClient;
    }

    /**
     * Determine if the provider is operating with state.
     */
    protected function usesState(): bool
    {
        return ! $this->stateless;
    }

    /**
     * Determine if the provider is operating as stateless.
     */
    protected function isStateless(): bool
    {
        return $this->stateless;
    }

    /**
     * Get the string used for session state.
     */
    protected function getState(): string
    {
        return Str::random(40);
    }

    /**
     * Determine if the provider uses PKCE.
     */
    protected function usesPKCE(): bool
    {
        return $this->usesPKCE;
    }

    /**
     * Enables PKCE for the provider.
     *
     * @return $this
     */
    protected function enablePKCE(): self
    {
        $this->usesPKCE = true;

        return $this;
    }

    /**
     * Generates a random string of the right length for the PKCE code verifier.
     */
    protected function getCodeVerifier(): string
    {
        return Str::random(96);
    }

    /**
     * Generates the PKCE code challenge based on the PKCE code verifier in the session.
     */
    protected function getCodeChallenge(): string
    {
        $hashed = hash('sha256', $this->session->get('code_verifier'), true);

        return rtrim(strtr(base64_encode($hashed), '+/', '-_'), '=');
    }

    /**
     * Returns the hash method used to calculate the PKCE code challenge.
     */
    protected function getCodeChallengeMethod(): string
    {
        return 'S256';
    }
}
