<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Trait global pour la définition du format de réponse API
 * Standardise toutes les réponses de l'application
 */
trait ApiResponseTrait
{
    /**
     * Return a successful JSON response with pagination
     */
    protected function successResponse($data = null, string $message = 'Opération réussie', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data instanceof LengthAwarePaginator) {
            $response['data'] = $data->items();
            $response['pagination'] = [
                'currentPage' => $data->currentPage(),
                'totalPages' => $data->lastPage(),
                'totalItems' => $data->total(),
                'itemsPerPage' => $data->perPage(),
                'hasNext' => $data->hasMorePages(),
                'hasPrevious' => $data->currentPage() > 1,
            ];
            $response['links'] = [
                'self' => $data->url($data->currentPage()),
                'first' => $data->url(1),
                'last' => $data->url($data->lastPage()),
            ];

            if ($data->hasMorePages()) {
                $response['links']['next'] = $data->url($data->currentPage() + 1);
            }

            if ($data->currentPage() > 1) {
                $response['links']['previous'] = $data->url($data->currentPage() - 1);
            }
        } elseif ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response.
     */
    protected function errorResponse(string $message = 'Une erreur est survenue', int $statusCode = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
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
     * Return a paginated response (legacy method for backward compatibility)
     */
    protected function paginatedResponse($paginator, string $message = 'Données récupérées'): JsonResponse
    {
        return $this->successResponse($paginator, $message);
    }
}