<?php

namespace Cloakr\Client\Commands\Support;

class CloakrToken
{

    protected bool $hasError = false;
    protected string $errorMessage = '';

    public function __construct(protected ?string $token, protected bool $isValid = false, protected bool $isPro = false)
    {
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function isInvalid(): bool
    {
        return !$this->isValid;
    }

    public function isPro(): bool
    {
        return $this->isPro;
    }

    public static function invalid(string $token): self
    {
        return new self($token, false, false);
    }

    public static function valid(string $token): self
    {
        return new self($token, true, false);
    }

    public static function pro(string $token): self
    {
        return new self($token, true, true);
    }

    public function hasError(): bool
    {
        return $this->hasError;
    }

    public function getError(): string
    {
        return $this->errorMessage;
    }

    public function setError(string $errorMessage): self
    {
        $this->hasError = true;
        $this->errorMessage = $errorMessage;
        return $this;
    }
}
