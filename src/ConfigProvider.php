<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace OnixSystemsPHP\HyperfSocialite;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                \OnixSystemsPHP\HyperfSocialite\Contracts\Factory::class => \OnixSystemsPHP\HyperfSocialite\SocialiteManager::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for socialite.',
                    'source' => __DIR__ . '/../publish/socialite.php',
                    'destination' => BASE_PATH . '/config/socialite.php',
                ],
            ],
        ];
    }
}
