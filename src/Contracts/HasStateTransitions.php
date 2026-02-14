<?php

namespace Jenishev\Laravel\ModelStateTransitions\Contracts;

use BackedEnum;

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
