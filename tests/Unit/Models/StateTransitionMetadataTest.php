<?php

namespace Jenishev\Laravel\ModelStateTransitions\Tests\Unit\Models;

use Jenishev\Laravel\ModelStateTransitions\Models\TransitionHistory;
use Workbench\App\Enums\PaymentStateEnum;
use Workbench\App\Models\Payment;

beforeEach(function () {
    $this->payment = Payment::create([
        'state' => PaymentStateEnum::Pending,
        'amount' => 100.00,
    ]);
});

// stateTransitionTo() method tests
it('can transition with stateTransitionTo method', function () {
    $result = $this->payment->stateTransitionTo(
        state: PaymentStateEnum::Approved
    );

    expect($result)->toBeTrue()
        ->and($this->payment->fresh()->state)->toBe(PaymentStateEnum::Approved);
})->group('transitions', 'metadata');

it('can transition with description using stateTransitionTo', function () {
    $this->payment->stateTransitionTo(
        state: PaymentStateEnum::Approved,
        description: 'Approved by manager'
    );

    $history = $this->payment->transitionHistory()->latest()->first();

    expect($history->description)->toBe('Approved by manager')
        ->and($history->from_state)->toBe(PaymentStateEnum::Pending)
        ->and($history->to_state)->toBe(PaymentStateEnum::Approved);
})->group('transitions', 'metadata');

it('can transition with custom_properties using stateTransitionTo', function () {
    $this->payment->stateTransitionTo(
        state: PaymentStateEnum::Approved,
        custom_properties: ['approved_by' => 123, 'notes' => 'All good']
    );

    $history = $this->payment->transitionHistory()->latest()->first();

    expect($history->custom_properties)->toBe(['approved_by' => 123, 'notes' => 'All good'])
        ->and($history->to_state)->toBe(PaymentStateEnum::Approved);
})->group('transitions', 'metadata');

it('can transition with description and custom_properties using stateTransitionTo', function () {
    $this->payment->stateTransitionTo(
        state: PaymentStateEnum::Approved,
        description: 'Payment approved after verification',
        custom_properties: [
            'approved_by' => 456,
            'verification_method' => 'manual',
            'notes' => 'All documents verified',
        ]
    );

    $history = $this->payment->transitionHistory()->latest()->first();

    expect($history->description)->toBe('Payment approved after verification')
        ->and($history->custom_properties['approved_by'])->toBe(456)
        ->and($history->custom_properties['verification_method'])->toBe('manual')
        ->and($history->custom_properties['notes'])->toBe('All documents verified')
        ->and($history->from_state)->toBe(PaymentStateEnum::Pending)
        ->and($history->to_state)->toBe(PaymentStateEnum::Approved);
})->group('transitions', 'metadata');

// setTransitionMetadata() method tests
it('can set transition metadata before saving', function () {
    $this->payment->setTransitionMetadata(
        description: 'Rejected due to insufficient funds'
    );
    $this->payment->state = PaymentStateEnum::Rejected;
    $this->payment->save();

    $history = $this->payment->transitionHistory()->latest()->first();

    expect($history->description)->toBe('Rejected due to insufficient funds')
        ->and($history->to_state)->toBe(PaymentStateEnum::Rejected);
})->group('transitions', 'metadata');

it('can set custom_properties with setTransitionMetadata', function () {
    $this->payment->setTransitionMetadata(
        custom_properties: [
            'reason_code' => 'INSUFFICIENT_FUNDS',
            'amount' => 1000.00,
        ]
    );
    $this->payment->state = PaymentStateEnum::Rejected;
    $this->payment->save();

    $history = $this->payment->transitionHistory()->latest()->first();

    expect($history->custom_properties['reason_code'])->toBe('INSUFFICIENT_FUNDS')
        ->and($history->custom_properties['amount'])->toEqual(1000);
})->group('transitions', 'metadata');

it('can set both description and custom_properties with setTransitionMetadata', function () {
    $this->payment->setTransitionMetadata(
        description: 'Rejected due to policy violation',
        custom_properties: [
            'policy_id' => 42,
            'notes' => 'Customer flagged for review',
        ]
    );
    $this->payment->state = PaymentStateEnum::Rejected;
    $this->payment->save();

    $history = $this->payment->transitionHistory()->latest()->first();

    expect($history->description)->toBe('Rejected due to policy violation')
        ->and($history->custom_properties['policy_id'])->toBe(42)
        ->and($history->custom_properties['notes'])->toBe('Customer flagged for review');
})->group('transitions', 'metadata');

it('setTransitionMetadata returns model instance for chaining', function () {
    $result = $this->payment->setTransitionMetadata(
        description: 'Chained transition'
    );

    expect($result)->toBe($this->payment);
})->group('transitions', 'metadata');

it('can chain setTransitionMetadata with update', function () {
    $this->payment
        ->setTransitionMetadata(
            description: 'Completed after successful payment',
            custom_properties: ['gateway' => 'stripe']
        )
        ->update(['state' => PaymentStateEnum::Completed]);

    $history = $this->payment->transitionHistory()->latest()->first();

    expect($history->description)->toBe('Completed after successful payment')
        ->and($history->custom_properties['gateway'])->toBe('stripe')
        ->and($history->to_state)->toBe(PaymentStateEnum::Completed);
})->group('transitions', 'metadata');

// Metadata cleanup tests
it('clears metadata after recording transition', function () {
    $this->payment->setTransitionMetadata(
        description: 'First transition',
        custom_properties: ['test' => 'value']
    );
    $this->payment->state = PaymentStateEnum::Approved;
    $this->payment->save();

    // Second transition without metadata
    $this->payment->state = PaymentStateEnum::Completed;
    $this->payment->save();

    $histories = $this->payment->transitionHistory()->orderBy('created_at')->get();

    expect($histories[0]->description)->toBe('First transition')
        ->and($histories[0]->custom_properties['test'])->toBe('value')
        ->and($histories[1]->description)->toBeNull()
        ->and($histories[1]->custom_properties)->toBeNull();
})->group('transitions', 'metadata');

// Backward compatibility tests
it('still works with traditional state update without metadata', function () {
    $this->payment->state = PaymentStateEnum::Approved;
    $this->payment->save();

    $history = $this->payment->transitionHistory()->latest()->first();

    expect($history->from_state)->toBe(PaymentStateEnum::Pending)
        ->and($history->to_state)->toBe(PaymentStateEnum::Approved)
        ->and($history->description)->toBeNull()
        ->and($history->custom_properties)->toBeNull();
})->group('transitions', 'metadata');

// Edge cases
it('handles empty description', function () {
    $this->payment->stateTransitionTo(
        state: PaymentStateEnum::Approved,
        description: ''
    );

    $history = $this->payment->transitionHistory()->latest()->first();

    expect($history->description)->toBeNull();
})->group('transitions', 'metadata');

it('handles empty custom_properties array', function () {
    $this->payment->stateTransitionTo(
        state: PaymentStateEnum::Approved,
        custom_properties: []
    );

    $history = $this->payment->transitionHistory()->latest()->first();

    expect($history->custom_properties)->toBeNull();
})->group('transitions', 'metadata');

it('handles null description explicitly', function () {
    $this->payment->stateTransitionTo(
        state: PaymentStateEnum::Approved,
        description: null
    );

    $history = $this->payment->transitionHistory()->latest()->first();

    expect($history->description)->toBeNull();
})->group('transitions', 'metadata');

it('handles null custom_properties explicitly', function () {
    $this->payment->stateTransitionTo(
        state: PaymentStateEnum::Approved,
        custom_properties: null
    );

    $history = $this->payment->transitionHistory()->latest()->first();

    expect($history->custom_properties)->toBeNull();
})->group('transitions', 'metadata');

// Complex custom_properties tests
it('stores complex nested custom_properties', function () {
    $complexProperties = [
        'user_id' => 123,
        'reason' => 'Customer requested',
        'metadata' => [
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
        ],
        'tags' => ['urgent', 'vip'],
    ];

    $this->payment->stateTransitionTo(
        state: PaymentStateEnum::Approved,
        custom_properties: $complexProperties
    );

    $history = $this->payment->transitionHistory()->latest()->first();

    expect($history->custom_properties)->toBe($complexProperties)
        ->and($history->custom_properties['metadata']['ip_address'])->toBe('192.168.1.1')
        ->and($history->custom_properties['tags'])->toBe(['urgent', 'vip']);
})->group('transitions', 'metadata');

// Multiple transitions tests
it('records multiple transitions with different metadata', function () {
    // First transition
    $this->payment->stateTransitionTo(
        state: PaymentStateEnum::Approved,
        description: 'First approval',
        custom_properties: ['step' => 1]
    );

    // Second transition
    $this->payment->stateTransitionTo(
        state: PaymentStateEnum::Completed,
        description: 'Completed',
        custom_properties: ['step' => 2]
    );

    $histories = $this->payment->transitionHistory()->orderBy('created_at')->get();

    expect($histories)->toHaveCount(2)
        ->and($histories[0]->description)->toBe('First approval')
        ->and($histories[0]->custom_properties['step'])->toBe(1)
        ->and($histories[1]->description)->toBe('Completed')
        ->and($histories[1]->custom_properties['step'])->toBe(2);
})->group('transitions', 'metadata');

it('can query history by custom_properties', function () {
    $this->payment->stateTransitionTo(
        state: PaymentStateEnum::Approved,
        custom_properties: ['priority' => 'high']
    );

    $payment2 = Payment::create([
        'state' => PaymentStateEnum::Pending,
        'amount' => 200.00,
    ]);

    $payment2->stateTransitionTo(
        state: PaymentStateEnum::Approved,
        custom_properties: ['priority' => 'low']
    );

    $highPriority = TransitionHistory::whereJsonContains('custom_properties->priority', 'high')->get();

    expect($highPriority)->toHaveCount(1)
        ->and($highPriority->first()->model_id)->toBe($this->payment->id);
})->group('transitions', 'metadata');

// Real-world scenario tests
it('handles payment approval workflow with metadata', function () {
    $this->payment->stateTransitionTo(
        state: PaymentStateEnum::Approved,
        description: 'Payment approved by manager',
        custom_properties: [
            'approved_by' => 1,
            'approved_at' => now()->toIso8601String(),
            'amount' => 100.00,
            'currency' => 'USD',
        ]
    );

    $history = $this->payment->transitionHistory()->latest()->first();

    expect($history->description)->toBe('Payment approved by manager')
        ->and($history->custom_properties['approved_by'])->toBe(1)
        ->and($history->custom_properties['amount'])->toEqual(100)
        ->and($history->custom_properties['currency'])->toBe('USD');
})->group('transitions', 'metadata');

it('handles payment rejection workflow with metadata', function () {
    $this->payment->setTransitionMetadata(
        description: 'Payment rejected due to fraud detection',
        custom_properties: [
            'rejected_by' => 'system',
            'fraud_score' => 95,
            'fraud_reasons' => ['suspicious_ip', 'velocity_check_failed'],
        ]
    );
    $this->payment->state = PaymentStateEnum::Rejected;
    $this->payment->save();

    $history = $this->payment->transitionHistory()->latest()->first();

    expect($history->description)->toBe('Payment rejected due to fraud detection')
        ->and($history->custom_properties['rejected_by'])->toBe('system')
        ->and($history->custom_properties['fraud_score'])->toBe(95)
        ->and($history->custom_properties['fraud_reasons'])->toContain('suspicious_ip');
})->group('transitions', 'metadata');
