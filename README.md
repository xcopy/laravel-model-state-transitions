# Laravel Model State Transitions

[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/xcopy/laravel-model-state-transitions/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/xcopy/laravel-model-state-transitions/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/xcopy/laravel-model-state-transitions/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/xcopy/laravel-model-state-transitions/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/xcopy/laravel-model-state-transitions.svg?style=flat-square)](https://packagist.org/packages/xcopy/laravel-model-state-transitions)

Manage state transitions for your Laravel models with role-based access control and automatic history tracking.

## Features

- Define valid state transitions for any Eloquent model
- Role-based access control (RBAC) for transitions
- Automatic transition history tracking with audit trail
- Type-safe enum support for states
- Polymorphic relationships for flexible model support
- Custom properties and descriptions for each transition
- Query available transitions based on user permissions

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

Run the installation command (publishes config and migrations):

```bash
php artisan model-state-transitions:install
php artisan migrate
```

Or publish manually:

```bash
php artisan vendor:publish --tag="model-state-transitions-migrations"
php artisan vendor:publish --tag="model-state-transitions-config"
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
use Jenishev\Laravel\ModelStateTransitions\Concerns\HasStateTransitions;
use Jenishev\Laravel\ModelStateTransitions\Contracts\HasStateTransitions as HasStateTransitionsContract;

class Payment extends Model implements HasStateTransitionsContract
{
    use HasStateTransitions;

    protected $fillable = [
        // ...
        'state'
    ];

    // Note: override if using custom enum naming
    public static function resolveStateEnum(): string
    {
        return \App\Enums\PaymentStateEnum::class;
    }
}
```

### 3. Define Transitions

```php
use Jenishev\Laravel\ModelStateTransitions\Models\Transition;

// Create a transition
$transition = Transition::create([
    'model_type' => \App\Models\Payment::class,
    'from_state' => 'pending',
    'to_state' => 'paid',
]);

// Attach to users/roles
$transition->users()->attach($user_id);
$transition->roles()->attach($role_id);
```

## Usage

### Get Available Transitions

```php
// For authenticated user
$payment->transitions()->get();

// For specific user
$payment->transitions($user)->get();

// Check if transition exists
$payment->transitions()
    ->where('to_state', 'paid')
    ->exists();
```

### Record Transition History

```php
$payment->transitionHistory()->create([
    'from_state' => PaymentStateEnum::Pending,
    'to_state' => PaymentStateEnum::Paid,
    'description' => '...',
    // 'custom_properties' => [...],
]);
```

### Query History

```php
// Get all transitions for a model
$payment->transitionHistory;

// Latest transition
$payment->transitionHistory()->latest()->first();

// Filter by state
TransitionHistory::where('to_state', 'paid')->get();
```

## Configuration

The package provides sensible defaults out of the box. You can customize the behavior by editing the published configuration file at `config/model-state-transitions.php`.

Key configuration options include:
- Database table names for transitions, history, and pivot tables
- The column name used for storing state on your models
- Related model classes

Refer to the config file for detailed documentation on each option.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
