<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception de base pour les erreurs API
 */
class ApiException extends Exception
{
    protected $statusCode;
    protected $errorCode;
    protected $errors;

    public function __construct(
        string $message = 'Une erreur est survenue',
        int $statusCode = 400,
        string $errorCode = 'API_ERROR',
        array $errors = null,
        \Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
        $this->errors = $errors;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }
}