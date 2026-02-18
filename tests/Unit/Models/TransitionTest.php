<?php

namespace Jenishev\Laravel\ModelStateTransitions\Tests\Unit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use InvalidArgumentException;
use Jenishev\Laravel\ModelStateTransitions\Models\Transition;
use Workbench\App\Enums\PaymentStateEnum;
use Workbench\App\Models\Payment;

beforeEach(function () {
    // Now we use REAL classes from the workbench
    $this->modelClass = Payment::class;
    $this->enumClass = PaymentStateEnum::class;
});

it('can create a transition with real model class', function () {
    $transition = Transition::create([
        'model_type' => $this->modelClass,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Approved,
    ]);

    expect($transition)->toBeInstanceOf(Transition::class);
    expect($transition->model_type)->toBe($this->modelClass);
    expect($transition->from_state)->toBe(PaymentStateEnum::Pending);
    expect($transition->to_state)->toBe(PaymentStateEnum::Approved);
})->group('transitions');

it('validates transition creation with real model', function () {
    $transition = Transition::create([
        'model_type' => $this->modelClass,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Approved,
    ]);

    expect($transition)->toBeInstanceOf(Transition::class);
    expect($transition->model_type)->toBe($this->modelClass);
})->group('transitions');

it('ensures unique transitions per model and state', function () {
    Transition::create([
        'model_type' => $this->modelClass,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Approved,
    ]);

    expect(fn () => Transition::create([
        'model_type' => $this->modelClass,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Approved,
    ]))->toThrow(QueryException::class);
})->group('transitions');

it('allows different to_state from same from_state', function () {
    Transition::create([
        'model_type' => $this->modelClass,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Approved,
    ]);

    $transition = Transition::create([
        'model_type' => $this->modelClass,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Rejected,
    ]);

    expect($transition)->toBeInstanceOf(Transition::class);
})->group('transitions');

it('can use real Payment model with transitions trait', function () {
    $payment = Payment::create([
        'state' => PaymentStateEnum::Pending,
        'amount' => 100.00,
    ]);

    expect($payment)->toBeInstanceOf(Payment::class);
    expect($payment->state)->toBe(PaymentStateEnum::Pending);
    expect(Payment::resolveStateEnum())->toBe(PaymentStateEnum::class);
})->group('transitions');

// AsModelClass cast validation tests
it('validates that model_type implements HasStateTransitions interface', function () {
    $transition = new Transition;
    $transition->model_type = Payment::class;
    $transition->from_state = PaymentStateEnum::Pending;
    $transition->to_state = PaymentStateEnum::Approved;
    $transition->save();

    expect($transition->model_type)->toBe(Payment::class);
})->group('transitions', 'validation');

it('throws exception for model_type that does not implement HasStateTransitions', function () {
    $transition = new Transition;
    $transition->model_type = Model::class;
    $transition->from_state = PaymentStateEnum::Pending;
    $transition->to_state = PaymentStateEnum::Approved;
    $transition->save();
})->throws(InvalidArgumentException::class)->group('transitions', 'validation');

it('throws exception for non-existent class in model_type', function () {
    $transition = new Transition;
    $transition->model_type = 'NonExistentClass';
    $transition->from_state = PaymentStateEnum::Pending;
    $transition->to_state = PaymentStateEnum::Approved;
    $transition->save();
})->throws(InvalidArgumentException::class)->group('transitions', 'validation');

it('throws exception for non-string model_type', function () {
    $transition = new Transition;
    $transition->model_type = 123;
})->throws(InvalidArgumentException::class)->group('transitions', 'validation');

it('throws exception for array model_type', function () {
    $transition = new Transition;
    $transition->model_type = [Payment::class];
})->throws(InvalidArgumentException::class)->group('transitions', 'validation');

it('handles null model_type', function () {
    $transition = new Transition;
    $transition->model_type = null;
    $transition->from_state = PaymentStateEnum::Pending;
    $transition->to_state = PaymentStateEnum::Approved;

    expect($transition->model_type)->toBeNull();
})->group('transitions', 'validation');

it('validates model_type on retrieval from database', function () {
    // Create a transition with valid model_type
    $transition = Transition::create([
        'model_type' => Payment::class,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Approved,
    ]);

    // Retrieve it
    $retrieved = Transition::find($transition->id);

    expect($retrieved->model_type)->toBe(Payment::class);
})->group('transitions', 'validation');

it('preserves fully qualified class names', function () {
    $transition = Transition::create([
        'model_type' => Payment::class,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Approved,
    ]);

    expect($transition->model_type)
        ->toBe(Payment::class)
        ->and($transition->model_type)->not->toStartWith('\\\\');
})->group('transitions', 'validation');

it('stores model_type as string in database', function () {
    $transition = Transition::create([
        'model_type' => Payment::class,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Approved,
    ]);

    expect($transition->getAttributes()['model_type'])
        ->toBe(Payment::class)
        ->and($transition->getAttributes()['model_type'])->toBeString();
})->group('transitions', 'validation');
