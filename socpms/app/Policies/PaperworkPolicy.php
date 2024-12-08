<?php

namespace App\Policies;

use App\Models\Paperwork;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PaperworkPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Paperwork $paperwork): bool
    {
        return $user->user_type === 'admin' || $user->id === $paperwork->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Paperwork $paperwork): bool
    {
        return $user->user_type === 'admin' || $user->id === $paperwork->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Paperwork $paperwork): bool
    {
        return $user->user_type === 'admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Paperwork $paperwork): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Paperwork $paperwork): bool
    {
        return false;
    }

    /**
     * Determine whether the user can approve the model.
     */
    public function approve(User $user, Paperwork $paperwork): bool
    {
        return $user->user_type === 'admin';
    }
}
