<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Imee\HyperfSocialite;

use Hyperf\Collection\Arr;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Stringable\Str;
use Imee\HyperfSocialite\One\TwitterProvider;
use Imee\HyperfSocialite\Two\AppleProvider;
use Imee\HyperfSocialite\Two\BitbucketProvider;
use Imee\HyperfSocialite\Two\FacebookProvider;
use Imee\HyperfSocialite\Two\GithubProvider;
use Imee\HyperfSocialite\Two\GitlabProvider;
use Imee\HyperfSocialite\Two\GoogleProvider;
use Imee\HyperfSocialite\Two\LineProvider;
use Imee\HyperfSocialite\Two\LinkedInProvider;
use Imee\HyperfSocialite\Two\TikTokProvider;
use League\OAuth1\Client\Server\Twitter as TwitterServer;

class SocialiteManager extends Manager implements Contracts\Factory
{
    /**
     * Get a driver instance.
     */
    public function with(string $driver): mixed
    {
        return $this->driver($driver);
    }

    /**
     * Build an OAuth 2 provider instance.
     */
    public function buildProvider(string $provider, array $config): Two\AbstractProvider
    {
        return new $provider(
            $this->container->make(RequestInterface::class),
            $config['client_id'],
            $config['client_secret'],
            $this->formatRedirectUrl($config),
            Arr::get($config, 'guzzle', [])
        );
    }

    /**
     * Format the server configuration.
     */
    public function formatConfig(array $config): array
    {
        return array_merge([
            'identifier' => $config['client_id'],
            'secret' => $config['client_secret'],
            'callback_uri' => $this->formatRedirectUrl($config),
        ], $config);
    }

    /**
     * Get the default driver name.
     *
     * @throws \InvalidArgumentException
     */
    public function getDefaultDriver(): string
    {
        throw new \InvalidArgumentException('No Socialite driver was specified.');
    }

    /**
     * Create an instance of the specified driver.
     */
    protected function createGithubDriver(): Two\AbstractProvider
    {
        $config = $this->config->get('socialite.github');

        return $this->buildProvider(
            GithubProvider::class,
            $config
        );
    }

    /**
     * Create an instance of the specified driver.
     */
    protected function createFacebookDriver(): Two\AbstractProvider
    {
        $config = $this->config->get('socialite.facebook');

        return $this->buildProvider(
            FacebookProvider::class,
            $config
        );
    }

    /**
     * Create an instance of the specified driver.
     */
    protected function createGoogleDriver(): Two\AbstractProvider
    {
        $config = $this->config->get('socialite.google');

        return $this->buildProvider(
            GoogleProvider::class,
            $config
        );
    }

    /**
     * Create an instance of the specified driver.
     */
    protected function createAppleDriver(): AppleProvider
    {
        $config = $this->config->get('socialite.apple');

        return $this->buildProvider(
            AppleProvider::class,
            $config
        );
    }

    /**
     * Create an instance of the specified driver.
     */
    protected function createLinkedinDriver(): Two\AbstractProvider
    {
        $config = $this->config->get('socialite.linkedin');

        return $this->buildProvider(
            LinkedInProvider::class,
            $config
        );
    }

    /**
     * Create an instance of the specified driver.
     */
    protected function createBitbucketDriver(): Two\AbstractProvider
    {
        $config = $this->config->get('socialite.bitbucket');

        return $this->buildProvider(
            BitbucketProvider::class,
            $config
        );
    }

    /**
     * Create an instance of the specified driver.
     */
    protected function createGitlabDriver(): Two\AbstractProvider
    {
        $config = $this->config->get('socialite.gitlab');

        return $this->buildProvider(
            GitlabProvider::class,
            $config
        )->setHost($config['host'] ?? null);
    }

    /**
     * Create an instance of the specified driver.
     */
    protected function createTwitterDriver(): One\AbstractProvider
    {
        $config = $this->config->get('socialite.twitter');

        return new TwitterProvider(
            $this->container->make('request'),
            new TwitterServer($this->formatConfig($config))
        );
    }

    /**
     * Create an instance of the specified driver.
     */
    protected function createTiktokDriver(): Two\AbstractProvider
    {
        $config = $this->config->get('socialite.tiktok');

        return $this->buildProvider(
            TikTokProvider::class,
            $config
        );
    }

    /**
     * Create an instance of the specified driver.
     */
    protected function createLineDriver(): Two\AbstractProvider
    {
        $config = $this->config->get('socialite.line');

        return $this->buildProvider(
            LineProvider::class,
            $config
        );
    }

    /**
     * Format the callback URL, resolving a relative URI if needed.
     */
    protected function formatRedirectUrl(array $config): string
    {
        $redirect = $config['redirect'];

        return Str::startsWith($redirect ?? '', '/')
            ? $redirect
            : '';
    }
}
