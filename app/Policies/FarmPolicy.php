<?php

namespace App\Policies;

use App\Models\Farm;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Exploitations : tout producteur peut consulter celles des autres,
 * seul le propriétaire peut les modifier ou supprimer. L'admin a tous les droits.
 */
class FarmPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Farm $farm): bool
    {
        return $user->isProducer();
    }

    public function update(User $user, Farm $farm): Response
    {
        return $farm->user_id === $user->id
            ? Response::allow()
            : Response::deny('Vous ne pouvez pas modifier l\'exploitation d\'un autre producteur.');
    }

    public function delete(User $user, Farm $farm): Response
    {
        return $farm->user_id === $user->id
            ? Response::allow()
            : Response::deny('Vous ne pouvez pas supprimer l\'exploitation d\'un autre producteur.');
    }
}
