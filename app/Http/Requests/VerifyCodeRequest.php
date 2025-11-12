<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyCodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'transaction_id' => 'required|exists:transactions,id',
            'code' => 'required|string|regex:/^[0-9]{4}$/',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'transaction_id.required' => 'L\'ID de la transaction est obligatoire.',
            'transaction_id.exists' => 'Cette transaction n\'existe pas.',
            'code.required' => 'Le code de vérification est obligatoire.',
            'code.regex' => 'Le code de vérification doit contenir exactement 4 chiffres.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = auth()->user();
            $transactionId = $this->input('transaction_id');
            $code = $this->input('code');

            // Vérifier que la transaction appartient à l'utilisateur
            $transaction = \App\Models\Transaction::where('id', $transactionId)
                ->where('user_id', $user->id)
                ->first();

            if (!$transaction) {
                $validator->errors()->add('transaction_id', 'Cette transaction ne vous appartient pas.');
                return;
            }

            // Vérifier que la transaction est en attente
            if ($transaction->statut !== 'en_attente') {
                $validator->errors()->add('transaction_id', 'Cette transaction ne nécessite pas de vérification.');
                return;
            }

            // Vérifier le code de vérification
            $verificationCode = \App\Models\VerificationCode::where('transaction_id', $transactionId)
                ->where('user_id', $user->id)
                ->where('code', $code)
                ->where('verifie', false)
                ->where('expire_at', '>', now())
                ->first();

            if (!$verificationCode) {
                $validator->errors()->add('code', 'Code de vérification invalide ou expiré.');
            }
        });
    }
}
