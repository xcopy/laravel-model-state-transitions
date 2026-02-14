<?php

namespace Jenishev\Laravel\ModelStateTransitions\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Provides polymorphic many-to-many relationship functionality for models
 * that can be associated with state transitions.
 *
 * This trait allows Users, Roles, or any other model to define which
 * state transitions they are authorized to perform through a pivot table.
 */
trait HasAttachedTransitions
{
    /**
     * Get the transitions associated with the model.
     *
     * This method establishes a polymorphic many-to-many relationship between
     * the model (User, Role, etc.) and Transition models through a pivot table.
     * It allows you to define which state transitions are available to
     * specific users or roles.
     *
     * Example usage:
     * ```php
     * // Get all transitions available to a user
     * $user->transitions;
     *
     * // Attach a transition to a user
     * $user->transitions()->attach($transitionId);
     *
     * // Check if a user has a specific transition
     * $user->transitions()->where('to_state', 'approved')->exists();
     * ```
     */
    public function transitions(): MorphToMany
    {
        $config = config('model-state-transitions');

        return $this->morphToMany(
            related: $config['transition_model'],
            name: 'model',
            table: $config['pivot_table'],
            relatedPivotKey: 'transition_id'
        );
    }
}
