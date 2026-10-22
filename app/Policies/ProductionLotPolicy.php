<?php

namespace App\Policies;

use App\Models\ProductionLot;
use App\Models\User;

class ProductionLotPolicy
{
    /**
     * Déterminer si l'utilisateur peut voir tous les lots de production
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'pro' && $user->pro_type === 'processor';
    }

    /**
     * Déterminer si l'utilisateur peut voir un lot de production
     */
    public function view(User $user, ProductionLot $lot): bool
    {
        // Un transformateur ne voit que ses propres lots
        return $user->id === $lot->processor_id;
    }

    /**
     * Déterminer si l'utilisateur peut créer un lot de production
     */
    public function create(User $user): bool
    {
        // Seuls les transformateurs approuvés peuvent créer
        return $user->role === 'pro' && $user->pro_type === 'processor' && $user->status === 'approved';
    }

    /**
     * Déterminer si l'utilisateur peut modifier un lot de production
     */
    public function update(User $user, ProductionLot $lot): bool
    {
        return $user->id === $lot->processor_id;
    }

    /**
     * Déterminer si l'utilisateur peut supprimer un lot de production
     */
    public function delete(User $user, ProductionLot $lot): bool
    {
        return $user->id === $lot->processor_id;
    }
}
