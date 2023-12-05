<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace OnixSystemsPHP\HyperfSocialite\One;

use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\SessionInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Server\Server;
use OnixSystemsPHP\HyperfSocialite\Contracts\Provider as ProviderContract;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

use function Hyperf\Support\make;

abstract class AbstractProvider implements ProviderContract
{
    protected SessionInterface $session;

    /**
     * The HTTP request instance.
     */
    protected RequestInterface $request;

    /**
     * The OAuth server implementation.
     */
    protected Server $server;

    /**
     * A hash representing the last requested user.
     */
    protected string $userHash;

    /**
     * Create a new provider instance.
     */
    public function __construct(RequestInterface $request, Server $server)
    {
        $this->server = $server;
        $this->request = $request;
        $this->session = ApplicationContext::getContainer()->get(SessionInterface::class);
    }

    /**
     * Redirect the user to the authentication page for the provider.
     */
    public function redirect(): PsrResponseInterface
    {
        $this->session->put(
            'oauth.temp',
            $temp = $this->server->getTemporaryCredentials()
        );

        $response = make(ResponseInterface::class);
        return $response->redirect($this->server->getAuthorizationUrl($temp));
    }

    /**
     * Get the User instance for the authenticated user.
     *
     * @throws MissingVerifierException
     */
    public function user(): User
    {
        if (! $this->hasNecessaryVerifier()) {
            throw new MissingVerifierException('Invalid request. Missing OAuth verifier.');
        }

        $token = $this->getToken();

        $user = $this->server->getUserDetails(
            $token,
            $this->shouldBypassCache($token->getIdentifier(), $token->getSecret())
        );

        $instance = (new User())->setRaw($user->extra)
            ->setToken($token->getIdentifier(), $token->getSecret());

        return $instance->map([
            'id' => $user->uid,
            'nickname' => $user->nickname,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->imageUrl,
        ]);
    }

    /**
     * Get a Social User instance from a known access token and secret.
     *
     * @param string $token
     * @param string $secret
     */
    public function userFromTokenAndSecret($token, $secret): User
    {
        $tokenCredentials = new TokenCredentials();

        $tokenCredentials->setIdentifier($token);
        $tokenCredentials->setSecret($secret);

        $user = $this->server->getUserDetails(
            $tokenCredentials,
            $this->shouldBypassCache($token, $secret)
        );

        $instance = (new User())->setRaw($user->extra)
            ->setToken($tokenCredentials->getIdentifier(), $tokenCredentials->getSecret());

        return $instance->map([
            'id' => $user->uid,
            'nickname' => $user->nickname,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->imageUrl,
        ]);
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
     * Get the token credentials for the request.
     */
    protected function getToken(): TokenCredentials
    {
        $temp = $this->session->get('oauth.temp');

        if (! $temp) {
            throw new MissingTemporaryCredentialsException('Missing temporary OAuth credentials.');
        }

        return $this->server->getTokenCredentials(
            $temp,
            $this->request->get('oauth_token'),
            $this->request->get('oauth_verifier')
        );
    }

    /**
     * Determine if the request has the necessary OAuth verifier.
     */
    protected function hasNecessaryVerifier(): bool
    {
        return $this->request->has('oauth_token') && $this->request->has('oauth_verifier');
    }

    /**
     * Determine if the user information cache should be bypassed.
     */
    protected function shouldBypassCache(string $token, string $secret): bool
    {
        $newHash = sha1($token . '_' . $secret);

        if (! empty($this->userHash) && $newHash !== $this->userHash) {
            $this->userHash = $newHash;

            return true;
        }

        $this->userHash = $this->userHash ?: $newHash;

        return false;
    }
}
