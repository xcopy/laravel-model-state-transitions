<?php

namespace Jenishev\Laravel\ModelStateTransitions\Contracts;

use BackedEnum;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Defines the contract for models that support state transitions.
 *
 * This interface ensures that models implementing state transitions
 * provide a method to resolve their associated state enum class.
 * The state enum is used for type-safe state handling and automatic
 * casting between database values and enum instances.
 *
 * Example implementation:
 * ```php
 * class Order extends Model implements HasStateTransitions
 * {
 *     public static function resolveStateEnum(): string
 *     {
 *         return OrderStateEnum::class;
 *     }
 * }
 * ```
 */
interface HasStateTransitions
{
    /**
     * Get the transition history for the model.
     *
     * Returns all recorded state transitions that have occurred on this model,
     * ordered by creation date. Each history record includes the previous state,
     * new state, timestamp, and optional custom properties.
     *
     * Example usage:
     * ```php
     * // Get all transitions for an order
     * $order->transitionHistory;
     *
     * // Get the latest transition
     * $order->transitionHistory()->latest()->first();
     * ```
     */
    public function transitionHistory(): MorphMany;

    /**
     * Get the available transitions for the given user.
     *
     * Queries and returns all valid state transitions from the model's current
     * state that the specified user (or authenticated user) is authorized to
     * perform. Authorization is determined by checking if the user or any of
     * their roles have been assigned the transition.
     *
     * Example usage:
     * ```php
     * // Get transitions for current authenticated user
     * $order->transitions()->get();
     *
     * // Get transitions for a specific user
     * $order->transitions($user)->get();
     *
     * // Check if a user can transition to a specific state
     * $order->transitions()->where('to_state', 'approved')->exists();
     * ```
     *
     * @param  Authenticatable|null  $user  The user to check permissions for (defaults to authenticated user)
     */
    public function transitions(?Authenticatable $user = null): EloquentBuilder;

    /**
     * Resolve the state enum for the model.
     *
     * Returns the fully qualified class name of the BackedEnum that
     * represents valid states for this model. This enum is used by
     * the state transition system to cast and validate state values.
     *
     * @return class-string<BackedEnum> The fully qualified enum class name
     */
    public static function resolveStateEnum(): string;
}
