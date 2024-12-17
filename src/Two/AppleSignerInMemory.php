<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Imee\HyperfSocialite\Two;

use Lcobucci\JWT\Signer\Key;

final class AppleSignerInMemory implements Key
{
    public string $contents;

    public string $passphrase;

    /** @param  non-empty-string  $contents */
    private function __construct(string $contents, string $passphrase)
    {
        $this->passphrase = $passphrase;
        $this->contents = $contents;
    }

    /** @param  non-empty-string  $contents */
    public static function plainText(string $contents, string $passphrase = ''): self
    {
        return new self($contents, $passphrase);
    }

    public function contents(): string
    {
        return $this->contents;
    }

    public function passphrase(): string
    {
        return $this->passphrase;
    }
}
