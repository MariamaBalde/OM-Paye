<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'telephone' => 'required|string|regex:/^[0-9]{9}$/',
            'code_secret' => 'required|string|min:4|max:6',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'telephone.regex' => 'Le numéro de téléphone doit contenir exactement 9 chiffres.',
            'code_secret.required' => 'Le code secret est obligatoire.',
            'code_secret.min' => 'Le code secret doit contenir au moins 4 caractères.',
            'code_secret.max' => 'Le code secret ne peut pas dépasser 6 caractères.',
        ];
    }
}
