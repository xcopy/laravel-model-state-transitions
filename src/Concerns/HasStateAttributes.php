<?php

namespace Jenishev\Laravel\ModelStateTransitions\Concerns;

use BackedEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Provides automatic casting of state attributes to and from backed enums.
 *
 * This trait automatically converts database string values to enum instances
 * and vice versa, allowing you to work with strongly typed state values
 * throughout your application. It handles both 'from_state' and 'to_state'
 * attributes seamlessly.
 *
 * The trait expects the model to have a 'model_type' property and implements
 * the resolveStateEnum() method to determine the appropriate enum class.
 *
 * Example usage:
 * ```php
 * $transition->from_state; // Returns OrderStateEnum instance
 * $transition->to_state = OrderStateEnum::Approved; // Saves as string
 * ```
 */
trait HasStateAttributes
{
    /**
     * Get the "from_state" attribute.
     *
     * Automatically casts the from_state database column to the appropriate
     * state enum instance when retrieving and converts the enum back to string
     * when saving to the database.
     *
     * This accessor enables type-safe state handling, preventing invalid
     * state values and providing IDE autocompletion for available states.
     *
     * @return Attribute<BackedEnum|null, BackedEnum|string>
     */
    protected function fromState(): Attribute
    {
        return $this->makeStateAttribute();
    }

    /**
     * Get the "to_state" attribute.
     *
     * Automatically casts the to_state database column to the appropriate
     * state enum instance when retrieving and converts the enum back to string
     * when saving to the database.
     *
     * This accessor enables type-safe state handling, preventing invalid
     * state values and providing IDE autocompletion for available states.
     *
     * @return Attribute<BackedEnum|null, BackedEnum|string>
     */
    protected function toState(): Attribute
    {
        return $this->makeStateAttribute();
    }

    /**
     * Creates a state attribute cast.
     *
     * This method builds an Attribute instance that handles bidirectional
     * conversion between database string values and enum instances. It uses
     * the model's resolveStateEnum() method to determine the correct enum
     * class to use for casting.
     *
     * The getter converts database strings to enum instances, returning null
     * for blank values. The setter accepts either enum instances or strings,
     * automatically extracting the value from enum instances.
     *
     * @return Attribute<BackedEnum|null, BackedEnum|string>
     */
    private function makeStateAttribute(): Attribute
    {
        return Attribute::make(
            get: function ($value): ?BackedEnum {
                if (blank($value)) {
                    return null;
                }

                /** @var BackedEnum $stateEnum */
                $stateEnum = call_user_func([$this->model_type, 'resolveStateEnum']);

                return $stateEnum::from($value);
            },
            set: fn ($value): string => $value instanceof BackedEnum ? $value->value : $value
        );
    }
}
