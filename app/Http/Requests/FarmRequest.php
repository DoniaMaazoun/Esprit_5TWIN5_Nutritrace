<?php

namespace App\Http\Requests;

use App\Models\Farm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FarmRequest extends FormRequest
{
    public function authorize(): bool
    {
        $farm = $this->route('farm');

        return $farm === null || $this->user()->can('update', $farm);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->name),
            'city' => trim((string) $this->city),
        ]);
    }

    public function rules(): array
    {
        $farm = $this->route('farm');
        // Unicité par propriétaire (l'admin peut modifier la ferme d'un producteur)
        $ownerId = $farm?->user_id ?? $this->user()->id;

        return [
            'name' => [
                'required', 'string', 'min:3', 'max:120',
                'regex:/^[\pL\pN\s\'\-\.]+$/u',
                Rule::unique('farms')->where('user_id', $ownerId)->ignore($farm?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'governorate' => ['required', Rule::in(Farm::GOVERNORATES)],
            'city' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[\pL\s\'\-]+$/u'],
            'address' => ['nullable', 'string', 'max:255'],
            // Coordonnées limitées au territoire tunisien
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:30.2,37.6'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:7.5,11.6'],
            'total_area' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'farming_type' => ['required', Rule::in(array_keys(Farm::FARMING_TYPES))],
            'irrigation_type' => ['required', Rule::in(array_keys(Farm::IRRIGATION_TYPES))],
            'soil_type' => ['nullable', Rule::in(array_keys(Farm::SOIL_TYPES))],
            'main_crops' => ['nullable', 'string', 'max:255'],
            // Règle métier : une exploitation biologique doit déclarer son organisme certificateur
            'certification' => ['nullable', 'required_if:farming_type,organic', 'string', 'max:100'],
            'established_year' => ['nullable', 'integer', 'min:1900', 'max:' . now()->year],
            'status' => ['required', Rule::in(array_keys(Farm::STATUSES))],
        ];
    }

    /**
     * Règle métier : la surface totale ne peut pas être inférieure
     * à la plus grande parcelle déjà déclarée dans une récolte.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $farm = $this->route('farm');
                if (!$farm || $validator->errors()->has('total_area')) {
                    return;
                }

                $largestPlot = $farm->largestPlotArea();
                if ($largestPlot > 0 && (float) $this->total_area < $largestPlot) {
                    $validator->errors()->add(
                        'total_area',
                        "La surface ne peut pas être inférieure à la plus grande parcelle déclarée ({$largestPlot} ha)."
                    );
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom de l\'exploitation',
            'governorate' => 'gouvernorat',
            'city' => 'ville',
            'address' => 'adresse',
            'total_area' => 'surface totale',
            'farming_type' => 'type d\'agriculture',
            'irrigation_type' => 'type d\'irrigation',
            'soil_type' => 'type de sol',
            'main_crops' => 'cultures principales',
            'certification' => 'certification',
            'established_year' => 'année de création',
            'status' => 'statut',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Le champ :attribute est obligatoire.',
            'in' => 'La valeur choisie pour :attribute est invalide.',
            'numeric' => 'Le champ :attribute doit être un nombre.',
            'integer' => 'Le champ :attribute doit être un nombre entier.',
            'max.string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
            'min.string' => 'Le champ :attribute doit contenir au moins :min caractères.',
            'name.regex' => 'Le nom ne peut contenir que des lettres, chiffres, espaces, tirets, points et apostrophes.',
            'name.unique' => 'Ce producteur a déjà une exploitation portant ce nom.',
            'city.regex' => 'La ville ne peut contenir que des lettres, espaces, tirets et apostrophes.',
            'latitude.between' => 'La latitude doit être située en Tunisie (entre 30.2 et 37.6).',
            'longitude.between' => 'La longitude doit être située en Tunisie (entre 7.5 et 11.6).',
            'latitude.required_with' => 'Indiquez la latitude et la longitude ensemble.',
            'longitude.required_with' => 'Indiquez la latitude et la longitude ensemble.',
            'total_area.min' => 'La surface doit être supérieure à 0 ha.',
            'total_area.max' => 'La surface ne peut pas dépasser 100 000 ha.',
            'certification.required_if' => 'Une exploitation biologique doit indiquer son organisme certificateur.',
            'established_year.min' => 'L\'année de création doit être postérieure à 1900.',
            'established_year.max' => 'L\'année de création ne peut pas être dans le futur.',
        ];
    }
}
