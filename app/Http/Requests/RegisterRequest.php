<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'telephone' => [
                'required',
                'regex:/^[0-9]{9}$/',
                'unique:users,telephone'
            ],
            'code_secret' => [
                'required',
                'regex:/^[0-9]{4}$/'
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'telephone.regex' => 'Le numéro de téléphone doit contenir exactement 9 chiffres.',
            'telephone.unique' => 'Ce numéro de téléphone est déjà enregistré.',
            'code_secret.required' => 'Le code secret est obligatoire.',
            'code_secret.regex' => 'Le code secret doit contenir exactement 4 chiffres.',
        ];
    }
}