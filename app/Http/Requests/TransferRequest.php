<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
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
            'destinataire_numero' => 'required|string|regex:/^[0-9]{9}$/|exists:users,telephone',
            'montant' => 'required|numeric|min:100|max:500000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'destinataire_numero.required' => 'Le numéro du destinataire est obligatoire.',
            'destinataire_numero.regex' => 'Le numéro du destinataire doit contenir exactement 9 chiffres.',
            'destinataire_numero.exists' => 'Ce numéro de téléphone n\'est pas enregistré dans Orange Money.',
            'montant.required' => 'Le montant est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant minimum est de 100 FCFA.',
            'montant.max' => 'Le montant maximum est de 500 000 FCFA.',
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

            if ($user && $user->solde_fcfa < $montant) {
                $validator->errors()->add('montant', 'Solde insuffisant pour effectuer ce transfert.');
            }

            // Vérifier que l'utilisateur ne se transfère pas à lui-même
            $destinataireNumero = $this->input('destinataire_numero');
            if ($user && $user->telephone === $destinataireNumero) {
                $validator->errors()->add('destinataire_numero', 'Vous ne pouvez pas vous transférer de l\'argent à vous-même.');
            }
        });
    }
}
