<?php

namespace App\Exceptions;

/**
 * Exception levée lorsqu'un utilisateur n'a pas les autorisations nécessaires
 */
class UnauthorizedException extends ApiException
{
    public function __construct(string $message = 'Accès non autorisé')
    {
        parent::__construct($message, 403, 'UNAUTHORIZED');
    }
}