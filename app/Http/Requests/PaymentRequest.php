<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
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
            'code_marchand' => 'required|string|exists:marchands,code_marchand',
            'montant' => 'required|numeric|min:100|max:100000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code_marchand.required' => 'Le code marchand est obligatoire.',
            'code_marchand.exists' => 'Ce marchand n\'existe pas dans Orange Money.',
            'montant.required' => 'Le montant est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant minimum est de 100 FCFA.',
            'montant.max' => 'Le montant maximum pour un paiement est de 100 000 FCFA.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = auth()->user();
            $montant = $this->input('montant');

            if ($user && $user->solde_total < $montant) {
                $validator->errors()->add('montant', 'Solde insuffisant pour effectuer ce paiement.');
            }

            // Vérifier que le marchand est actif
            $codeMarchand = $this->input('code_marchand');
            $marchand = \App\Models\Marchand::where('code_marchand', $codeMarchand)->first();
            if ($marchand && $marchand->statut !== 'actif') {
                $validator->errors()->add('code_marchand', 'Ce marchand n\'est pas disponible pour le moment.');
            }
        });
    }
}
