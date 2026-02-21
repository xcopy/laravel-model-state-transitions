# Laravel Model State Transitions

[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/xcopy/laravel-model-state-transitions/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/xcopy/laravel-model-state-transitions/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/xcopy/laravel-model-state-transitions/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/xcopy/laravel-model-state-transitions/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/xcopy/laravel-model-state-transitions.svg?style=flat-square)](https://packagist.org/packages/xcopy/laravel-model-state-transitions)

Manage state transitions for your Laravel models with role-based access control and automatic history tracking.

## Features

- **Define valid state transitions** for any Eloquent model
- **Role-based access control (RBAC)** for transitions - users and roles can be authorized
- **Automatic transition history tracking** with complete audit trail and user attribution
- **Type-safe enum support** – automatic casting between database values and BackedEnum instances
- **Polymorphic relationships** for flexible model support
- **Custom properties and descriptions** for each transition with metadata support
- **Query available transitions** based on current state and user permissions
- **Convenience methods** for state transitions with metadata (`stateTransitionTo()`, `setTransitionMetadata()`)
- **Automatic user tracking** via EloquentBlameable integration

## Installation

**Note:** This package is not yet available on Packagist. You must add it to your `composer.json` manually.

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/xcopy/laravel-model-state-transitions"
        }
    ],
    "require": {
        "xcopy/laravel-model-state-transitions": "dev-main"
    }
}
```

Run the installation commands:

```bash
php artisan vendor:publish --provider="Jenishev\Laravel\ModelStateTransitions\ModelStateTransitionsServiceProvider" --tag=config
php artisan vendor:publish --provider="Jenishev\Laravel\ModelStateTransitions\ModelStateTransitionsServiceProvider" --tag=migrations
php artisan migrate
```

## Quick Start

### 1. Create a State Enum

```php
namespace App\Enums;

enum PaymentStateEnum: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    // ...
}
```

### 2. Add Trait to Your Model

```php
use Jenishev\Laravel\ModelStateTransitions\Concerns\HasStateTransitions as HasStateTransitionsConcern;
use Jenishev\Laravel\ModelStateTransitions\Contracts\HasStateTransitions as HasStateTransitionsContract;

class Payment extends Model implements HasStateTransitionsContract
{
    use HasStateTransitionsConcern;

    protected $fillable = [
        // ...
        'state'
    ];

    protected function casts(): array
    {
        return [
            'state' => PaymentStateEnum::class,
            // ... other casts
        ];
    }

    // Override only if using custom enum naming convention,
    // By default, expects: App\Enums\{ModelName}StateEnum
    public static function resolveStateEnum(): string
    {
        return PaymentStateEnum::class;
    }
}
```

### 3. Define Transitions

```php
use Jenishev\Laravel\ModelStateTransitions\Models\Transition;

// Create a transition definition
$transition = Transition::create([
    'model_type' => Payment::class,
    'from_state' => PaymentStateEnum::Pending,
    'to_state' => PaymentStateEnum::Paid,
]);

// Authorize specific users
$transition->users()->attach($userId);

// Authorize by role
$transition->roles()->attach($roleId);

// Or authorize multiple at once
$transition->users()->attach([$userId1, $userId2]);
$transition->roles()->attach([$adminRoleId, $managerRoleId]);
```

### 4. Use in Your Application

```php
// Check available transitions for current user
$availableTransitions = $payment->transitions()->get();

// Perform transition with metadata
$payment->stateTransitionTo(
    state: PaymentStateEnum::Paid,
    description: 'Payment confirmed'
);

// View history
foreach ($payment->transitionHistory as $history) {
    echo "{$history->from_state->value} → {$history->to_state->value} by user {$history->creator->name}";
    // OR
    // echo "{$history->from_state->label()} → {$history->to_state->label()}";
}
```

## Usage

### Performing State Transitions

The package provides multiple ways to transition between states:

#### Simple State Change (Auto-tracked)

```php
// Direct state change - automatically tracked in history
$payment->state = PaymentStateEnum::Paid;
$payment->save();
```

#### Transition with Metadata (Recommended)

```php
// Use stateTransitionTo() for convenience - transitions and saves in one call
$payment->stateTransitionTo(
    state: PaymentStateEnum::Paid,
    description: 'Payment approved by manager',
    custom_properties: [
        'approved_by' => $manager->id,
        'approval_method' => 'manual',
        'notes' => 'All documents verified'
    ]
);
```

#### Set Metadata Before Saving

```php
// Set metadata first, then update the state
$payment->setTransitionMetadata(
    description: 'Payment rejected due to insufficient funds',
    custom_properties: ['reason_code' => 'INSUFFICIENT_FUNDS']
);
$payment->state = PaymentStateEnum::Rejected;
$payment->save();
```

### Get Available Transitions

```php
// For authenticated user
$payment->transitions()->get();

// For specific user
$payment->transitions($user)->get();

// Check if a specific transition exists
$payment->transitions()
    ->where('to_state', PaymentStateEnum::Paid)
    ->exists();
```

### Query History

```php
// Get all transitions for a model
$payment->transitionHistory;

// Latest transition
$payment->transitionHistory()->latest()->first();

// Filter by state
$payment->transitionHistory()
    ->where('to_state', PaymentStateEnum::Paid)
    ->get();

// Access enum values
$history = $payment->transitionHistory()->latest()->first();
$history->from_state; // Returns PaymentStateEnum::Pending
$history->to_state;   // Returns PaymentStateEnum::Paid

// Access metadata
$history->description;
$history->custom_properties; // Array of custom data
$history->created_by; // User ID who made the transition
```

## Advanced Usage

### Attaching Transitions to Users/Roles

To query available transitions from the user or role side, add the `HasAttachedTransitions` trait:

```php
use Jenishev\Laravel\ModelStateTransitions\Concerns\HasAttachedTransitions;

class User extends Authenticatable
{
    use HasAttachedTransitions;
    
    // ... existing code
}

class Role extends Model
{
    use HasAttachedTransitions;
    
    // ... existing code
}
```

This enables:
```php
// Get all transitions available to a user
$user->transitions;

// Attach a transition to a user
$user->transitions()->attach($transitionId);

// Check if a user has a specific transition
$user->transitions()->where('to_state', 'approved')->exists();
```

## Configuration

The package provides sensible defaults out of the box. You can customize the behavior by editing the published configuration file at `config/model-state-transitions.php`.

Key configuration options include:

- **`transitions_table`** - Table storing state transition definitions (default: `transitions`)
- **`transition_history_table`** - Audit trail table (default: `transition_history`)
- **`pivot_table`** - User/Role authorization pivot table (default: `model_has_transitions`)
- **`transitionable_state_column`** - Column name on your models (default: `state`)
- **`transition_model`** - Transition model class (customizable)
- **`transition_history_model`** - History model class (customizable)
- **`role_model`** - Your application's Role model (customizable)
- **`user_model`** - Your application's User model (customizable)

Refer to the config file for detailed documentation on each option.

## Testing

```bash
composer test
composer format
composer analyse
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
