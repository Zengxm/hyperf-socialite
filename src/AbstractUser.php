<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Imee\HyperfSocialite;

use Imee\HyperfSocialite\Contracts\User;

abstract class AbstractUser implements \ArrayAccess, User
{
    /**
     * The unique identifier for the user.
     *
     * @var mixed
     */
    public string $id;

    /**
     * The user's nickname / username.
     */
    public ?string $nickname;

    /**
     * The user's full name.
     */
    public ?string $name;

    /**
     * The user's e-mail address.
     */
    public ?string $email;

    /**
     * The user's avatar image URL.
     */
    public ?string $avatar;

    /**
     * The user's raw attributes.
     */
    public array $user;

    /**
     * Get the unique identifier for the user.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the nickname / username for the user.
     */
    public function getNickname(): string
    {
        return is_null($this->nickname) ? '' : $this->nickname;
    }

    /**
     * Get the full name of the user.
     */
    public function getName(): string
    {
        return is_null($this->name) ? '' : $this->name;
    }

    /**
     * Get the e-mail address of the user.
     */
    public function getEmail(): string
    {
        return is_null($this->email) ? '' : $this->email;
    }

    /**
     * Get the avatar / image URL for the user.
     */
    public function getAvatar(): string
    {
        return is_null($this->avatar) ? '' : $this->avatar;
    }

    /**
     * Get the raw user array.
     */
    public function getRaw(): array
    {
        return $this->user;
    }

    /**
     * Set the raw user array from the provider.
     *
     * @return $this
     */
    public function setRaw(array $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Map the given array onto the user's properties.
     *
     * @return $this
     */
    public function map(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    /**
     * Determine if the given raw user attribute exists.
     *
     * @param string $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->user);
    }

    /**
     * Get the given key from the raw user.
     *
     * @param string $offset
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->user[$offset];
    }

    /**
     * Set the given attribute on the raw user array.
     *
     * @param string $offset
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->user[$offset] = $value;
    }

    /**
     * Unset the given value from the raw user array.
     *
     * @param string $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->user[$offset]);
    }
}
