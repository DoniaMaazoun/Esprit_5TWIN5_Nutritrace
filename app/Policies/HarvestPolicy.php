<?php

namespace App\Policies;

use App\Models\Harvest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Produits (récoltes) : visibles par tous les producteurs, modifiables et
 * supprimables uniquement par le producteur propriétaire. L'admin a tous les droits.
 */
class HarvestPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Harvest $harvest): bool
    {
        return $user->isProducer();
    }

    public function update(User $user, Harvest $harvest): Response
    {
        return $harvest->farm->user_id === $user->id
            ? Response::allow()
            : Response::deny('Vous ne pouvez pas modifier le produit d\'un autre producteur.');
    }

    public function delete(User $user, Harvest $harvest): Response
    {
        return $harvest->farm->user_id === $user->id
            ? Response::allow()
            : Response::deny('Vous ne pouvez pas supprimer le produit d\'un autre producteur.');
    }
}
