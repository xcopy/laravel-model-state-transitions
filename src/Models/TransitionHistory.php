<?php

namespace Jenishev\Laravel\ModelStateTransitions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Jenishev\Laravel\ModelStateTransitions\Concerns\HasStateAttributes;
use Jenishev\Laravel\ModelStateTransitions\Contracts\HasStateTransitions;
use Jenishev\Laravel\Support\Eloquent\Casts\AsModelClass;
use RichanFongdasen\EloquentBlameable\BlameableTrait;

/**
 * Represents a recorded state transition in the audit trail.
 *
 * This model stores the history of all state transitions that have occurred
 * on transitionable models. Each record captures the previous state, new state,
 * timestamp, optional description, and custom properties for auditing purposes.
 *
 * @property string $model_type
 */
class TransitionHistory extends Model
{
    use BlameableTrait;
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
        'created_by',
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
    public function blameable(): array
    {
        return [
            'user' => config('model-state-transitions.user_model'),
            'createdBy' => 'created_by',
            'updatedBy' => null,
            'deletedBy' => null,
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function casts(): array
    {
        return [
            'model_type' => AsModelClass::of(HasStateTransitions::class),
            'model_id' => 'integer',
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
