<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Http\Requests\VerifyCodeRequest;
use App\Http\Resources\TransactionResource;
use App\Jobs\ProcessTransactionJob;
use App\Jobs\SendNotificationJob;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * @OA\Tag(
 *     name="Transactions",
 *     description="Gestion des transactions financières"
 * )
 */

class TransferController extends Controller
{
    use ApiResponseTrait;

    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * @OA\Hidden
     */
    public function initiate(TransferRequest $request): JsonResponse
    {
        // Vérification des autorisations
        if (!Gate::allows('transfer-money')) {
            return $this->errorResponse('Vous n\'avez pas l\'autorisation d\'effectuer des transferts.', 403);
        }

        try {
            $transaction = $this->transactionService->initiateTransfer(
                $request->validated(),
                auth()->id()
            );

            // Dispatch jobs pour traitement asynchrone
            ProcessTransactionJob::dispatch($transaction->id)->onQueue('transactions');
            SendNotificationJob::dispatch($transaction->id, 'transaction_initiated')->onQueue('notifications');

            return $this->successResponse(
                ['transaction_id' => $transaction->id],
                'Transfert initié. Veuillez saisir le code de vérification.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Hidden
     */
    public function verify(VerifyCodeRequest $request): JsonResponse
    {
        try {
            $transaction = $this->transactionService->verifyAndCompleteTransaction(
                $request->transaction_id,
                $request->code,
                auth()->id()
            );

            // Notification de succès
            SendNotificationJob::dispatch($transaction->id, 'transaction_completed')->onQueue('notifications');

            return $this->successResponse(
                new TransactionResource($transaction->load(['emetteur', 'destinataire'])),
                'Transfert validé avec succès'
            );
        } catch (\Exception $e) {
            SendNotificationJob::dispatch($request->transaction_id, 'transaction_failed')->onQueue('notifications');
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}