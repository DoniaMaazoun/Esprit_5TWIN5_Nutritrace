<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Farm;
use App\Models\Harvest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Intelligence produit du Module 1 : fraîcheur, prix conseillé, score de conformité,
 * recherche en langage naturel, produits similaires, prévision et fiche consommateur.
 *
 * Tous les calculs sont locaux (référentiels agronomiques ci-dessous) ; l'IA (AiAssistant)
 * ne sert qu'à rédiger ou expliquer, et le module reste fonctionnel sans clé.
 */
class ProductIntelligence
{
    /**
     * Référentiel par catégorie (slug) :
     *  yield  : rendement plausible [min, max] en kg/ha
     *  shelf  : durée de conservation [ambiante, au froid] en jours
     *  price  : prix producteur indicatif [min, max] en TND/kg
     *  months : mois de récolte habituels (null = toute l'année)
     *  cycle  : durée minimale de culture en jours
     */
    public const NORMS = [
        'legumes' => ['yield' => [8000, 80000], 'shelf' => [7, 21], 'price' => [1.2, 3.5], 'months' => null, 'cycle' => 45],
        'fruits' => ['yield' => [3000, 35000], 'shelf' => [6, 28], 'price' => [2.5, 8.0], 'months' => null, 'cycle' => 60],
        'cereales' => ['yield' => [1200, 7000], 'shelf' => [365, 365], 'price' => [0.8, 1.6], 'months' => [5, 6, 7], 'cycle' => 120],
        'olives' => ['yield' => [300, 12000], 'shelf' => [20, 45], 'price' => [2.0, 5.0], 'months' => [10, 11, 12, 1], 'cycle' => 150],
        'dattes' => ['yield' => [500, 15000], 'shelf' => [180, 365], 'price' => [4.0, 12.0], 'months' => [9, 10, 11, 12], 'cycle' => 150],
        'agrumes' => ['yield' => [8000, 40000], 'shelf' => [21, 60], 'price' => [1.5, 4.0], 'months' => [11, 12, 1, 2, 3, 4], 'cycle' => 150],
        'legumineuses' => ['yield' => [600, 4500], 'shelf' => [365, 365], 'price' => [3.0, 7.0], 'months' => null, 'cycle' => 60],
        'plantes-aromatiques' => ['yield' => [4000, 25000], 'shelf' => [5, 14], 'price' => [4.0, 15.0], 'months' => null, 'cycle' => 30],
    ];

    private const DEFAULT_NORM = ['yield' => null, 'shelf' => [14, 30], 'price' => [1.5, 6.0], 'months' => null, 'cycle' => 30];

    private const MONTHS = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

    public function __construct(private AiAssistant $ai)
    {
    }

    public function norm(?Category $category): array
    {
        return self::NORMS[$category?->slug] ?? self::DEFAULT_NORM;
    }

    // ==================== FRAÎCHEUR / DATE LIMITE ====================

    /**
     * @return array{cold: bool, days: int, best_before: Carbon, days_left: int, percent: int, level: string, label: string, active: bool}
     */
    public function freshness(Harvest $h): array
    {
        $norm = $this->norm($h->category);
        $cold = (bool) preg_match('/froid|frigo|r[ée]frig|°\s?c|\bcongel|atmosph[èe]re/iu', (string) $h->storage_conditions);
        $days = $norm['shelf'][$cold ? 1 : 0];
        $bestBefore = $h->harvest_date->copy()->addDays($days);
        $left = (int) now()->startOfDay()->diffInDays($bestBefore, false);
        $percent = (int) max(0, min(100, round($left / $days * 100)));
        // Un lot vendu, expédié ou transformé n'est plus suivi par le producteur
        $active = $h->status === 'in_stock';

        [$level, $label] = match (true) {
            !$active => ['done', 'Lot écoulé'],
            $left < 0 => ['expired', 'Date limite estimée dépassée'],
            $percent <= 25 => ['low', 'À écouler rapidement'],
            $percent <= 60 => ['mid', 'Fraîcheur correcte'],
            default => ['high', 'Très frais'],
        };

        return compact('cold', 'days', 'percent', 'level', 'label', 'active') + ['best_before' => $bestBefore, 'days_left' => $left];
    }

    // ==================== PRIX CONSEILLÉ ====================

    /**
     * Fourchette de prix producteur (TND/kg) et valeur estimée du lot.
     *
     * @return array{min: float, max: float, median: float, value: float, factors: array<int, string>}
     */
    public function price(Harvest $h): array
    {
        [$min, $max] = $this->norm($h->category)['price'];
        $factor = 1.0;
        $factors = [];

        $grade = ['A' => [1.15, 'Qualité A (Extra) : +15 %'], 'B' => [1.0, null], 'C' => [0.8, 'Qualité C : −20 %']][$h->quality_grade] ?? [1.0, null];
        $factor *= $grade[0];
        if ($grade[1]) $factors[] = $grade[1];

        $farming = $h->farm?->farming_type;
        if ($farming === 'organic') { $factor *= 1.3; $factors[] = 'Agriculture biologique : +30 %'; }
        elseif (in_array($farming, ['reasoned', 'permaculture', 'agroforestry'])) { $factor *= 1.1; $factors[] = 'Pratiques durables : +10 %'; }
        if ($h->farm?->certification) { $factor *= 1.05; $factors[] = 'Exploitation certifiée : +5 %'; }

        $fresh = $this->freshness($h);
        if ($fresh['active'] && $fresh['percent'] <= 25) { $factor *= 0.8; $factors[] = 'Fin de fraîcheur : −20 %'; }

        $min = round($min * $factor, 2);
        $max = round($max * $factor, 2);
        $median = round(($min + $max) / 2, 2);

        return [
            'min' => $min,
            'max' => $max,
            'median' => $median,
            'value' => round($median * $h->quantityInKg()),
            'factors' => $factors ?: ['Prix de référence de la catégorie'],
        ];
    }

    // ==================== SCORE DE CONFORMITÉ (ANTI-FRAUDE) ====================

    /**
     * Score de risque 0–100 : plus il est élevé, plus la déclaration mérite un contrôle.
     *
     * @return array{score: int, level: string, label: string, reasons: array<int, array{0: int, 1: string}>}
     */
    public function risk(Harvest $h): array
    {
        $norm = $this->norm($h->category);
        $reasons = [];

        if ($norm['yield'] && ($yield = $h->yieldPerHectare()) !== null) {
            [$yMin, $yMax] = $norm['yield'];
            if ($yield > $yMax * 1.3) {
                $reasons[] = [40, 'Rendement de ' . number_format($yield, 0, ',', ' ') . ' kg/ha très supérieur à la normale de la catégorie (max. ' . number_format($yMax, 0, ',', ' ') . ') : possible surdéclaration.'];
            } elseif ($yield < $yMin * 0.3) {
                $reasons[] = [10, 'Rendement de ' . number_format($yield, 0, ',', ' ') . ' kg/ha anormalement faible : surface ou quantité à vérifier.'];
            }
        }

        if ($norm['months'] && !in_array($h->harvest_date->month, $norm['months'])) {
            $season = collect($norm['months'])->map(fn ($m) => self::MONTHS[$m])->implode(', ');
            $reasons[] = [20, "Récolte en " . self::MONTHS[$h->harvest_date->month] . ", hors de la saison habituelle ({$season})."];
        }

        if (($days = $h->growingDays()) !== null && $days < $norm['cycle']) {
            $reasons[] = [15, "Cycle de culture de {$days} jours, inférieur au minimum attendu ({$norm['cycle']} jours)."];
        }

        $fresh = $this->freshness($h);
        if ($fresh['level'] === 'expired') {
            $reasons[] = [25, 'Lot toujours en stock après sa date limite estimée (' . $fresh['best_before']->format('d/m/Y') . ').'];
        }

        if ($h->farm?->farming_type === 'organic' && !$h->farm->certification) {
            $reasons[] = [20, 'Produit présenté comme biologique sans organisme certificateur.'];
        }
        if (!$h->plot_area) $reasons[] = [10, 'Surface de la parcelle non renseignée : rendement invérifiable.'];
        if (!$h->hasPhoto()) $reasons[] = [8, 'Aucune photo du lot.'];
        if (!$h->planting_date) $reasons[] = [5, 'Date de plantation absente.'];
        if ($h->farm && !$h->farm->hasLocation()) $reasons[] = [5, 'Exploitation non géolocalisée.'];

        $score = min(100, array_sum(array_column($reasons, 0)));
        usort($reasons, fn ($a, $b) => $b[0] <=> $a[0]);

        [$level, $label] = match (true) {
            $score >= 50 => ['high', 'Risque élevé'],
            $score >= 25 => ['medium', 'À surveiller'],
            default => ['low', 'Conforme'],
        };

        return compact('score', 'level', 'label', 'reasons');
    }

    /**
     * Explication rédigée (IA si disponible, sinon synthèse locale) pour l'administrateur.
     *
     * @return array{text: string, source: string}
     */
    public function explainRisk(Harvest $h): array
    {
        $risk = $this->risk($h);
        $facts = $this->facts($h);
        $reasons = collect($risk['reasons'])->map(fn ($r) => "- (+{$r[0]}) {$r[1]}")->implode("\n") ?: '- Aucune anomalie détectée.';

        $text = $this->ai->ask(
            'Tu es contrôleur qualité d\'une plateforme tunisienne de traçabilité alimentaire. En français, en 3 à 5 phrases, '
            . 'explique à l\'administrateur le niveau de risque de cette déclaration de récolte et les vérifications concrètes à mener '
            . '(documents, appel au producteur, visite). Reste factuel, n\'invente rien.',
            "Lot :\n{$facts}\nScore de risque : {$risk['score']}/100 ({$risk['label']})\nAnomalies :\n{$reasons}",
            350
        );

        if ($text) {
            return ['text' => $text, 'source' => 'ai'];
        }

        $local = "Score de {$risk['score']}/100 : {$risk['label']}. ";
        $local .= $risk['reasons']
            ? 'Point principal : ' . Str::lcfirst($risk['reasons'][0][1]) . ' '
              . ($risk['score'] >= 50 ? 'Recommandation : contacter le producteur et demander un justificatif (bon de pesée, photos datées) avant de laisser le lot au catalogue.'
                                      : 'Recommandation : demander au producteur de compléter les informations manquantes.')
            : 'La déclaration est cohérente avec les références de la catégorie ; aucun contrôle particulier n\'est nécessaire.';

        return ['text' => $local, 'source' => 'local'];
    }

    // ==================== FICHE CONSOMMATEUR ====================

    /**
     * @param  array<string, mixed>  $d  product_name, variety, category_id, farm_id, harvest_date, quality_grade, storage_conditions, treatments
     * @return array{text: string, source: string}
     */
    public function consumerDescription(array $d): array
    {
        $farm = Farm::find($d['farm_id'] ?? null);
        $category = Category::find($d['category_id'] ?? null);
        $product = trim(($d['product_name'] ?? 'Produit') . ' ' . ($d['variety'] ?? ''));
        $date = !empty($d['harvest_date']) ? Carbon::parse($d['harvest_date'])->translatedFormat('d F Y') : null;
        $grade = ['A' => 'extra', 'B' => 'de catégorie I', 'C' => 'de catégorie II'][$d['quality_grade'] ?? ''] ?? null;

        $facts = collect([
            'Produit' => $product,
            'Catégorie' => $category?->name,
            'Exploitation' => $farm ? "{$farm->name} ({$farm->city}, {$farm->governorate})" : null,
            'Agriculture' => $farm?->farmingTypeLabel(),
            'Certification' => $farm?->certification,
            'Récolte' => $date,
            'Qualité' => $grade,
            'Conservation' => $d['storage_conditions'] ?? null,
            'Traitements' => $d['treatments'] ?? null,
        ])->filter()->map(fn ($v, $k) => "- {$k} : {$v}")->implode("\n");

        $text = $this->ai->ask(
            'Tu rédiges, en français, la fiche consommateur d\'un lot alimentaire tracé sur la plateforme tunisienne Farm to Fork : '
            . '60 à 90 mots, ton chaleureux, conseils de dégustation ou de conservation, sans inventer de chiffres ni de labels absents.',
            "Données du lot :\n{$facts}",
            300
        );
        if ($text) {
            return ['text' => $text, 'source' => 'ai'];
        }

        $parts = ["{$product} récolté" . ($date ? " le {$date}" : '') . ($farm ? " à {$farm->name}, {$farm->city} ({$farm->governorate})" : '') . '.'];
        if ($farm) {
            $parts[] = 'Cultivé en agriculture ' . Str::lower($farm->farmingTypeLabel()) . ($farm->certification ? " et certifié {$farm->certification}" : '') . ', avec une irrigation ' . Str::lower($farm->irrigationTypeLabel()) . '.';
        }
        if ($grade) $parts[] = "Ce lot a été classé en qualité {$grade} par son producteur.";
        $parts[] = match ($category?->slug) {
            'legumes' => 'Conservez-le au frais et consommez-le dans la semaine pour profiter de toute sa saveur.',
            'fruits' => 'Dégustez-le à température ambiante pour révéler ses arômes ; il se garde quelques jours au réfrigérateur.',
            'olives' => 'Destiné à la table ou au pressage, il exprime toute la richesse du terroir tunisien.',
            'dattes' => 'À conserver dans un endroit sec, idéal en en-cas ou dans vos pâtisseries.',
            'agrumes' => 'Riche en vitamine C, il se conserve plusieurs semaines dans un endroit frais.',
            'cereales', 'legumineuses' => 'À conserver au sec, à l\'abri de la lumière et de l\'humidité.',
            'plantes-aromatiques' => 'À utiliser rapidement ou à conserver dans un linge humide au réfrigérateur.',
            default => 'Scannez le QR code pour découvrir toute son histoire.',
        };

        return ['text' => implode(' ', $parts), 'source' => 'local'];
    }

    // ==================== RECHERCHE EN LANGAGE NATUREL ====================

    /**
     * Transforme « tomates bio de Nabeul en stock, qualité A » en filtres du catalogue.
     *
     * @return array{filters: array<string, mixed>, chips: array<int, string>}
     */
    public function parseSearch(string $query): array
    {
        $q = ' ' . Str::lower(Str::ascii($query)) . ' ';
        $filters = [];
        $chips = [];
        $consume = function (string $pattern) use (&$q) {
            $q = preg_replace("/{$pattern}/", ' ', $q);
        };

        foreach (Category::active()->get() as $c) {
            $base = Str::lower(Str::ascii($c->name));
            $stem = preg_quote(rtrim(explode(' ', $base)[0], 's'), '/');
            if (preg_match("/\\b{$stem}s?\\b/", $q)) {
                $filters['category'] = $c->id;
                $chips[] = "Catégorie : {$c->name}";
                $consume("\\b{$stem}s?\\b");
                break;
            }
        }

        foreach (Farm::GOVERNORATES as $g) {
            $name = preg_quote(Str::lower(Str::ascii($g)), '/');
            if (preg_match("/\\b{$name}\\b/", $q)) {
                $filters['governorate'] = $g;
                $chips[] = "Région : {$g}";
                $consume("\\b(de |du |a |en )?{$name}\\b");
                break;
            }
        }

        $types = ['organic' => '\\bbio(logique)?s?\\b', 'reasoned' => '\\braisonnee?s?\\b', 'permaculture' => '\\bpermaculture\\b', 'conventional' => '\\bconventionnel(le)?s?\\b'];
        foreach ($types as $type => $pattern) {
            if (preg_match("/{$pattern}/", $q)) {
                $filters['farming_type'] = $type;
                $chips[] = 'Agriculture : ' . Farm::FARMING_TYPES[$type];
                $consume($pattern);
                break;
            }
        }

        if (preg_match('/\\b(en stock|disponibles?|dispo)\\b/', $q)) {
            $filters['availability'] = 'in_stock';
            $chips[] = 'Disponibles uniquement';
            $consume('\\b(en stock|disponibles?|dispo)\\b');
        }
        if (preg_match('/\\b(qualite\\s*)?(extra|premium|qualite a)\\b/', $q)) {
            $filters['quality'] = 'A';
            $chips[] = 'Qualité A';
            $consume('\\b(qualite\\s*)?(extra|premium|qualite a)\\b');
        }
        if (preg_match('/\\b(frais|recents?|recemment)\\b/', $q)) {
            $filters['fresh'] = 1;
            $chips[] = 'Récoltés depuis moins de 30 jours';
            $consume('\\b(frais|recents?|recemment)\\b');
        }

        // Mots restants (hors mots vides) : recherche texte sur le produit
        $stop = ['de', 'du', 'des', 'la', 'le', 'les', 'en', 'a', 'et', 'je', 'veux', 'cherche', 'produits?', 'pour', 'avec', 'qui', 'sont', 'un', 'une', 'qualite', 'agriculture', 'region'];
        $words = collect(preg_split('/[^a-z0-9]+/', $q))->filter(fn ($w) => strlen($w) > 2 && !preg_match('/^(' . implode('|', $stop) . ')$/', $w));
        if ($words->isNotEmpty()) {
            // On ramène les pluriels au singulier pour chercher « tomates » → « tomate »
            $term = $words->map(fn ($w) => preg_replace('/s$/', '', $w))->implode(' ');
            $filters['q'] = $term;
            $chips[] = "Mots-clés : « {$term} »";
        }

        return compact('filters', 'chips');
    }

    // ==================== RECOMMANDATIONS ====================

    /**
     * Produits similaires (même catégorie), privilégiant la même région et le même mode de culture.
     */
    public function similar(Harvest $h, int $limit = 3): Collection
    {
        return Harvest::with(['farm.user', 'category'])
            ->where('category_id', $h->category_id)
            ->whereKeyNot($h->id)
            ->where('farm_id', '!=', $h->farm_id)
            ->whereHas('farm.user', fn ($u) => $u->where('status', 'approved'))
            ->latest('harvest_date')
            ->take(30)
            ->get()
            ->sortByDesc(fn (Harvest $o) => ($o->farm->governorate === $h->farm->governorate ? 2 : 0)
                + ($o->farm->farming_type === $h->farm->farming_type ? 1 : 0)
                + ($o->status === 'in_stock' ? 1 : 0))
            ->take($limit)
            ->values();
    }

    // ==================== PRÉVISION ====================

    /**
     * Prévision des 3 prochains mois par régression linéaire sur la série mensuelle (kg).
     *
     * @param  array<int, float>  $series  12 valeurs mensuelles, de la plus ancienne à la plus récente
     * @return array{values: array<int, float>, trend: string}
     */
    public function forecast(array $series, int $horizon = 3): array
    {
        $n = count($series);
        if ($n < 2 || array_sum($series) == 0) {
            return ['values' => array_fill(0, $horizon, 0), 'trend' => 'stable'];
        }

        $xMean = ($n - 1) / 2;
        $yMean = array_sum($series) / $n;
        $num = $den = 0;
        foreach ($series as $x => $y) {
            $num += ($x - $xMean) * ($y - $yMean);
            $den += ($x - $xMean) ** 2;
        }
        $slope = $den ? $num / $den : 0;
        $intercept = $yMean - $slope * $xMean;

        $values = [];
        for ($i = 0; $i < $horizon; $i++) {
            $values[] = max(0, round($intercept + $slope * ($n + $i)));
        }

        $trend = abs($slope) < $yMean * 0.03 ? 'stable' : ($slope > 0 ? 'hausse' : 'baisse');

        return compact('values', 'trend');
    }

    private function facts(Harvest $h): string
    {
        return collect([
            'Produit' => $h->product_name . ($h->variety ? " ({$h->variety})" : ''),
            'Catégorie' => $h->categoryLabel(),
            'Exploitation' => $h->farm ? "{$h->farm->name}, {$h->farm->governorate}, {$h->farm->farmingTypeLabel()}" : null,
            'Récolte' => $h->harvest_date->format('d/m/Y'),
            'Plantation' => $h->planting_date?->format('d/m/Y'),
            'Quantité' => $h->quantityInKg() . ' kg',
            'Parcelle' => $h->plot_area ? "{$h->plot_area} ha" : null,
            'Rendement' => $h->yieldPerHectare() ? $h->yieldPerHectare() . ' kg/ha' : null,
            'Qualité' => $h->quality_grade,
            'Statut' => $h->statusLabel(),
        ])->filter()->map(fn ($v, $k) => "- {$k} : {$v}")->implode("\n");
    }
}
