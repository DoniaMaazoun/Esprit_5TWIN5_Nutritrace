<?php

namespace App\Services;

use App\Models\TransportMode;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Module 3 - Recommandation du mode de transport le moins polluant.
 *
 * Le calcul CO2 (distance x tonnes x facteur ADEME), la durée estimée et les contraintes
 * (délai, capacité) sont toujours faits ici. L'IA (service AiAssistant du module 1, même
 * fournisseur et même clé) choisit parmi les modes possibles et rédige l'explication.
 * Sans clé API, ou si sa réponse est inutilisable, le mode le moins émetteur est retenu.
 */
class TransportRecommender
{
    /**
     * Hypothèses par code de mode : vitesse moyenne (km/h), temps de manutention (h)
     * et charge utile maximale (kg, null = pas de limite pratique).
     */
    private const PROFILES = [
        'truck_heavy' => ['speed' => 60, 'handling' => 2, 'max_kg' => null],
        'truck_light' => ['speed' => 60, 'handling' => 1, 'max_kg' => 19000],
        'van' => ['speed' => 70, 'handling' => 0.5, 'max_kg' => 1500],
        'rail' => ['speed' => 40, 'handling' => 12, 'max_kg' => null],
        'river' => ['speed' => 12, 'handling' => 24, 'max_kg' => null],
        'sea' => ['speed' => 25, 'handling' => 48, 'max_kg' => null],
        'air' => ['speed' => 600, 'handling' => 12, 'max_kg' => null],
    ];

    /** Profil utilisé pour un mode ajouté par l'admin avec un code inconnu */
    private const DEFAULT_PROFILE = ['speed' => 50, 'handling' => 2, 'max_kg' => null];

    /** Camion de référence pour calculer le gain, par ordre de préférence */
    private const REFERENCE_TRUCKS = ['truck_light', 'truck_heavy'];

    public function __construct(private AiAssistant $ai)
    {
    }

    /**
     * @return array{
     *     source: string,
     *     recommended: array,
     *     explanation: string,
     *     reference: ?array,
     *     saving_kg: ?float,
     *     saving_percent: ?float,
     *     options: array<int, array>
     * }
     */
    public function recommend(float $distanceKm, float $weightKg, ?float $maxHours = null): array
    {
        $options = $this->evaluateModes($distanceKm, $weightKg, $maxHours);
        $feasible = $options->where('feasible', true);

        $reference = $options->first(fn ($o) => $o['code'] === self::REFERENCE_TRUCKS[0])
            ?? $options->first(fn ($o) => $o['code'] === self::REFERENCE_TRUCKS[1]);

        $aiChoice = $feasible->isNotEmpty()
            ? $this->askAi($distanceKm, $weightKg, $maxHours, $options, $reference)
            : null;

        if ($aiChoice) {
            $recommended = $feasible->firstWhere('code', $aiChoice['code']);
            $explanation = $aiChoice['explanation'];
            $source = 'ai';
        } else {
            // Mode de secours : le moins émetteur parmi les modes possibles,
            // ou le plus rapide si aucun ne respecte le délai
            $recommended = $feasible->isNotEmpty()
                ? $feasible->sortBy('co2_kg')->first()
                : $options->where('fits_capacity', true)->sortBy('hours')->first() ?? $options->sortBy('hours')->first();
            $explanation = $this->localExplanation($recommended, $reference, $maxHours, $feasible->isNotEmpty());
            $source = 'local';
        }

        $saving = $reference ? round($reference['co2_kg'] - $recommended['co2_kg'], 3) : null;

        return [
            'source' => $source,
            'recommended' => $recommended,
            'explanation' => $explanation,
            'reference' => $reference,
            'saving_kg' => $saving,
            'saving_percent' => $reference && $reference['co2_kg'] > 0
                ? round($saving / $reference['co2_kg'] * 100, 1)
                : null,
            'options' => $options->values()->all(),
        ];
    }

    /**
     * CO2, durée et faisabilité de chaque mode actif, du moins au plus émetteur
     */
    public function evaluateModes(float $distanceKm, float $weightKg, ?float $maxHours = null): Collection
    {
        return TransportMode::where('is_active', true)->get()
            ->map(function (TransportMode $mode) use ($distanceKm, $weightKg, $maxHours) {
                $profile = self::PROFILES[$mode->code] ?? self::DEFAULT_PROFILE;
                $hours = round($distanceKm / $profile['speed'] + $profile['handling'], 1);
                $fitsCapacity = $profile['max_kg'] === null || $weightKg <= $profile['max_kg'];
                $fitsDelay = $maxHours === null || $hours <= $maxHours;

                return [
                    'id' => $mode->id,
                    'code' => $mode->code,
                    'name' => $mode->name,
                    'emission_factor' => (float) $mode->emission_factor,
                    'co2_kg' => round($distanceKm * ($weightKg / 1000) * (float) $mode->emission_factor, 3),
                    'hours' => $hours,
                    'fits_capacity' => $fitsCapacity,
                    'fits_delay' => $fitsDelay,
                    'feasible' => $fitsCapacity && $fitsDelay,
                ];
            })
            ->sortBy('co2_kg')
            ->values();
    }

    /**
     * Demande à l'IA de choisir parmi les modes possibles. Retourne null si l'IA est
     * indisponible ou si sa réponse ne désigne pas un mode possible.
     *
     * @return array{code: string, explanation: string}|null
     */
    private function askAi(float $distanceKm, float $weightKg, ?float $maxHours, Collection $options, ?array $reference): ?array
    {
        if (!$this->ai->isEnabled()) {
            return null;
        }

        $table = $options->map(fn ($o) => sprintf(
            '- %s (code %s) : %s kg CO2e, environ %s h%s',
            $o['name'],
            $o['code'],
            $o['co2_kg'],
            $o['hours'],
            $o['feasible'] ? '' : ' [IMPOSSIBLE : ' . (!$o['fits_capacity'] ? 'capacité dépassée' : 'délai dépassé') . ']'
        ))->implode("\n");

        $answer = $this->ai->ask(
            'Tu es expert en logistique bas carbone pour une plateforme tunisienne de traçabilité alimentaire. '
            . 'Choisis le meilleur mode de transport parmi ceux qui ne sont pas marqués IMPOSSIBLE, en privilégiant le CO2 '
            . 'tout en restant réaliste (desserte ferroviaire ou portuaire, produits frais). N\'invente aucun chiffre. '
            . 'Réponds uniquement en JSON : {"code": "<code du mode>", "explanation": "<2 à 3 phrases en français>"}.',
            "Expédition : {$distanceKm} km, {$weightKg} kg"
            . ($maxHours ? ", délai souhaité {$maxHours} h" : ', pas de délai imposé') . ".\n"
            . ($reference ? "Camion de référence : {$reference['name']} ({$reference['co2_kg']} kg CO2e).\n" : '')
            . "Modes disponibles (calcul ADEME) :\n{$table}",
            300
        );

        if (!$answer) {
            return null;
        }

        // Le modèle peut entourer le JSON de texte ou de balises ```json
        if (!preg_match('/\{.*\}/s', $answer, $match)) {
            return null;
        }
        $data = json_decode($match[0], true);
        $code = is_array($data) ? ($data['code'] ?? null) : null;
        $explanation = is_array($data) ? trim((string) ($data['explanation'] ?? '')) : '';

        $isFeasible = $options->contains(fn ($o) => $o['code'] === $code && $o['feasible']);

        return $isFeasible && $explanation !== ''
            ? ['code' => $code, 'explanation' => Str::limit($explanation, 600)]
            : null;
    }

    private function localExplanation(array $mode, ?array $reference, ?float $maxHours, bool $hasFeasible): string
    {
        $co2 = fn (float $kg) => number_format($kg, 2, ',', ' ') . ' kg CO2e';

        if (!$hasFeasible) {
            return "Aucun mode ne respecte le délai de {$maxHours} h avec ce chargement. Le plus rapide est {$mode['name']} "
                . "(environ {$mode['hours']} h, {$co2($mode['co2_kg'])}) : élargissez le délai pour réduire l'empreinte.";
        }

        $text = "{$mode['name']} est le mode possible le moins émetteur : {$co2($mode['co2_kg'])} pour environ {$mode['hours']} h de transport";
        $text .= $maxHours ? ", compatible avec votre délai de {$maxHours} h." : '.';

        if ($reference && $reference['code'] !== $mode['code'] && $reference['co2_kg'] > 0) {
            $percent = round(($reference['co2_kg'] - $mode['co2_kg']) / $reference['co2_kg'] * 100);
            $text .= " Par rapport au {$reference['name']} ({$co2($reference['co2_kg'])}), "
                . ($percent >= 0 ? "vous économisez {$percent} %." : 'il émet ' . abs($percent) . ' % de plus.');
        }
        if (in_array($mode['code'], ['rail', 'sea', 'river'])) {
            $text .= ' Sous réserve d\'une desserte adaptée au départ et à l\'arrivée.';
        }

        return $text;
    }
}
