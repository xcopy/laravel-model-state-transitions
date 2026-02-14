<?php

namespace Jenishev\Laravel\ModelStateTransitions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Jenishev\Laravel\ModelStateTransitions\Concerns\HasStateAttributes;

/**
 * Represents a recorded state transition in the audit trail.
 *
 * This model stores the history of all state transitions that have occurred
 * on transitionable models. Each record captures the previous state, new state,
 * timestamp, optional description, and custom properties for auditing purposes.
 */
class TransitionHistory extends Model
{
    use HasStateAttributes;

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'model_type',
        'model_id',
        'from_state',
        'to_state',
        'description',
        'custom_properties',
    ];

    /**
     * {@inheritDoc}
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('model-state-transitions.transition_history_table');
    }

    /**
     * {@inheritDoc}
     */
    protected function casts(): array
    {
        return [
            'model_type' => 'string', // todo
            'model_id' => 'integer', // todo
            'description' => 'string',
            'custom_properties' => 'array',
        ];
    }

    /**
     * Get the parent model that the transition history belongs to.
     *
     * @return MorphTo<Model, $this>
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
