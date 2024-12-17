<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Imee\HyperfSocialite\One;

class TwitterProvider extends AbstractProvider
{
    public function user(): User
    {
        if (! $this->hasNecessaryVerifier()) {
            throw new MissingVerifierException('Invalid request. Missing OAuth verifier.');
        }

        $user = $this->server->getUserDetails($token = $this->getToken(), $this->shouldBypassCache($token->getIdentifier(), $token->getSecret()));

        $extraDetails = [
            'location' => $user->location,
            'description' => $user->description,
        ];

        $instance = (new User())->setRaw(array_merge($user->extra, $user->urls, $extraDetails))
            ->setToken($token->getIdentifier(), $token->getSecret());

        return $instance->map([
            'id' => $user->uid,
            'nickname' => $user->nickname,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->imageUrl,
            'avatar_original' => str_replace('_normal', '', $user->imageUrl),
        ]);
    }
}
