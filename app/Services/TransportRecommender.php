<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\TransportMode;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Module 3 - Recommandation du mode de transport le moins polluant.
 *
 * Tous les chiffres (CO2 = distance x tonnes x facteur ADEME, durées, faisabilité) sont calculés
 * ici, pour des modes uniques et des plans multimodaux (camion -> train -> utilitaire...).
 * L'IA (service AiAssistant du module 1, même fournisseur et même clé) choisit parmi les
 * options possibles en tenant compte de la nature du produit, explique son choix et donne
 * des conseils. Sans clé API, ou si sa réponse est inutilisable, des règles simples prennent le relais.
 */
class TransportRecommender
{
    /**
     * Hypothèses par code de mode : vitesse moyenne (km/h), temps de manutention (h),
     * charge utile maximale (kg, null = pas de limite pratique) et livraison porte-à-porte.
     * Train et bateau ne desservent que des gares ou des ports : ils n'apparaissent que dans les plans multimodaux.
     */
    private const PROFILES = [
        'truck_heavy' => ['speed' => 60, 'handling' => 2, 'max_kg' => null, 'door_to_door' => true],
        'truck_light' => ['speed' => 60, 'handling' => 1, 'max_kg' => 19000, 'door_to_door' => true],
        'van' => ['speed' => 70, 'handling' => 0.5, 'max_kg' => 1500, 'door_to_door' => true],
        'rail' => ['speed' => 40, 'handling' => 12, 'max_kg' => null, 'door_to_door' => false],
        'river' => ['speed' => 12, 'handling' => 24, 'max_kg' => null, 'door_to_door' => false],
        'sea' => ['speed' => 25, 'handling' => 48, 'max_kg' => null, 'door_to_door' => false],
        'air' => ['speed' => 600, 'handling' => 12, 'max_kg' => null, 'door_to_door' => true],
    ];

    /** Profil utilisé pour un mode ajouté par l'admin avec un code inconnu */
    private const DEFAULT_PROFILE = ['speed' => 50, 'handling' => 2, 'max_kg' => null, 'door_to_door' => true];

    /** Distance minimale (km) pour qu'un plan multimodal ait du sens */
    private const MAIN_HAUL_MIN_KM = ['rail' => 150, 'river' => 150, 'sea' => 300];

    /** Pré- et post-acheminement routier des plans multimodaux (km, plafonnés à 10 % du trajet) */
    private const FIRST_MILE_KM = 30;

    private const LAST_MILE_KM = 20;

    /** Camion de référence pour calculer le gain, par ordre de préférence */
    private const REFERENCE_TRUCKS = ['truck_light', 'truck_heavy'];

    /**
     * Nature des produits : délai maximal conseillé (h), durée de conservation (jours)
     * et besoin de froid. Pour un lot de production, sa durée de conservation (DLC) prime ;
     * sinon les mots-clés du nom du produit, puis la catégorie de la récolte d'origine.
     */
    private const PRODUCT_CLASSES = [
        'perishable' => ['label' => 'Périssable', 'max_hours' => 24, 'shelf_days' => 7, 'refrigerated' => true],
        'semi' => ['label' => 'Se conserve quelques semaines', 'max_hours' => 72, 'shelf_days' => 21, 'refrigerated' => false],
        'durable' => ['label' => 'Se conserve longtemps', 'max_hours' => null, 'shelf_days' => null, 'refrigerated' => false],
        'unknown' => ['label' => 'Nature non précisée', 'max_hours' => null, 'shelf_days' => null, 'refrigerated' => false],
    ];

    private const CATEGORY_CLASSES = [
        'legumes' => 'perishable', 'fruits' => 'perishable', 'plantes-aromatiques' => 'perishable',
        'agrumes' => 'semi',
        'olives' => 'durable', 'dattes' => 'durable', 'cereales' => 'durable', 'legumineuses' => 'durable',
    ];

    /** Durée de conservation d'un produit transformé (jours entre production et DLC) : seuils des classes */
    private const SHELF_LIFE_CLASSES = ['perishable' => 10, 'semi' => 60];

    private const PRODUCT_KEYWORDS = [
        'durable' => ['huile', 'olive', 'datte', 'ble', 'orge', 'avoine', 'cereale', 'semoule', 'farine', 'pois chiche',
            'lentille', 'feve seche', 'amande', 'noix', 'pistache', 'miel', 'sec', 'seche'],
        'semi' => ['orange', 'citron', 'mandarine', 'clementine', 'pomme de terre', 'oignon', 'ail', 'grenade', 'pomme', 'courge'],
        'perishable' => ['tomate', 'fraise', 'salade', 'laitue', 'peche', 'abricot', 'melon', 'pasteque', 'raisin', 'figue',
            'cerise', 'poivron', 'piment', 'courgette', 'concombre', 'aubergine', 'epinard', 'menthe', 'persil', 'coriandre',
            'lait', 'fromage', 'poisson', 'viande', 'oeuf', 'fruit', 'legume'],
    ];

    public function __construct(private AiAssistant $ai) {}

    /**
     * @return array|null null si aucun mode de transport actif ne permet de livrer
     */
    public function recommend(float $distanceKm, float $weightKg, ?float $maxHours = null, ?Shipment $shipment = null): ?array
    {
        $product = $this->describeProduct($shipment);
        $limit = $this->effectiveLimit($maxHours, $product);

        $options = $this->evaluateOptions($distanceKm, $weightKg, $limit['hours']);
        if ($options->isEmpty()) {
            return null;
        }
        $feasible = $options->where('feasible', true);
        $reference = $this->reference($options);
        $tips = $this->localTips($options, $product, $limit, $maxHours, $weightKg, $shipment);

        $aiChoice = $feasible->isNotEmpty()
            ? $this->askAi($distanceKm, $weightKg, $maxHours, $product, $limit, $options, $reference, $tips)
            : null;

        if ($aiChoice) {
            $recommended = $feasible->firstWhere('key', $aiChoice['key']);
            $explanation = $aiChoice['explanation'];
            $tips = $aiChoice['tips'] ?: $tips;
            $source = 'ai';
        } else {
            // Mode de secours : l'option possible la moins émettrice,
            // ou la plus rapide si aucune ne respecte le délai
            $recommended = $feasible->isNotEmpty()
                ? $feasible->sortBy('co2_kg')->first()
                : $options->where('fits_capacity', true)->sortBy('hours')->first() ?? $options->sortBy('hours')->first();
            $explanation = $this->localExplanation($recommended, $reference, $product, $limit, $feasible->isNotEmpty());
            $source = 'local';
        }

        $saving = $reference ? round($reference['co2_kg'] - $recommended['co2_kg'], 3) : null;

        return [
            'source' => $source,
            'product' => $product,
            'max_hours' => $limit['hours'],
            'recommended' => $recommended,
            'explanation' => $explanation,
            'tips' => array_values(array_slice($tips, 0, 2)),
            'reference' => $reference,
            'saving_kg' => $saving,
            'saving_percent' => $reference && $reference['co2_kg'] > 0
                ? round($saving / $reference['co2_kg'] * 100, 1)
                : null,
            'options' => $options->values()->all(),
        ];
    }

    // ==================== NATURE DU PRODUIT ====================

    /**
     * Classe un produit à partir de son nom (prioritaire) puis de la catégorie du lot (module 1)
     */
    public function classifyProduct(?string $name, ?string $categorySlug = null): string
    {
        $normalized = ' '.Str::lower(Str::ascii((string) $name)).' ';

        foreach (self::PRODUCT_KEYWORDS as $class => $keywords) {
            foreach ($keywords as $keyword) {
                if (preg_match('/\b'.preg_quote($keyword, '/').'s?\b/', $normalized)) {
                    return $class;
                }
            }
        }

        return self::CATEGORY_CLASSES[$categorySlug] ?? 'unknown';
    }

    /**
     * Nature d'un produit transformé d'après sa durée de conservation (production -> DLC)
     */
    public function classifyShelfLife(int $shelfDays): string
    {
        foreach (self::SHELF_LIFE_CLASSES as $class => $maxDays) {
            if ($shelfDays <= $maxDays) {
                return $class;
            }
        }

        return 'durable';
    }

    /**
     * Nature du produit transporté : elle vient du lot de production (produit transformé, module 2).
     * Les récoltes d'origine (module 1), si on peut les retrouver, sont gardées pour information
     * et servent de dernier recours pour la catégorie.
     */
    private function describeProduct(?Shipment $shipment): array
    {
        $lot = $shipment?->productionLot;
        $lot?->loadMissing('recipe.items', 'processor');
        $origins = $shipment ? $shipment->originHarvests() : collect();
        $origin = $origins->first();

        $name = $shipment?->product_name ?: $lot?->getProductName();
        $production = $lot?->production_date;
        $expiry = $lot?->expiry_date;
        $shelfDays = $production && $expiry ? (int) max(0, $production->diffInDays($expiry)) : null;
        $daysToExpiry = $expiry ? (int) floor(now()->startOfDay()->diffInDays($expiry->copy()->startOfDay(), false)) : null;

        if ($shelfDays !== null) {
            $class = $this->classifyShelfLife($shelfDays);
            $classBy = 'expiry';
        } else {
            $class = $this->classifyProduct($name, $origin?->category?->slug);
            $classBy = $class === 'unknown' ? null : 'name';
        }
        $rules = self::PRODUCT_CLASSES[$class];

        // Le lot ne doit pas arriver périmé : le délai est plafonné par les jours restants avant la DLC
        $maxHours = $rules['max_hours'];
        if ($daysToExpiry !== null && ($maxHours !== null || $daysToExpiry <= 7)) {
            $maxHours = max(6, min($maxHours ?? PHP_INT_MAX, $daysToExpiry * 24));
        }

        return [
            'name' => $name,
            'category' => $origin?->category?->name,
            'lot_code' => $lot?->qr_code_data,
            'processor' => $lot ? ($lot->processor->company_name ?: $lot->processor->name) : null,
            'production_date' => $production?->format('d/m/Y'),
            'expiry_date' => $expiry?->format('d/m/Y'),
            'age_days' => $production ? (int) max(0, $production->diffInDays(now())) : null,
            'shelf_days' => $shelfDays,
            'days_to_expiry' => $daysToExpiry,
            'expired' => $daysToExpiry !== null && $daysToExpiry < 0,
            'origins' => $origins->map(fn ($harvest) => [
                'batch_code' => $harvest->batch_code,
                'product_name' => $harvest->product_name,
                'category' => $harvest->category?->name,
                'harvest_date' => $harvest->harvest_date?->format('d/m/Y'),
                'farm' => $harvest->farm?->name,
            ])->values()->all(),
            'class' => $class,
            'class_by' => $classBy,
            'label' => $rules['label'],
            'max_hours' => $maxHours,
            'refrigerated' => $rules['refrigerated'],
        ];
    }

    /**
     * Délai retenu : le plus court entre celui du distributeur et celui imposé par le produit
     *
     * @return array{hours: ?float, by: ?string}
     */
    private function effectiveLimit(?float $maxHours, array $product): array
    {
        $productHours = $product['max_hours'];

        if ($productHours !== null && ($maxHours === null || $productHours < $maxHours)) {
            return ['hours' => (float) $productHours, 'by' => 'product'];
        }

        return ['hours' => $maxHours, 'by' => $maxHours !== null ? 'user' : null];
    }

    // ==================== OPTIONS DE TRANSPORT ====================

    /**
     * Modes uniques porte-à-porte et plans multimodaux, du moins au plus émetteur
     */
    public function evaluateOptions(float $distanceKm, float $weightKg, ?float $maxHours = null): Collection
    {
        $modes = TransportMode::where('is_active', true)->get()->keyBy('code');
        $options = collect();

        foreach ($modes as $mode) {
            if ($this->profile($mode)['door_to_door']) {
                $options->push($this->option('mode_'.$mode->code, $mode->name, [$this->leg($mode, $distanceKm, $weightKg)], $maxHours));
            }
        }

        foreach (self::MAIN_HAUL_MIN_KM as $code => $minKm) {
            $main = $modes->get($code);
            $first = $this->roadMode($modes, $weightKg, false);
            $last = $this->roadMode($modes, $weightKg, true);
            if (! $main || ! $first || ! $last || $distanceKm < $minKm) {
                continue;
            }

            $firstKm = min(self::FIRST_MILE_KM, $distanceKm * 0.1);
            $lastKm = min(self::LAST_MILE_KM, $distanceKm * 0.1);
            $legs = [
                $this->leg($first, $firstKm, $weightKg),
                $this->leg($main, $distanceKm - $firstKm - $lastKm, $weightKg),
                $this->leg($last, $lastKm, $weightKg),
            ];
            $name = collect($legs)->pluck('name')->map(fn ($n) => Str::before($n, ' ('))->implode(' → ');
            $options->push($this->option('plan_'.$code, $name, $legs, $maxHours));
        }

        return $options->sortBy('co2_kg')->values();
    }

    private function option(string $key, string $name, array $legs, ?float $maxHours): array
    {
        $hours = round(array_sum(array_column($legs, 'hours')), 1);
        $fitsCapacity = collect($legs)->every('fits_capacity');
        $fitsDelay = $maxHours === null || $hours <= $maxHours;

        return [
            'key' => $key,
            'type' => count($legs) > 1 ? 'plan' : 'mode',
            // Mode de la première étape : c'est lui que le bouton « Utiliser » sélectionne
            'id' => $legs[0]['id'],
            'code' => $legs[0]['code'],
            'name' => $name,
            'legs' => $legs,
            'co2_kg' => round(array_sum(array_column($legs, 'co2_kg')), 3),
            'hours' => $hours,
            'fits_capacity' => $fitsCapacity,
            'fits_delay' => $fitsDelay,
            'feasible' => $fitsCapacity && $fitsDelay,
        ];
    }

    private function leg(TransportMode $mode, float $distanceKm, float $weightKg): array
    {
        $profile = $this->profile($mode);

        return [
            'id' => $mode->id,
            'code' => $mode->code,
            'name' => $mode->name,
            'distance_km' => round($distanceKm, 1),
            'emission_factor' => (float) $mode->emission_factor,
            'co2_kg' => round($distanceKm * ($weightKg / 1000) * (float) $mode->emission_factor, 3),
            'hours' => round($distanceKm / $profile['speed'] + $profile['handling'], 1),
            'fits_capacity' => $profile['max_kg'] === null || $weightKg <= $profile['max_kg'],
        ];
    }

    private function profile(TransportMode $mode): array
    {
        return self::PROFILES[$mode->code] ?? self::DEFAULT_PROFILE;
    }

    /**
     * Mode routier pour le pré- ou post-acheminement : utilitaire en dernier kilomètre
     * si le poids le permet, sinon camion porteur puis poids lourd
     */
    private function roadMode(Collection $modes, float $weightKg, bool $lastMile): ?TransportMode
    {
        $order = $lastMile ? ['van', 'truck_light', 'truck_heavy'] : ['truck_light', 'truck_heavy'];

        foreach ($order as $code) {
            $mode = $modes->get($code);
            if ($mode && ($this->profile($mode)['max_kg'] === null || $weightKg <= $this->profile($mode)['max_kg'])) {
                return $mode;
            }
        }

        return null;
    }

    private function reference(Collection $options): ?array
    {
        foreach (self::REFERENCE_TRUCKS as $code) {
            if ($option = $options->firstWhere('key', 'mode_'.$code)) {
                return $option;
            }
        }

        return null;
    }

    // ==================== CONSEILS ====================

    /**
     * Conseils chiffrés calculés localement (fournis à l'IA comme faits, et utilisés en secours)
     *
     * @return array<int, string>
     */
    private function localTips(Collection $options, array $product, array $limit, ?float $maxHours, float $weightKg, ?Shipment $shipment): array
    {
        $tips = [];
        $kg = fn (float $v) => number_format($v, 1, ',', ' ').' kg CO2e';
        $best = $options->where('feasible', true)->sortBy('co2_kg')->first();
        $greener = $options->where('fits_capacity', true)->sortBy('co2_kg')->first();

        // Un délai plus souple permettrait une option moins émettrice
        if ($limit['by'] === 'user' && $greener && ! $greener['fits_delay'] && (! $best || $greener['co2_kg'] < $best['co2_kg'])) {
            $neededHours = (int) ceil($greener['hours']);
            $gain = ($best['co2_kg'] ?? 0) - $greener['co2_kg'];
            $tips[] = "Avec un délai de {$neededHours} h au lieu de {$this->hours($maxHours)} h, l'option {$greener['name']} serait possible"
                .($best ? " et économiserait {$kg($gain)} (".round($gain / max($best['co2_kg'], 0.001) * 100).' %).' : '.');
        }

        if ($product['expired']) {
            $tips[] = "Attention : le lot a dépassé sa date limite ({$product['expiry_date']}). Vérifiez avec le transformateur avant d'expédier.";
        }

        if ($product['refrigerated']) {
            $tips[] = 'Produit périssable'.($product['age_days'] !== null ? " fabriqué il y a {$product['age_days']} jour(s)" : '')
                ." : prévoyez un camion frigorifique (2 à 8 °C) et une livraison sous {$this->hours($limit['hours'])} h.";
        } elseif ($limit['by'] === 'product' && $product['class'] === 'semi') {
            $tips[] = "{$product['name']} se conserve quelques semaines : une livraison sous {$this->hours($product['max_hours'])} h suffit, inutile de payer l'express.";
        } elseif ($limit['by'] === 'product' && ! $product['expired'] && $product['expiry_date']) {
            $tips[] = "Date limite du lot le {$product['expiry_date']} : livrez sous {$this->hours($product['max_hours'])} h pour qu'il reste vendable.";
        }

        if ($shipment) {
            $siblings = Shipment::where('user_id', $shipment->user_id)
                ->whereKeyNot($shipment->id)
                ->where('status', 'planned')
                ->whereNotNull('point_of_sale_id')
                ->where('point_of_sale_id', $shipment->point_of_sale_id)
                ->pluck('reference');
            if ($siblings->isNotEmpty()) {
                $tips[] = 'Regroupez avec '.$siblings->implode(', ')." vers {$shipment->pointOfSale->name} : un seul trajet au lieu de ".($siblings->count() + 1).'.';
            }
        }

        $van = $options->firstWhere('key', 'mode_van');
        $heavy = $options->firstWhere('key', 'mode_truck_heavy');
        if ($weightKg < 1000 && $van && $heavy && $heavy['legs'][0]['emission_factor'] > 0) {
            $ratio = round($van['legs'][0]['emission_factor'] / $heavy['legs'][0]['emission_factor']);
            $tips[] = "Chargement léger ({$this->hours($weightKg)} kg) : par tonne transportée, un utilitaire émet {$ratio} fois plus qu'un poids lourd. "
                .'Regrouper plusieurs lots vers la même ville réduit fortement le CO2 par tonne.';
        }

        return $tips;
    }

    private function hours(?float $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 1, ',', ''), '0'), ',');
    }

    // ==================== IA ====================

    /**
     * Demande à l'IA de choisir parmi les options possibles. Retourne null si l'IA est
     * indisponible ou si sa réponse ne désigne pas une option possible.
     *
     * @return array{key: string, explanation: string, tips: array<int, string>}|null
     */
    private function askAi(float $distanceKm, float $weightKg, ?float $maxHours, array $product, array $limit, Collection $options, ?array $reference, array $tips): ?array
    {
        if (! $this->ai->isEnabled()) {
            return null;
        }

        $table = $options->map(fn ($o) => sprintf(
            '- [%s] %s : %s kg CO2e, environ %s h%s%s',
            $o['key'],
            $o['name'],
            $o['co2_kg'],
            $o['hours'],
            $o['type'] === 'plan'
                ? ' (étapes : '.collect($o['legs'])->map(fn ($l) => "{$l['name']} {$l['distance_km']} km")->implode(', ').')'
                : '',
            $o['feasible'] ? '' : ' [IMPOSSIBLE : '.(! $o['fits_capacity'] ? 'capacité dépassée' : 'délai dépassé').']'
        ))->implode("\n");

        $productFacts = 'Produit : '.($product['name'] ?: 'non précisé')
            .($product['lot_code'] ? " (produit transformé, lot {$product['lot_code']}" : ($product['processor'] ? ' (produit transformé' : ''))
            .($product['processor'] ? ", transformateur {$product['processor']}" : '')
            .($product['lot_code'] || $product['processor'] ? ')' : '')
            .", nature : {$product['label']}"
            .($product['age_days'] !== null ? ", fabriqué il y a {$product['age_days']} jour(s)" : '')
            .($product['days_to_expiry'] !== null ? ", date limite dans {$product['days_to_expiry']} jour(s)" : '')
            .($product['refrigerated'] ? ', transport réfrigéré nécessaire' : '').'.'
            .($product['origins']
                ? ' Matières premières : '.collect($product['origins'])->map(fn ($o) => "{$o['product_name']}"
                    .($o['category'] ? " ({$o['category']})" : '')
                    .($o['harvest_date'] ? ", récolte du {$o['harvest_date']}" : ''))->implode(' ; ').'.'
                : '');

        $system = 'Tu es expert en logistique alimentaire bas carbone pour une plateforme tunisienne de traçabilité. '
            .'Choisis la meilleure option parmi celles qui ne sont pas marquées IMPOSSIBLE. Pour un produit périssable, '
            .'privilégie la rapidité et la chaîne du froid ; pour un produit qui se conserve, privilégie le CO2 le plus bas, '
            .'y compris un plan multimodal. Reste réaliste (desserte ferroviaire ou portuaire). '
            .'N\'invente aucun chiffre : utilise seulement ceux fournis. '
            .'Réponds uniquement en JSON : {"choice": "<clé de l\'option, sans crochets, ex. plan_rail>", '
            .'"explanation": "<2 à 3 phrases en français>", "tips": ["<1 ou 2 conseils concrets>"]}.';
        $facts = "Expédition : {$distanceKm} km, {$weightKg} kg"
            .($maxHours ? ", délai souhaité par le distributeur {$maxHours} h" : ', pas de délai imposé par le distributeur')
            .($limit['by'] === 'product' ? ", délai maximal retenu pour le produit {$limit['hours']} h" : '').".\n"
            .$productFacts."\n"
            .($reference ? "Camion de référence : {$reference['name']} ({$reference['co2_kg']} kg CO2e).\n" : '')
            ."Options calculées (facteurs ADEME) :\n{$table}\n"
            .($tips ? "Pistes calculées :\n- ".implode("\n- ", $tips) : '');

        $this->warmUpDns();

        // Une réponse reçue mais inutilisable (tronquée, mauvais format) est redemandée une fois.
        // Sans réponse du tout (réseau, clé), on passe directement au mode de secours.
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            // Marge de tokens : les modèles « à raisonnement » (gpt-oss…) les consomment avant de répondre
            $answer = $this->ai->ask($system, $facts, 1500);
            if (! $answer) {
                return null;
            }
            if ($choice = $this->parseAiAnswer($answer, $options, $facts)) {
                return $choice;
            }
        }

        return null;
    }

    /**
     * @return array{key: string, explanation: string, tips: array<int, string>}|null
     */
    private function parseAiAnswer(string $answer, Collection $options, string $facts): ?array
    {
        // Le modèle peut entourer le JSON de texte ou de balises ```json
        if (! preg_match('/\{.*\}/s', $answer, $match)) {
            return null;
        }
        $data = json_decode($match[0], true);
        if (! is_array($data)) {
            return null;
        }

        // Tolère « [plan_rail] » ou des guillemets autour de la clé
        $key = trim((string) ($data['choice'] ?? ''), " \t\n[]\"'`");
        $explanation = trim((string) ($data['explanation'] ?? ''));
        if ($explanation === '' || ! $options->contains(fn ($o) => $o['key'] === $key && $o['feasible'])) {
            return null;
        }

        // Les chiffres restent ceux du calcul local : un conseil citant un nombre absent des données est écarté
        $allowed = $this->numbersIn($facts);
        $aiTips = collect(is_array($data['tips'] ?? null) ? $data['tips'] : [])
            ->filter(fn ($tip) => is_string($tip) && trim($tip) !== '')
            ->filter(fn ($tip) => array_diff($this->numbersIn($tip), $allowed) === [])
            ->map(fn ($tip) => Str::limit(trim($tip), 300))
            ->take(2)->values()->all();

        return ['key' => $key, 'explanation' => Str::limit($explanation, 600), 'tips' => $aiTips];
    }

    /**
     * Nombres d'un texte, normalisés (« 2 000 » -> 2000, « 24,68 » -> 24.68)
     *
     * @return array<int, string>
     */
    private function numbersIn(string $text): array
    {
        $text = preg_replace('/(\d)[\s\x{00A0}\x{202F}](?=\d{3}\b)/u', '$1', $text);
        preg_match_all('/\d+(?:[.,]\d+)?/', $text, $matches);

        return array_values(array_unique(array_map(
            fn ($n) => (string) (float) str_replace(',', '.', $n),
            $matches[0]
        )));
    }

    /**
     * Résout le nom de l'API avant l'appel : sur certains postes Windows la résolution DNS
     * dépasse les 10 s de connexion de cURL, alors que celle de PHP n'a pas cette limite
     * et met le résultat en cache pour l'appel qui suit.
     */
    private function warmUpDns(): void
    {
        $host = parse_url((string) config('services.ai.base_url'), PHP_URL_HOST);
        if ($host && ! app()->runningUnitTests()) {
            gethostbyname($host);
        }
    }

    // ==================== MODE DE SECOURS ====================

    private function localExplanation(array $option, ?array $reference, array $product, array $limit, bool $hasFeasible): string
    {
        $co2 = fn (float $kg) => number_format($kg, 2, ',', ' ').' kg CO2e';

        if (! $hasFeasible) {
            return "Aucune option ne respecte le délai de {$this->hours($limit['hours'])} h avec ce chargement. La plus rapide est {$option['name']} "
                ."(environ {$option['hours']} h, {$co2($option['co2_kg'])})"
                .($limit['by'] === 'product' ? ' : le produit étant périssable, la rapidité passe avant le CO2.' : ' : élargissez le délai pour réduire l\'empreinte.');
        }

        $text = match ($product['class']) {
            'perishable' => "{$product['name']} est périssable : on se limite aux options livrant en moins de {$this->hours($limit['hours'])} h, puis on retient la moins émettrice. ",
            'semi' => "{$product['name']} se conserve quelques semaines : livraison sous {$this->hours($limit['hours'])} h, en privilégiant le CO2. ",
            'durable' => "{$product['name']} se conserve bien : on peut privilégier l'option la moins émettrice, même plus lente. ",
            default => '',
        };

        $text .= "{$option['name']} : {$co2($option['co2_kg'])} pour environ {$option['hours']} h de transport";
        $text .= $limit['by'] === 'user' ? ", compatible avec votre délai de {$this->hours($limit['hours'])} h." : '.';

        if ($reference && $reference['key'] !== $option['key'] && $reference['co2_kg'] > 0) {
            $percent = round(($reference['co2_kg'] - $option['co2_kg']) / $reference['co2_kg'] * 100);
            $text .= " Par rapport au {$reference['name']} ({$co2($reference['co2_kg'])}), "
                .($percent >= 0 ? "vous économisez {$percent} %." : 'il émet '.abs($percent).' % de plus.');
        }
        if ($option['type'] === 'plan') {
            $text .= ' Sous réserve d\'une gare ou d\'un port proches du départ et de l\'arrivée.';
        }

        return $text;
    }
}
