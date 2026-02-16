<?php

namespace Jenishev\Laravel\ModelStateTransitions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Jenishev\Laravel\ModelStateTransitions\Concerns\HasStateAttributes;
use Jenishev\Laravel\ModelStateTransitions\Contracts\HasStateTransitions;
use Jenishev\Laravel\Support\Eloquent\Casts\AsModelClass;

/**
 * Represents a state transition definition.
 *
 * This model stores the available state transitions for various models,
 * defining valid state changes (from_state -> to_state) and which users
 * or roles are authorized to perform each transition.
 */
class Transition extends Model
{
    use HasStateAttributes;

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'model_type',
        'from_state',
        'to_state',
    ];

    /**
     * {@inheritDoc}
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('model-state-transitions.transitions_table');
    }

    /**
     * {@inheritDoc}
     */
    protected function casts(): array
    {
        return [
            'model_type' => AsModelClass::of(HasStateTransitions::class),
        ];
    }

    /**
     * Get the users that can perform this transition.
     *
     * This establishes a polymorphic many-to-many relationship with the
     * User model, allowing specific users to be granted permission to
     * execute this state transition.
     *
     * Example usage:
     * ```php
     * // Attach users to a transition
     * $transition->users()->attach($userId);
     *
     * // Get all users who can perform this transition
     * $transition->users;
     * ```
     */
    public function users(): MorphToMany
    {
        $config = config('model-state-transitions');

        return $this->morphedByMany(
            related: $config['user_model'],
            name: 'model',
            table: $config['pivot_table'],
            foreignPivotKey: 'transition_id'
        );
    }

    /**
     * Get the roles that can perform this transition.
     *
     * This establishes a polymorphic many-to-many relationship with the
     * Role model, enabling role-based access control for state transitions.
     * When a user has any of these roles, they can perform the transition.
     *
     * Example usage:
     * ```php
     * // Attach roles to a transition
     * $transition->roles()->attach($roleId);
     *
     * // Check if an admin role can perform this transition
     * $transition->roles()->where('name', 'admin')->exists();
     * ```
     */
    public function roles(): MorphToMany
    {
        $config = config('model-state-transitions');

        return $this->morphedByMany(
            related: $config['role_model'],
            name: 'model',
            table: $config['pivot_table'],
            foreignPivotKey: 'transition_id'
        );
    }
}
