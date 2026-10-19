<?php

namespace App\Policies;

use App\Models\RawMaterialReception;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RawMaterialReceptionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Only processors (transformateurs) can view receptions
        return $user->role === 'pro' && $user->pro_type === 'processor' && $user->status === 'approved';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RawMaterialReception $rawMaterialReception): bool
    {
        // Only the processor who created the reception can view it
        return $user->id === $rawMaterialReception->processor_id 
            && $user->role === 'pro' 
            && $user->pro_type === 'processor';
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only approved processors can create receptions
        return $user->role === 'pro' 
            && $user->pro_type === 'processor' 
            && $user->status === 'approved';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RawMaterialReception $rawMaterialReception): bool
    {
        // Only the processor who created it can update (and only if pending)
        return $user->id === $rawMaterialReception->processor_id 
            && $rawMaterialReception->status === 'pending'
            && $user->role === 'pro' 
            && $user->pro_type === 'processor';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RawMaterialReception $rawMaterialReception): bool
    {
        // Only the processor who created it can delete (and only if pending)
        return $user->id === $rawMaterialReception->processor_id 
            && $rawMaterialReception->status === 'pending'
            && $user->role === 'pro' 
            && $user->pro_type === 'processor';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RawMaterialReception $rawMaterialReception): bool
    {
        return false; // Not implemented
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RawMaterialReception $rawMaterialReception): bool
    {
        return false; // Not implemented
    }
}

