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

/**
 * @OA\Tag(
 *     name="Transactions",
 *     description="Gestion des transactions financières"
 * )
 */

class PaymentController extends Controller
{
    use ApiResponseTrait;

    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * @OA\Post(
     *     path="/v1/payments/initiate",
     *     summary="Initiate merchant payment",
     *     description="Initiate a payment to a merchant",
     *     tags={"Transactions"},
     *     security={{"passport":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"marchand_id","montant"},
     *             @OA\Property(property="marchand_id", type="integer", example=1),
     *             @OA\Property(property="montant", type="number", format="float", example=2500.00),
     *             @OA\Property(property="description", type="string", example="Paiement pour services")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment initiated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paiement initié. Veuillez saisir le code de vérification."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transaction_id", type="integer", example=124)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
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
     * @OA\Post(
     *     path="/v1/payments/verify",
     *     summary="Verify and complete payment",
     *     description="Verify payment with code and complete the transaction",
     *     tags={"Transactions"},
     *     security={{"passport":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"transaction_id","code"},
     *             @OA\Property(property="transaction_id", type="integer", example=124),
     *             @OA\Property(property="code", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paiement validé avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Transaction")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
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