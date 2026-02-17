<?php

namespace Jenishev\Laravel\ModelStateTransitions\Concerns;

use BackedEnum;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use RuntimeException;

/**
 * Provides state transition functionality for Eloquent models.
 *
 * This trait adds the ability to track state changes and query available
 * transitions based on the current state and user permissions. It integrates
 * with the role-based access control system to determine which transitions a user can perform.
 *
 * Example usage:
 * ```php
 * use Jenishev\Laravel\ModelStateTransitions\Contracts\HasStateTransitions as HasStateTransitionsContract;
 * use Jenishev\Laravel\ModelStateTransitions\Concerns\HasStateTransitions as HasStateTransitionsConcern;
 * class Order extends Model implements HasStateTransitionsContract
 * {
 *     use HasStateTransitionsConcern;
 * }
 *
 * // Simple state transition
 * $order->state = OrderStateEnum::Approved;
 * $order->save();
 *
 * // Transition with description and custom properties
 * $order->stateTransitionTo(
 *     state: OrderStateEnum::Approved,
 *     description: 'Approved by manager',
 *     custom_properties: [...]
 * );
 *
 * // Or set metadata before updating
 * $order->setTransitionMetadata(
 *     description: 'Rejected due to fraud',
 *     custom_properties: [...]
 * );
 * $order->state = OrderStateEnum::Rejected;
 * $order->save();
 * ```
 */
trait HasStateTransitions
{
    /**
     * Transition metadata to be saved with the history record.
     *
     * @var array{description?: string|null, custom_properties?: array|null}
     */
    protected array $transitionMetadata = [];

    /**
     * Boot the HasStateTransitions trait for a model.
     */
    public static function bootHasStateTransitions(): void
    {
        static::updated(function (self $model) {
            $state_column = config('model-state-transitions.transitionable_state_column');

            $description = Arr::get($model->transitionMetadata, 'description');
            $custom_properties = Arr::get($model->transitionMetadata, 'custom_properties');

            if (
                $model->wasChanged($state_column) ||
                $description ||
                $custom_properties
            ) {
                $model->transitionHistory()->create([
                    'from_state' => $model->getOriginal($state_column),
                    'to_state' => $model->{$state_column},
                    'description' => $description,
                    'custom_properties' => $custom_properties,
                ]);
            }

            // Clear metadata after recording
            $model->transitionMetadata = [];
        });
    }

    /**
     * Transition the model to a new state with optional metadata.
     *
     * This is a convenience method that sets the state and saves the model
     * in a single call, while also allowing you to provide description and
     * custom properties for the transition history.
     *
     * @param  BackedEnum  $state  The new state to transition to
     * @param  string|null  $description  Optional description of the transition
     * @param  array|null  $custom_properties  Optional custom properties to store with the transition
     * @return bool True if the model was successfully saved
     */
    public function stateTransitionTo(
        BackedEnum $state,
        ?string $description = null,
        ?array $custom_properties = null
    ): bool {
        $state_column = config('model-state-transitions.transitionable_state_column');

        $this->{$state_column} = $state;

        $this->setTransitionMetadata($description, $custom_properties);

        return $this->save();
    }

    /**
     * Set metadata for the next state transition.
     *
     * This allows you to provide description and custom properties that will
     * be recorded when the state changes and the model is saved.
     *
     * @param  string|null  $description  Optional description of the transition
     * @param  array|null  $custom_properties  Optional custom properties to store
     * @return $this
     */
    public function setTransitionMetadata(
        ?string $description = null,
        ?array $custom_properties = null
    ): static {
        if (filled($description)) {
            $this->transitionMetadata['description'] = $description;
        }

        if (filled($custom_properties)) {
            $this->transitionMetadata['custom_properties'] = $custom_properties;
        }

        return $this;
    }

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
    public function transitionHistory(): MorphMany
    {
        $config = config('model-state-transitions');

        return $this->morphMany($config['transition_history_model'], 'model');
    }

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
    public function transitions(?Authenticatable $user = null): EloquentBuilder
    {
        $config = config('model-state-transitions');

        $transition_model = $config['transition_model'];

        /** @var EloquentBuilder $builder */
        $builder = call_user_func([$transition_model, 'query']);

        $baseQuery = $builder
            ->where('model_type', static::class)
            ->where('from_state', $this->{$config['transitionable_state_column']});

        // Return an empty result if no user is authenticated
        if (! $user ??= auth()->user()) {
            return $baseQuery->whereRaw('false');
        }

        return $baseQuery
            ->whereIn('id', function (QueryBuilder $query) use ($config, $user) {
                $query->select('transition_id')
                    ->from($config['pivot_table'])
                    ->where(function (QueryBuilder $query) use ($config, $user) {
                        $query->where([
                            'model_type' => $config['user_model'],
                            'model_id' => $user->getKey(),
                        ]);

                        if (method_exists($user, 'roles')) {
                            $query->orWhere(function (QueryBuilder $query) use ($config, $user) {
                                $query->where('model_type', $config['role_model'])
                                    ->whereIn('model_id', $user->roles->pluck('id'));
                            });
                        }
                    });
            });
    }

    /**
     * Resolve the state enum for the model.
     *
     * Returns the fully qualified class name of the BackedEnum that represents
     * valid states for this model. By default, it follows the convention of
     * `App\Enums\{ModelName}StateEnum` (e.g., `App\Enums\OrderStateEnum` for
     * the `Order` model).
     *
     * Override this method to use a custom enum class or different naming convention.
     *
     * Example override:
     * ```php
     * public static function resolveStateEnum(): string
     * {
     *     return \App\Enums\CustomStateEnum::class;
     * }
     * ```
     *
     * @return class-string<BackedEnum> The fully qualified enum class name
     *
     * @throws RuntimeException If the resolved enum class does not exist
     */
    public static function resolveStateEnum(): string
    {
        $stateEnum = str(class_basename(static::class))
            ->prepend('App\\Enums\\')
            ->append('StateEnum');

        throw_unless(
            enum_exists($stateEnum),
            RuntimeException::class,
            sprintf('State enum not found for model: %s. Expected: %s', static::class, $stateEnum)
        );

        return $stateEnum;
    }
}
