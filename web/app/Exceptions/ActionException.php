<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Erro de regra de negócio com mensagem pronta para o usuário e status HTTP sugerido.
 * Os controllers Web convertem em redirect com flash; a API mobile em JSON.
 */
class ActionException extends RuntimeException
{
    public function __construct(string $message, private readonly int $status = 422)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    public static function forbidden(string $message): self
    {
        return new self($message, 403);
    }

    public static function notFound(string $message): self
    {
        return new self($message, 404);
    }

    public static function tooLarge(string $message): self
    {
        return new self($message, 413);
    }
}
