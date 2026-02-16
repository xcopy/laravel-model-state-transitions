<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Transitions Table
    |--------------------------------------------------------------------------
    |
    | This table stores the available state transitions for your models.
    | Each transition defines a valid state change (from_state -> to_state)
    | for a specific model type.
    |
    */

    'transitions_table' => 'transitions',

    /*
    |--------------------------------------------------------------------------
    | Transition History Table
    |--------------------------------------------------------------------------
    |
    | This table maintains a complete audit trail of all state transitions
    | that have occurred on your models. Each record includes the old state,
    | new state, timestamp, and optional custom properties.
    |
    */

    'transition_history_table' => 'transition_history',

    /*
    |--------------------------------------------------------------------------
    | Pivot Table
    |--------------------------------------------------------------------------
    |
    | This pivot table connects transitions with users and roles, defining
    | which transitions are available to specific users or roles. This
    | enables role-based access control for state transitions.
    |
    */

    'pivot_table' => 'model_has_transitions',

    /*
    |--------------------------------------------------------------------------
    | Transitionable State Column
    |--------------------------------------------------------------------------
    |
    | The database column name on your models that stores the current state.
    | By default, this is 'state', but you can customize it to match your
    | existing schema (e.g., 'status', 'current_state', etc.).
    |
    */

    'transitionable_state_column' => 'state',

    /*
    |--------------------------------------------------------------------------
    | Transition Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model class used to represent transitions in your
    | application. You can extend this model to add custom methods
    | or attributes specific to your application's needs.
    |
    */

    'transition_model' => Jenishev\Laravel\ModelStateTransitions\Models\Transition::class,
    // 'transition_model' => App\Models\Transition::class,

    /*
    |--------------------------------------------------------------------------
    | Transition History Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model class used to represent transition history records.
    | You can extend this model to customize the history tracking behavior
    | or add additional relationships and accessors.
    |
    */

    'transition_history_model' => Jenishev\Laravel\ModelStateTransitions\Models\TransitionHistory::class,
    // 'transition_history_model' => App\Models\TransitionHistory::class,

    /*
    |--------------------------------------------------------------------------
    | Role Model
    |--------------------------------------------------------------------------
    |
    | The fully qualified class name of your application's Role model.
    | This is used to determine which transitions are available based on
    | the authenticated user's assigned roles.
    |
    */

    'role_model' => 'App\Models\Role',

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The fully qualified class name of your application's User model.
    | This should typically match the model defined in your auth.php
    | configuration file.
    |
    */

    'user_model' => 'App\Models\User',

];
