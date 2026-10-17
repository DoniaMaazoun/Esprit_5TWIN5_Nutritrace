<?php

namespace App\Services;

use App\Models\Farm;
use App\Models\Harvest;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Assistant IA du Module 1 (Producteur).
 *
 * Utilise n'importe quelle API compatible OpenAI (Groq, OpenAI, Mistral, Gemini, Ollama…)
 * configurée via AI_API_KEY / AI_BASE_URL / AI_MODEL. Sans clé ou en cas d'erreur réseau,
 * un moteur local à base de règles prend le relais pour que le module reste utilisable.
 */
class AiAssistant
{
    /** Gouvernorats arides où l'eau est la contrainte principale. */
    private const ARID_GOVERNORATES = ['Gafsa', 'Tozeur', 'Kébili', 'Tataouine', 'Médenine', 'Gabès', 'Sidi Bouzid', 'Kasserine'];

    public function isEnabled(): bool
    {
        return filled(config('services.ai.key'));
    }

    // ==================== GÉNÉRATION DE DESCRIPTION ====================

    /**
     * @return array{text: string, source: string}
     */
    public function farmDescription(array $data): array
    {
        $facts = $this->describeFarmFacts($data);

        $text = $this->complete([
            ['role' => 'system', 'content' => 'Tu es rédacteur pour une plateforme tunisienne de traçabilité alimentaire "Farm to Fork". '
                . 'Tu rédiges en français des présentations d\'exploitations agricoles : 80 à 120 mots, ton chaleureux et factuel, '
                . 'sans inventer de chiffres ni de certifications non fournis, sans titre ni liste, en un ou deux paragraphes.'],
            ['role' => 'user', 'content' => "Rédige la présentation de cette exploitation :\n{$facts}"],
        ], 400);

        return $text
            ? ['text' => $text, 'source' => 'ai']
            : ['text' => $this->localFarmDescription($data), 'source' => 'local'];
    }

    private function describeFarmFacts(array $d): string
    {
        $lines = [
            'Nom' => $d['name'] ?? null,
            'Localisation' => trim(($d['city'] ?? '') . ', ' . ($d['governorate'] ?? ''), ', '),
            'Surface' => !empty($d['total_area']) ? $d['total_area'] . ' ha' : null,
            'Agriculture' => Farm::FARMING_TYPES[$d['farming_type'] ?? ''] ?? null,
            'Irrigation' => Farm::IRRIGATION_TYPES[$d['irrigation_type'] ?? ''] ?? null,
            'Sol' => Farm::SOIL_TYPES[$d['soil_type'] ?? ''] ?? null,
            'Cultures principales' => $d['main_crops'] ?? null,
            'Certification' => $d['certification'] ?? null,
            'Année de création' => $d['established_year'] ?? null,
        ];

        return collect($lines)->filter()->map(fn ($v, $k) => "- {$k} : {$v}")->implode("\n");
    }

    private function localFarmDescription(array $d): string
    {
        $name = $d['name'] ?? 'Notre exploitation';
        $place = collect([$d['city'] ?? null, $d['governorate'] ?? null])->filter()->implode(', ');
        $farming = Str::lower(Farm::FARMING_TYPES[$d['farming_type'] ?? ''] ?? 'raisonnée');
        $irrigation = Str::lower(Farm::IRRIGATION_TYPES[$d['irrigation_type'] ?? ''] ?? '');
        $soil = Str::lower(Farm::SOIL_TYPES[$d['soil_type'] ?? ''] ?? '');

        $parts = [];
        $intro = "{$name} est une exploitation en agriculture {$farming}";
        $intro .= $place ? " située à {$place}" : '';
        $intro .= !empty($d['total_area']) ? ", s'étendant sur {$d['total_area']} hectares" : '';
        $intro .= !empty($d['established_year']) ? " et cultivée depuis {$d['established_year']}." : '.';
        $parts[] = $intro;

        if (!empty($d['main_crops'])) {
            $parts[] = "On y cultive principalement {$d['main_crops']}, récoltés à maturité pour garantir fraîcheur et saveur.";
        }
        if ($soil || $irrigation) {
            $sentence = 'Les cultures bénéficient';
            $sentence .= $soil ? " d'un sol {$soil}" : '';
            $sentence .= $soil && $irrigation ? ' et' : '';
            $sentence .= $irrigation ? " d'une irrigation {$irrigation} pensée pour préserver la ressource en eau" : '';
            $parts[] = $sentence . '.';
        }
        if (!empty($d['certification'])) {
            $parts[] = "L'exploitation est certifiée {$d['certification']}.";
        }
        $parts[] = 'Chaque lot récolté est enregistré sur Farm to Fork afin que le consommateur puisse en retracer l\'origine, de la parcelle jusqu\'à son assiette.';

        return implode(' ', $parts);
    }

    // ==================== ANALYSE D'EXPLOITATION ====================

    /**
     * Indicateurs + recommandations agronomiques pour une exploitation.
     *
     * @return array{metrics: array, recommendations: array<int, string>, summary: string, source: string}
     */
    public function farmInsights(Farm $farm): array
    {
        $farm->loadMissing('harvests');
        $harvests = $farm->harvests;
        $totalKg = $farm->totalHarvestedKg();

        $byProduct = $harvests->groupBy('product_name')
            ->map(fn ($g) => $g->sum(fn (Harvest $h) => $h->quantityInKg()))
            ->sortDesc();

        $gradeA = $harvests->where('quality_grade', 'A')->count();
        $inStockKg = $harvests->where('status', 'in_stock')->sum(fn (Harvest $h) => $h->quantityInKg());
        $growing = $harvests->map->growingDays()->filter();

        $metrics = [
            'harvest_count' => $harvests->count(),
            'total_kg' => round($totalKg, 1),
            'yield_per_ha' => $farm->yieldPerHectare(),
            'top_product' => $byProduct->keys()->first(),
            'grade_a_rate' => $harvests->count() ? round($gradeA / $harvests->count() * 100) : null,
            'in_stock_kg' => round($inStockKg, 1),
            'avg_growing_days' => $growing->isNotEmpty() ? (int) round($growing->avg()) : null,
            'categories' => $harvests->pluck('category_id')->unique()->count(),
        ];

        $recommendations = $this->localRecommendations($farm, $metrics);

        $summary = $this->complete([
            ['role' => 'system', 'content' => 'Tu es agronome-conseil en Tunisie. Réponds en français, en 4 à 6 puces courtes commençant par "- ", '
                . 'concrètes et adaptées au climat local. Base-toi uniquement sur les données fournies.'],
            ['role' => 'user', 'content' => "Analyse cette exploitation et donne des recommandations prioritaires.\n"
                . $this->describeFarmFacts($farm->toArray()) . "\n" . $this->describeMetrics($metrics, $byProduct)],
        ], 500);

        return [
            'metrics' => $metrics,
            'recommendations' => $recommendations,
            'summary' => $summary ?? $this->localSummary($farm, $metrics),
            'source' => $summary ? 'ai' : 'local',
        ];
    }

    private function describeMetrics(array $m, $byProduct): string
    {
        $products = $byProduct->take(5)->map(fn ($kg, $p) => "{$p} ({$kg} kg)")->implode(', ');

        return "- Récoltes enregistrées : {$m['harvest_count']}\n"
            . "- Production totale : {$m['total_kg']} kg\n"
            . '- Rendement moyen : ' . ($m['yield_per_ha'] ?? 'n/d') . " kg/ha\n"
            . '- Part qualité A : ' . ($m['grade_a_rate'] ?? 'n/d') . " %\n"
            . "- Stock non vendu : {$m['in_stock_kg']} kg\n"
            . '- Cycle moyen de culture : ' . ($m['avg_growing_days'] ?? 'n/d') . " jours\n"
            . '- Produits : ' . ($products ?: 'aucun');
    }

    private function localRecommendations(Farm $farm, array $m): array
    {
        $tips = [];

        if ($m['harvest_count'] === 0) {
            $tips[] = 'Enregistrez vos premières récoltes pour activer le suivi du rendement et la traçabilité par QR code.';
        }
        if (in_array($farm->governorate, self::ARID_GOVERNORATES) && in_array($farm->irrigation_type, ['gravity', 'sprinkler'])) {
            $tips[] = "Zone aride ({$farm->governorate}) : passer au goutte-à-goutte réduirait la consommation d'eau de 30 à 50 %.";
        }
        if ($farm->irrigation_type === 'rainfed' && !in_array($farm->farming_type, ['agroforestry', 'permaculture'])) {
            $tips[] = 'Culture pluviale : privilégiez des variétés résistantes à la sécheresse et le paillage pour conserver l\'humidité du sol.';
        }
        if ($m['grade_a_rate'] !== null && $m['grade_a_rate'] < 50) {
            $tips[] = "Seulement {$m['grade_a_rate']} % de vos lots sont en qualité A : améliorez le tri et la récolte aux heures fraîches.";
        }
        if ($m['total_kg'] > 0 && $m['in_stock_kg'] / $m['total_kg'] > 0.5) {
            $tips[] = 'Plus de la moitié de la production est encore en stock : planifiez l\'écoulement ou vérifiez les conditions de conservation.';
        }
        if ($m['harvest_count'] >= 3 && $m['categories'] === 1) {
            $tips[] = 'Une seule famille de cultures : introduisez une rotation (légumineuses, par exemple) pour préserver la fertilité du sol.';
        }
        if ($farm->soil_type === 'sandy') {
            $tips[] = 'Sol sableux : un apport régulier de compost améliore la rétention d\'eau et de nutriments.';
        }
        if ($farm->soil_type === 'limestone') {
            $tips[] = 'Sol calcaire : surveillez la chlorose ferrique, notamment sur les agrumes et les arbres fruitiers.';
        }
        if (!$farm->hasLocation()) {
            $tips[] = 'Ajoutez la position GPS de l\'exploitation : elle apparaîtra sur la carte de traçabilité vue par les consommateurs.';
        }
        if (!$farm->certification && $farm->farming_type === 'conventional') {
            $tips[] = 'Une démarche raisonnée ou biologique certifiée valoriserait vos produits auprès des consommateurs.';
        }
        $lastHarvest = $farm->harvests->max('harvest_date');
        if ($farm->status === 'active' && $lastHarvest && $lastHarvest->lt(now()->subDays(120))) {
            $tips[] = 'Aucune récolte déclarée depuis plus de 4 mois : pensez à mettre à jour votre registre.';
        }

        return $tips ?: ['Vos indicateurs sont bons : continuez à enregistrer chaque lot pour suivre l\'évolution de vos rendements.'];
    }

    private function localSummary(Farm $farm, array $m): string
    {
        if ($m['harvest_count'] === 0) {
            return "{$farm->name} n'a pas encore de récolte enregistrée. Les indicateurs apparaîtront dès le premier lot.";
        }

        $summary = "{$farm->name} totalise {$m['harvest_count']} récolte(s) pour " . number_format($m['total_kg'], 0, ',', ' ') . ' kg';
        $summary .= $m['yield_per_ha'] ? ', soit un rendement moyen de ' . number_format($m['yield_per_ha'], 0, ',', ' ') . ' kg/ha' : '';
        $summary .= $m['top_product'] ? ". Produit phare : {$m['top_product']}" : '';
        $summary .= $m['grade_a_rate'] !== null ? ", {$m['grade_a_rate']} % des lots en qualité A." : '.';

        return $summary;
    }

    // ==================== CHATBOT ====================

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array{reply: string, source: string}
     */
    public function chat(User $user, string $message, array $history = []): array
    {
        $farms = $user->farms()->with('harvests')->get();

        $messages = [[
            'role' => 'system',
            'content' => "Tu es « AgriBot », l'assistant du producteur sur la plateforme tunisienne de traçabilité Farm to Fork. "
                . "Tu réponds en français (ou en darija si l'utilisateur l'emploie), de façon concise (moins de 150 mots), "
                . "sur l'agronomie, le calendrier cultural tunisien, l'irrigation, la qualité, la traçabilité et l'utilisation du module "
                . "(menu Exploitations : ajouter/modifier/supprimer une ferme ; menu Récoltes : déclarer un lot, chaque lot reçoit un code et un QR code). "
                . "Utilise les données ci-dessous pour les questions sur ses exploitations ; n'invente aucun chiffre. Date du jour : " . now()->format('d/m/Y') . ".\n\n"
                . "Producteur : {$user->name} ({$user->company_name})\n" . $this->describeProducerData($farms),
        ]];

        foreach (array_slice($history, -8) as $turn) {
            $messages[] = ['role' => $turn['role'], 'content' => $turn['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        $reply = $this->complete($messages, 500);

        return $reply
            ? ['reply' => $reply, 'source' => 'ai']
            : ['reply' => $this->localChat($user, $farms, $message), 'source' => 'local'];
    }

    private function describeProducerData($farms): string
    {
        if ($farms->isEmpty()) {
            return 'Aucune exploitation enregistrée pour le moment.';
        }

        return $farms->map(function (Farm $f) {
            $recent = $f->harvests->sortByDesc('harvest_date')->take(5)
                ->map(fn (Harvest $h) => "{$h->product_name} {$h->quantity} {$h->unitShort()} le {$h->harvest_date->format('d/m/Y')} (qualité {$h->quality_grade}, {$h->statusLabel()})")
                ->implode('; ');

            return "• {$f->name} — {$f->city}, {$f->governorate}, {$f->total_area} ha, {$f->farmingTypeLabel()}, irrigation {$f->irrigationTypeLabel()}, "
                . "statut {$f->statusLabel()}, {$f->harvests->count()} récolte(s), total " . round($f->totalHarvestedKg()) . ' kg'
                . ($recent ? ". Dernières : {$recent}" : '');
        })->implode("\n");
    }

    /**
     * Chatbot hors-ligne : détection d'intention par mots-clés.
     */
    private function localChat(User $user, $farms, string $message): string
    {
        $q = Str::lower(Str::ascii($message));
        $has = fn (string $pattern) => preg_match("/{$pattern}/", $q) === 1;
        $harvests = $farms->flatMap->harvests;
        $kg = fn ($n) => number_format($n, 0, ',', ' ') . ' kg';

        if ($has('^(bonjour|salut|hello|bonsoir|ahla|aslema|salam|hi)\b')) {
            return "Bonjour {$user->name} ! Je suis AgriBot. Demandez-moi par exemple : « résume mes exploitations », « quel est mon stock ? », « que planter ce mois-ci ? » ou « comment générer un QR code ? ».";
        }

        if ($has('(qr|tracabilit|code.*lot|consommateur)')) {
            return 'Chaque récolte enregistrée reçoit automatiquement un code de lot (LOT-AAAAMMJJ-XXXXX). Ouvrez la récolte depuis le menu « Récoltes » : son QR code s\'affiche et peut être imprimé. En le scannant, le consommateur voit l\'exploitation, la parcelle, les dates et la carte d\'origine.';
        }

        if ($has('(comment|ajouter|creer|modifier|supprimer|enregistrer|declarer)')) {
            return "Pour gérer vos exploitations : menu « Exploitations » → « Nouvelle exploitation » (le bouton ✨ rédige la description pour vous). Pour déclarer un lot : menu « Récoltes » → « Nouvelle récolte ». Les boutons ✏️ et 🗑️ de chaque ligne permettent de modifier ou supprimer.";
        }

        if ($farms->isEmpty() && $has('(exploitation|ferme|recolte|stock|production|rendement)')) {
            return "Vous n'avez encore aucune exploitation. Commencez par en créer une depuis le menu « Exploitations ».";
        }

        if ($has('(stock|invendu|reste)')) {
            $stock = $harvests->where('status', 'in_stock');
            if ($stock->isEmpty()) {
                return 'Aucun lot en stock actuellement : tous vos lots sont expédiés, transformés ou vendus.';
            }
            $lines = $stock->groupBy('product_name')->map(fn ($g, $p) => "• {$p} : " . $kg($g->sum(fn ($h) => $h->quantityInKg())))->implode("\n");

            return "Vous avez {$stock->count()} lot(s) en stock, soit " . $kg($stock->sum(fn ($h) => $h->quantityInKg())) . " :\n{$lines}";
        }

        if ($has('(rendement|meilleur|performan|productiv)')) {
            $ranked = $farms->filter(fn ($f) => $f->harvests->isNotEmpty())->sortByDesc(fn ($f) => $f->yieldPerHectare());
            if ($ranked->isEmpty()) {
                return 'Aucune récolte enregistrée : impossible de calculer un rendement pour l\'instant.';
            }
            $lines = $ranked->map(fn ($f) => "• {$f->name} : " . number_format($f->yieldPerHectare(), 0, ',', ' ') . ' kg/ha')->implode("\n");

            return "Rendement moyen par exploitation :\n{$lines}\nMeilleure performance : {$ranked->first()->name}.";
        }

        if ($has('(derniere|recent|dernier)')) {
            $last = $harvests->sortByDesc('harvest_date')->first();

            return $last
                ? "Dernière récolte : {$last->product_name} ({$last->quantity} {$last->unitShort()}) le {$last->harvest_date->format('d/m/Y')} sur « {$last->farm->name} », lot {$last->batch_code}."
                : 'Aucune récolte enregistrée pour le moment.';
        }

        if ($has('(total|combien|production|recolte|resume|bilan|statistique|exploitation|ferme)')) {
            $top = $harvests->groupBy('product_name')->map(fn ($g) => $g->sum(fn ($h) => $h->quantityInKg()))->sortDesc()->take(3);
            $reply = "Vous gérez {$farms->count()} exploitation(s) sur " . number_format($farms->sum('total_area'), 2, ',', ' ') . " ha, avec {$harvests->count()} récolte(s) pour un total de " . $kg($harvests->sum(fn ($h) => $h->quantityInKg())) . '.';
            if ($top->isNotEmpty()) {
                $reply .= "\nTop produits :\n" . $top->map(fn ($v, $p) => "• {$p} : " . $kg($v))->implode("\n");
            }

            return $reply;
        }

        if ($has('(planter|semer|semis|saison|calendrier|cultiver|mois)')) {
            return $this->seasonalAdvice((int) now()->month);
        }

        if ($has('(irrigation|eau|arros|secheresse|goutte)')) {
            return "Conseils irrigation : le goutte-à-goutte économise 30 à 50 % d'eau par rapport à l'aspersion ; arrosez tôt le matin ou le soir ; le paillage limite l'évaporation. En zone aride (Sud, Centre), privilégiez des variétés tolérantes à la sécheresse.";
        }

        if ($has('(bio|certifi|label|pesticide|traitement)')) {
            return "En agriculture biologique, les produits de synthèse sont interdits : le module refuse d'ailleurs un traitement comme le glyphosate sur une exploitation bio. Une exploitation bio doit indiquer son organisme certificateur (ex. : CNAB, Ecocert).";
        }

        if ($has('(qualite|grade|tri)')) {
            return 'Les lots sont classés A (Extra), B (Catégorie I) ou C (Catégorie II). Pour augmenter la part en A : récoltez à maturité aux heures fraîches, triez rapidement et stockez à l\'abri de la chaleur.';
        }

        return "Je n'ai pas bien compris. Je peux vous aider sur : le résumé de vos exploitations, votre stock, vos rendements, votre dernière récolte, le calendrier des cultures, l'irrigation, le bio, la qualité ou le QR code de traçabilité."
            . ($this->isEnabled() ? '' : "\n\n(Mode hors-ligne : ajoutez AI_API_KEY dans .env pour des réponses plus riches.)");
    }

    /**
     * Conseil cultural du mois (calendrier tunisien), aussi affiché sur le tableau de bord producteur.
     */
    public function seasonalAdvice(int $month): string
    {
        $calendar = [
            [[12, 1, 2], 'semer fèves, petits pois, oignons et pommes de terre primeur ; c\'est la pleine saison de la récolte des agrumes et la fin de celle des olives.'],
            [[3, 4], 'repiquer tomates, poivrons, piments et courgettes en plein champ ; semer melons et pastèques ; surveiller les céréales en montaison.'],
            [[5, 6], 'moissonner blé dur et orge ; récolter abricots, pêches et fèves sèches ; semer haricots et gombo.'],
            [[7, 8], 'récolter tomates, poivrons, melons et pastèques ; préparer le sol et les pépinières pour l\'arrière-saison ; irriguer aux heures fraîches.'],
            [[9, 10], 'planter pommes de terre d\'arrière-saison, carottes, fenouil, laitues et choux ; récolter grenades et premières dattes Deglet Nour.'],
            [[11], 'semer blé et orge après les premières pluies ; récolter olives et dattes ; planter les arbres fruitiers à racines nues.'],
        ];

        $names = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        foreach ($calendar as [$months, $advice]) {
            if (in_array($month, $months)) {
                return "En {$names[$month]} en Tunisie : {$advice}";
            }
        }

        return 'Consultez le calendrier cultural de votre CRDA pour votre région.';
    }

    /**
     * Question libre au modèle (utilisée par ProductIntelligence). Retourne null sans clé ou en cas d'erreur.
     */
    public function ask(string $system, string $user, int $maxTokens = 400): ?string
    {
        return $this->complete([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ], $maxTokens);
    }

    // ==================== APPEL API ====================

    /**
     * Appel d'une API « chat completions » compatible OpenAI. Retourne null si indisponible.
     */
    private function complete(array $messages, int $maxTokens): ?string
    {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            $response = Http::withToken(config('services.ai.key'))
                ->acceptJson()
                ->timeout(25)
                ->post(rtrim(config('services.ai.base_url'), '/') . '/chat/completions', [
                    'model' => config('services.ai.model'),
                    'messages' => $messages,
                    'temperature' => 0.6,
                    'max_tokens' => $maxTokens,
                ]);

            if ($response->failed()) {
                Log::warning('AI API error', ['status' => $response->status(), 'body' => Str::limit($response->body(), 500)]);

                return null;
            }

            return trim((string) $response->json('choices.0.message.content')) ?: null;
        } catch (\Throwable $e) {
            Log::warning('AI API unreachable: ' . $e->getMessage());

            return null;
        }
    }
}
