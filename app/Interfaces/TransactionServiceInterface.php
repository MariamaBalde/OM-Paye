<?php

namespace App\Interfaces;

interface TransactionServiceInterface
{
    public function calculateTransferFees(float $montant): float;
    public function calculatePaymentFees(float $montant): float;
    public function generateVerificationCode(): string;
    public function initiateTransfer(array $data, int $userId): \App\Models\Transaction;
    public function initiatePayment(array $data, int $userId): \App\Models\Transaction;
    public function verifyAndCompleteTransaction(int $transactionId, string $code, int $userId): \App\Models\Transaction;
}