<?php

namespace App\Http\Requests\Profile;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AccountDeletionRequest extends FormRequest
{
    protected $errorBag = 'deletion';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'current_password'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                // La plateforme doit toujours garder au moins un administrateur
                if ($this->user()->isAdmin() && User::where('role', 'admin')->count() <= 1) {
                    $validator->errors()->add('account', 'Impossible de supprimer le dernier compte administrateur. Nommez d\'abord un autre administrateur.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Saisissez votre mot de passe pour confirmer la suppression.',
            'password.current_password' => 'Le mot de passe est incorrect.',
        ];
    }
}
