<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected $errorBag = 'profile';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->name),
            'email' => mb_strtolower(trim((string) $this->email)),
            'company_name' => trim((string) $this->company_name),
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->user()->id)],
        ];

        // Champ réservé aux professionnels (le matricule fiscal et le type restent validés par l'admin)
        if ($this->user()->isPro()) {
            $rules['company_name'] = ['required', 'string', 'min:2', 'max:255'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'name.min' => 'Le nom doit contenir au moins 2 caractères.',
            'name.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'L\'adresse e-mail doit être valide.',
            'email.max' => 'L\'adresse e-mail ne doit pas dépasser 255 caractères.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée par un autre compte.',
            'company_name.required' => 'Le nom de l\'établissement est obligatoire pour un compte professionnel.',
            'company_name.min' => 'Le nom de l\'établissement doit contenir au moins 2 caractères.',
            'company_name.max' => 'Le nom de l\'établissement ne doit pas dépasser 255 caractères.',
        ];
    }
}
