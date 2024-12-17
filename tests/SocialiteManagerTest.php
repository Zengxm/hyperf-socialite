<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Imee\HyperfSocialite\Tests;

use Imee\HyperfSocialite\Contracts\Factory;
use Imee\HyperfSocialite\SocialiteServiceProvider;
use Imee\HyperfSocialite\Two\GithubProvider;
use Orchestra\Testbench\TestCase;

/**
 * @internal
 * @coversNothing
 */
class SocialiteManagerTest extends TestCase
{
    public function testItCanInstantiateTheGithubDriver()
    {
        $factory = $this->app->make(Factory::class);

        $provider = $factory->driver('github');

        $this->assertInstanceOf(GithubProvider::class, $provider);
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('services.github', [
            'client_id' => 'github-client-id',
            'client_secret' => 'github-client-secret',
            'redirect' => 'http://your-callback-url',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [SocialiteServiceProvider::class];
    }
}
