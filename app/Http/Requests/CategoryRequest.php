<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->name),
            'color' => Str::upper(trim((string) $this->color)),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'min:2', 'max:60', 'regex:/^[\pL\s\'\-]+$/u',
                Rule::unique('categories', 'name')->ignore($category?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['required', Rule::in(array_keys(Category::ICONS))],
            'color' => ['required', 'regex:/^#[0-9A-F]{6}$/'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Règles métier : deux catégories ne peuvent pas produire le même slug (ex. « Légumes » / « legumes »),
     * et une catégorie ne peut pas être désactivée si c'est la dernière active.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $category = $this->route('category');

                if (!$validator->errors()->has('name')) {
                    $slugTaken = Category::where('slug', Str::slug($this->name))->when($category, fn ($q) => $q->whereKeyNot($category->id))->exists();
                    if ($slugTaken) {
                        $validator->errors()->add('name', 'Une catégorie au nom équivalent existe déjà.');
                    }
                }

                if ($category?->is_active && !$this->boolean('is_active') && Category::active()->count() <= 1) {
                    $validator->errors()->add('is_active', 'Impossible de désactiver la dernière catégorie active : les producteurs ne pourraient plus déclarer de produit.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nom', 'description' => 'description', 'icon' => 'icône', 'color' => 'couleur'];
    }

    public function messages(): array
    {
        return [
            'required' => 'Le champ :attribute est obligatoire.',
            'name.min' => 'Le nom doit contenir au moins 2 caractères.',
            'name.max' => 'Le nom ne doit pas dépasser 60 caractères.',
            'name.regex' => 'Le nom ne peut contenir que des lettres, espaces, tirets et apostrophes.',
            'name.unique' => 'Cette catégorie existe déjà.',
            'description.max' => 'La description ne doit pas dépasser 500 caractères.',
            'icon.in' => 'Choisissez une icône dans la liste.',
            'color.regex' => 'La couleur doit être au format hexadécimal (ex. : #2D6A4F).',
        ];
    }
}
