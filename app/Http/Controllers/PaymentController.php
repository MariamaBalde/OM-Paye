<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Http\Requests\VerifyCodeRequest;
use App\Http\Resources\TransactionResource;
use App\Jobs\ProcessTransactionJob;
use App\Jobs\SendNotificationJob;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    use ApiResponseTrait;

    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Initiate merchant payment
     */
    public function initiate(PaymentRequest $request): JsonResponse
    {
        // Vérification des autorisations
        if (!Gate::allows('pay-merchant')) {
            return $this->errorResponse('Vous n\'avez pas l\'autorisation d\'effectuer des paiements.', 403);
        }

        try {
            $transaction = $this->transactionService->initiatePayment(
                $request->validated(),
                auth()->id()
            );

            // Dispatch jobs pour traitement asynchrone
            ProcessTransactionJob::dispatch($transaction->id)->onQueue('transactions');
            SendNotificationJob::dispatch($transaction->id, 'transaction_initiated')->onQueue('notifications');

            return $this->successResponse(
                ['transaction_id' => $transaction->id],
                'Paiement initié. Veuillez saisir le code de vérification.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Verify and complete payment
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
                new TransactionResource($transaction->load(['emetteur', 'marchand'])),
                'Paiement validé avec succès'
            );
        } catch (\Exception $e) {
            SendNotificationJob::dispatch($request->transaction_id, 'transaction_failed')->onQueue('notifications');
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}