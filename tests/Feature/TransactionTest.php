<?php

namespace Jenishev\Laravel\ModelStateTransitions\Tests\Feature;

use Illuminate\Database\QueryException;
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
