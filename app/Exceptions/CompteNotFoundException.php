<?php

namespace App\Exceptions;

/**
 * Exception levée lorsqu'un compte n'est pas trouvé
 */
class CompteNotFoundException extends ApiException
{
    public function __construct(string $numeroCompte = null)
    {
        $message = $numeroCompte
            ? "Le compte {$numeroCompte} n'existe pas ou n'est pas accessible."
            : "Le compte demandé n'existe pas ou n'est pas accessible.";

        parent::__construct($message, 404, 'COMPTE_NOT_FOUND');
    }
}