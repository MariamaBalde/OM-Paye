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
     * @OA\Post(
     *     path="/v1/transfers/initiate",
     *     summary="Initiate money transfer",
     *     description="Initiate a money transfer to another account",
     *     tags={"Transactions"},
     *     security={{"passport":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"destinataire_numero","montant"},
     *             @OA\Property(property="destinataire_numero", type="string", example="771234567"),
     *             @OA\Property(property="montant", type="number", format="float", example=5000.00),
     *             @OA\Property(property="description", type="string", example="Transfert pour achat")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transfer initiated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transfert initié. Veuillez saisir le code de vérification."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transaction_id", type="integer", example=123)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
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
     * @OA\Post(
     *     path="/v1/transfers/verify",
     *     summary="Verify and complete transfer",
     *     description="Verify transfer with code and complete the transaction",
     *     tags={"Transactions"},
     *     security={{"passport":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"transaction_id","code"},
     *             @OA\Property(property="transaction_id", type="integer", example=123),
     *             @OA\Property(property="code", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transfer completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transfert validé avec succès"),
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
                new TransactionResource($transaction->load(['emetteur', 'destinataire'])),
                'Transfert validé avec succès'
            );
        } catch (\Exception $e) {
            SendNotificationJob::dispatch($request->transaction_id, 'transaction_failed')->onQueue('notifications');
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}