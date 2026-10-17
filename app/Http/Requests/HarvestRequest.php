<?php

namespace App\Http\Requests;

use App\Models\Farm;
use App\Models\Harvest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class HarvestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $harvest = $this->route('harvest');

        return $harvest === null || $this->user()->can('update', $harvest);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['product_name' => trim((string) $this->product_name)]);
    }

    public function rules(): array
    {
        // L'exploitation doit appartenir au propriétaire du lot
        // (le producteur connecté, ou le producteur du lot quand l'admin le modifie)
        $ownerId = $this->route('harvest')?->farm->user_id ?? $this->user()->id;

        return [
            'farm_id' => ['required', Rule::exists('farms', 'id')->where('user_id', $ownerId)],
            'product_name' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[\pL\pN\s\'\-]+$/u'],
            // Catégorie active (ou catégorie actuelle du produit si elle a été désactivée depuis)
            'category_id' => ['required', Rule::exists('categories', 'id')->where(fn ($q) => $q
                ->where('is_active', true)
                ->when($this->route('harvest'), fn ($w, $h) => $w->orWhere('id', $h->category_id)))],
            'variety' => ['nullable', 'string', 'max:100'],
            'plot_name' => ['nullable', 'string', 'max:100'],
            'plot_area' => ['nullable', 'numeric', 'min:0.01'],
            'planting_date' => ['nullable', 'date', 'before:harvest_date'],
            'harvest_date' => ['required', 'date', 'before_or_equal:today', 'after:2000-01-01'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'unit' => ['required', Rule::in(array_keys(Harvest::UNITS))],
            'quality_grade' => ['required', Rule::in(array_keys(Harvest::QUALITY_GRADES))],
            'treatments' => ['nullable', 'string', 'max:1000'],
            'storage_conditions' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'public_description' => ['nullable', 'string', 'min:20', 'max:1500'],
            'status' => ['required', Rule::in(array_keys(Harvest::STATUSES))],
            // Photo du produit : JPG/PNG/WebP, 2 Mo max, au moins 200×200 px
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=200,min_height=200'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Données du lot sans les champs de fichier (traités par Harvest::syncImage).
     */
    public function harvestData(): array
    {
        return $this->safe()->except(['image', 'remove_image']);
    }

    /**
     * Règles métier dépendant de l'exploitation choisie.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('farm_id')) {
                    return;
                }

                $farm = Farm::find($this->farm_id);
                $harvest = $this->route('harvest');

                // Une nouvelle récolte ne peut être déclarée que sur une exploitation active
                // (en modification, on tolère l'exploitation d'origine passée en jachère)
                $isSameFarm = $harvest && $harvest->farm_id === $farm->id;
                if ($farm->status !== 'active' && !$isSameFarm) {
                    $validator->errors()->add('farm_id', "L'exploitation « {$farm->name} » est " . mb_strtolower($farm->statusLabel()) . " : impossible d'y déclarer une récolte.");
                }

                // La parcelle doit tenir dans l'exploitation
                if ($this->filled('plot_area') && !$validator->errors()->has('plot_area') && (float) $this->plot_area > $farm->total_area) {
                    $validator->errors()->add('plot_area', "La parcelle ne peut pas dépasser la surface de l'exploitation ({$farm->total_area} ha).");
                }

                // La récolte ne peut pas précéder la création de l'exploitation
                if ($farm->established_year && !$validator->errors()->has('harvest_date')
                    && (int) date('Y', strtotime($this->harvest_date)) < $farm->established_year) {
                    $validator->errors()->add('harvest_date', "La récolte ne peut pas être antérieure à la création de l'exploitation ({$farm->established_year}).");
                }

                // Biologique : les traitements de synthèse sont interdits
                if ($farm->farming_type === 'organic' && $this->filled('treatments')
                    && preg_match('/\b(glyphosate|chlorpyrifos|n[ée]onicotino[iï]de|imidaclopride|synth[ée]se)\b/iu', $this->treatments)) {
                    $validator->errors()->add('treatments', 'Ce traitement est interdit en agriculture biologique.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'farm_id' => 'exploitation',
            'product_name' => 'produit',
            'category_id' => 'catégorie',
            'variety' => 'variété',
            'plot_name' => 'parcelle',
            'plot_area' => 'surface de la parcelle',
            'planting_date' => 'date de plantation',
            'harvest_date' => 'date de récolte',
            'quantity' => 'quantité',
            'unit' => 'unité',
            'quality_grade' => 'qualité',
            'treatments' => 'traitements',
            'storage_conditions' => 'conditions de stockage',
            'status' => 'statut',
            'image' => 'photo',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Le champ :attribute est obligatoire.',
            'in' => 'La valeur choisie pour :attribute est invalide.',
            'numeric' => 'Le champ :attribute doit être un nombre.',
            'date' => 'Le champ :attribute doit être une date valide.',
            'max.string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
            'min.string' => 'Le champ :attribute doit contenir au moins :min caractères.',
            'category_id.exists' => 'Cette catégorie n\'existe pas ou a été désactivée par l\'administrateur.',
            'farm_id.exists' => 'Exploitation introuvable ou n\'appartenant pas au producteur de ce lot.',
            'product_name.regex' => 'Le nom du produit ne peut contenir que des lettres, chiffres, espaces, tirets et apostrophes.',
            'plot_area.min' => 'La surface de la parcelle doit être supérieure à 0 ha.',
            'planting_date.before' => 'La date de plantation doit précéder la date de récolte.',
            'harvest_date.before_or_equal' => 'La date de récolte ne peut pas être dans le futur.',
            'harvest_date.after' => 'La date de récolte doit être postérieure à l\'an 2000.',
            'quantity.gt' => 'La quantité doit être strictement positive.',
            'quantity.max' => 'La quantité ne peut pas dépasser 1 000 000.',
            'public_description.min' => 'La fiche consommateur doit contenir au moins 20 caractères.',
            'public_description.max' => 'La fiche consommateur ne doit pas dépasser 1500 caractères.',
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'Formats acceptés : JPG, PNG ou WebP.',
            'image.max' => 'L\'image ne doit pas dépasser 2 Mo.',
            'image.dimensions' => 'L\'image doit mesurer au moins 200 × 200 pixels.',
            'image.uploaded' => 'Le téléversement de l\'image a échoué (2 Mo maximum).',
        ];
    }
}
