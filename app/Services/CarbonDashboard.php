<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentLeg;
use App\Models\TransportMode;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Module 3 - Indicateurs du dashboard carbone d'un distributeur, sur une période.
 * Une expédition compte à sa date de départ (ou de création si elle n'est pas partie) ;
 * les expéditions annulées sont exclues.
 */
class CarbonDashboard
{
    public const PERIODS = [
        'month' => 'Ce mois',
        '3m' => '3 derniers mois',
        '12m' => '12 derniers mois',
    ];

    private const MONTHS = [1 => 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

    /** Camion de référence pour le CO2 économisé, par ordre de préférence (comme la recommandation IA) */
    private const REFERENCE_TRUCKS = ['truck_light', 'truck_heavy'];

    public function forUser(User $user, string $period = '12m'): array
    {
        $period = array_key_exists($period, self::PERIODS) ? $period : '12m';
        $start = $this->periodStart($period);

        $shipments = $user->shipments()
            ->with('legs.transportMode')
            ->where('status', '!=', 'cancelled')
            ->get()
            ->filter(fn (Shipment $s) => $this->dateOf($s)->gte($start))
            ->values();
        $legs = $shipments->flatMap->legs;

        $delivered = $shipments->where('status', 'delivered');
        $deliveredTonnes = $delivered->sum(fn (Shipment $s) => (float) $s->weight_kg) / 1000;

        return [
            'period' => $period,
            'period_label' => self::PERIODS[$period],
            'start' => $start,
            'total_co2_kg' => round($legs->sum(fn (ShipmentLeg $l) => (float) $l->co2_kg), 3),
            'shipment_count' => $shipments->count(),
            'total_distance_km' => round($legs->sum(fn (ShipmentLeg $l) => (float) $l->distance_km), 1),
            'delivered_count' => $delivered->count(),
            'co2_per_tonne' => $deliveredTonnes > 0
                ? round($delivered->sum(fn (Shipment $s) => $s->legs->sum(fn ($l) => (float) $l->co2_kg)) / $deliveredTonnes, 2)
                : null,
            'savings' => $this->recommendationSavings($legs),
            'by_mode' => $this->byMode($legs),
            'by_month' => $this->byMonth($shipments, $start),
        ];
    }

    public function periodStart(string $period): Carbon
    {
        return match ($period) {
            'month' => now()->startOfMonth(),
            '3m' => now()->subMonthsNoOverflow(2)->startOfMonth(),
            default => now()->subMonthsNoOverflow(11)->startOfMonth(),
        };
    }

    private function dateOf(Shipment $shipment): Carbon
    {
        return $shipment->departed_at ?? $shipment->created_at;
    }

    /**
     * CO2 évité par les étapes créées en appliquant la recommandation IA, comparé au même
     * trajet en camion de référence. Null si aucun camion de référence n'existe.
     *
     * @return array{co2_kg: float, legs: int, reference: string}|null
     */
    private function recommendationSavings(Collection $legs): ?array
    {
        $reference = TransportMode::whereIn('code', self::REFERENCE_TRUCKS)->get()
            ->sortBy(fn ($m) => array_search($m->code, self::REFERENCE_TRUCKS))
            ->first();
        if (!$reference) {
            return null;
        }

        $recommended = $legs->where('from_recommendation', true);

        return [
            'co2_kg' => round($recommended->sum(
                fn (ShipmentLeg $l) => (float) $l->distance_km * ((float) $l->weight_kg / 1000) * (float) $reference->emission_factor - (float) $l->co2_kg
            ), 3),
            'legs' => $recommended->count(),
            'reference' => $reference->name,
        ];
    }

    /**
     * @return array<string, float> CO2 par mode, du plus au moins émetteur
     */
    private function byMode(Collection $legs): array
    {
        return $legs->groupBy(fn (ShipmentLeg $l) => $l->transportMode->name)
            ->map(fn ($group) => round($group->sum(fn ($l) => (float) $l->co2_kg), 3))
            ->sortDesc()
            ->all();
    }

    /**
     * @return array<string, float> CO2 par mois de la période, mois sans expédition inclus
     */
    private function byMonth(Collection $shipments, Carbon $start): array
    {
        $months = [];
        for ($month = $start->copy(); $month->lte(now()); $month->addMonthNoOverflow()) {
            $months[$month->format('Y-m')] = 0.0;
        }

        foreach ($shipments as $shipment) {
            $key = $this->dateOf($shipment)->format('Y-m');
            if (array_key_exists($key, $months)) {
                $months[$key] += $shipment->legs->sum(fn ($l) => (float) $l->co2_kg);
            }
        }

        $labels = [];
        foreach ($months as $key => $co2) {
            [$year, $month] = explode('-', $key);
            $labels[self::MONTHS[(int) $month] . ' ' . $year] = round($co2, 3);
        }

        return $labels;
    }
}
