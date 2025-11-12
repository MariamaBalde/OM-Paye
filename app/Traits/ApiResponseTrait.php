<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    /**
     * Return a successful JSON response.
     */
    protected function successResponse($data = null, string $message = 'Opération réussie', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Return an error JSON response.
     */
    protected function errorResponse(string $message = 'Une erreur est survenue', int $statusCode = 400, $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }

    /**
     * Return a validation error response.
     */
    protected function validationErrorResponse($errors, string $message = 'Erreurs de validation'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors);
    }

    /**
     * Return an unauthorized response.
     */
    protected function unauthorizedResponse(string $message = 'Non autorisé'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Return a not found response.
     */
    protected function notFoundResponse(string $message = 'Ressource non trouvée'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Return a paginated response.
     */
    protected function paginatedResponse($paginator, string $message = 'Données récupérées'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }
}