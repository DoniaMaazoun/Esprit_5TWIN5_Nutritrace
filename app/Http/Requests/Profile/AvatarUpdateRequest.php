<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class AvatarUpdateRequest extends FormRequest
{
    protected $errorBag = 'avatar';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'Choisissez une photo à envoyer.',
            'avatar.file' => 'Le fichier envoyé est invalide.',
            'avatar.uploaded' => 'L\'envoi de la photo a échoué (2 Mo maximum).',
            'avatar.image' => 'Le fichier doit être une image (JPG, PNG ou WebP).',
            'avatar.mimes' => 'Formats acceptés : JPG, PNG ou WebP.',
            'avatar.max' => 'La photo ne doit pas dépasser 2 Mo.',
        ];
    }
}
