<?php

namespace App\Services\AI;

/**
 * Interface pour les clients AI (Groq, OpenAI, local fallback)
 * Permet de changer de fournisseur sans modifier la logique métier
 */
interface AIClientInterface
{
    /**
     * Appel chat completions compatible OpenAI
     *
     * @param  array  $messages Tableau de messages avec 'role' et 'content'
     * @param  int  $maxTokens Nombre maximum de tokens
     * @return string|null La réponse ou null si indisponible
     */
    public function complete(array $messages, int $maxTokens = 400): ?string;

    /**
     * Vérifie si le client est configuré et disponible
     *
     * @return bool
     */
    public function isEnabled();
}
