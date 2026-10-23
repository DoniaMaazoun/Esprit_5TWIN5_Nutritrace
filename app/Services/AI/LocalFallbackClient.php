<?php

namespace App\Services\AI;

/**
 * Client AI local basé sur des règles
 * Utilisé quand l'API n'est pas configurée ou échoue
 */
class LocalFallbackClient implements AIClientInterface
{
    public function isEnabled(): bool
    {
        return true; // Toujours disponible
    }

    public function complete(array $messages, int $maxTokens = 400): ?string
    {
        // Pour le moment, retourne null pour forcer l'utilisation de réponses pré-craftées
        // dans NutriAIService. Le fallback local sera implémenté avec des règles
        // spécifiques aux questions consommateurs dans une étape ultérieure.
        return null;
    }
}
