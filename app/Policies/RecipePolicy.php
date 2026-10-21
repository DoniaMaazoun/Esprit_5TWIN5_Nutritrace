<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\User;

class RecipePolicy
{
    /**
     * Déterminer si l'utilisateur peut voir une recette
     */
    public function view(User $user, Recipe $recipe): bool
    {
        // Un transformateur ne voit que ses propres recettes
        return $user->id === $recipe->processor_id;
    }

    /**
     * Déterminer si l'utilisateur peut créer une recette
     */
    public function create(User $user): bool
    {
        // Seuls les transformateurs approuvés peuvent créer
        return $user->role === 'pro' && $user->pro_type === 'processor' && $user->status === 'approved';
    }

    /**
     * Déterminer si l'utilisateur peut modifier une recette
     */
    public function update(User $user, Recipe $recipe): bool
    {
        return $user->id === $recipe->processor_id;
    }

    /**
     * Déterminer si l'utilisateur peut supprimer une recette
     */
    public function delete(User $user, Recipe $recipe): bool
    {
        return $user->id === $recipe->processor_id;
    }
}
