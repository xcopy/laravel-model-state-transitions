<?php

namespace Jenishev\Laravel\ModelStateTransitions\Tests\Feature;

use Jenishev\Laravel\ModelStateTransitions\Models\TransitionHistory;
use Workbench\App\Enums\PaymentStateEnum;
use Workbench\App\Models\Payment;

beforeEach(function () {
    $this->payment = Payment::create([
        'state' => PaymentStateEnum::Pending,
        'amount' => 100.00,
    ]);
});

it('can create transition history record', function () {
    $history = TransitionHistory::create([
        'model_type' => Payment::class,
        'model_id' => $this->payment->id,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Approved,
        'description' => 'Approved by manager',
        'custom_properties' => ['approved_by' => 1],
    ]);

    expect($history)->toBeInstanceOf(TransitionHistory::class);
    expect($history->model_type)->toBe(Payment::class);
    expect($history->model_id)->toBe($this->payment->id);
    expect($history->from_state)->toBe(PaymentStateEnum::Pending);
    expect($history->to_state)->toBe(PaymentStateEnum::Approved);
    expect($history->description)->toBe('Approved by manager');
    expect($history->custom_properties)->toBe(['approved_by' => 1]);
})->group('history');

it('can cast custom_properties as array', function () {
    $history = TransitionHistory::create([
        'model_type' => Payment::class,
        'model_id' => $this->payment->id,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Approved,
        'custom_properties' => ['key' => 'value', 'user_id' => 42],
    ]);

    $retrieved = TransitionHistory::find($history->id);

    expect($retrieved->custom_properties)->toBeArray();
    expect($retrieved->custom_properties['key'])->toBe('value');
    expect($retrieved->custom_properties['user_id'])->toBe(42);
})->group('history');

it('can store null description', function () {
    $history = TransitionHistory::create([
        'model_type' => Payment::class,
        'model_id' => $this->payment->id,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Rejected,
        'description' => null,
    ]);

    expect($history->description)->toBeNull();
})->group('history');

it('can store null custom_properties', function () {
    $history = TransitionHistory::create([
        'model_type' => Payment::class,
        'model_id' => $this->payment->id,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Rejected,
        'custom_properties' => null,
    ]);

    expect($history->custom_properties)->toBeNull();
})->group('history');

it('can query transition history by state', function () {
    TransitionHistory::create([
        'model_type' => Payment::class,
        'model_id' => $this->payment->id,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Approved,
    ]);

    $payment2 = Payment::create([
        'state' => PaymentStateEnum::Pending,
        'amount' => 200.00,
    ]);

    TransitionHistory::create([
        'model_type' => Payment::class,
        'model_id' => $payment2->id,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Rejected,
    ]);

    $approved = TransitionHistory::where('to_state', PaymentStateEnum::Approved)->get();

    expect($approved)->toHaveCount(1);
    expect($approved->first()->model_id)->toBe($this->payment->id);
})->group('history');

it('can query by from_state', function () {
    TransitionHistory::create([
        'model_type' => Payment::class,
        'model_id' => $this->payment->id,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Approved,
    ]);

    TransitionHistory::create([
        'model_type' => Payment::class,
        'model_id' => $this->payment->id,
        'from_state' => PaymentStateEnum::Approved,
        'to_state' => PaymentStateEnum::Completed,
    ]);

    $fromPending = TransitionHistory::where('from_state', PaymentStateEnum::Pending)->get();

    expect($fromPending)->toHaveCount(1);
    expect($fromPending->first()->to_state)->toBe(PaymentStateEnum::Approved);
})->group('history');

it('has morphTo relationship to model', function () {
    $history = TransitionHistory::create([
        'model_type' => Payment::class,
        'model_id' => $this->payment->id,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Approved,
    ]);

    $relatedModel = $history->model;

    expect($relatedModel)->toBeInstanceOf(Payment::class);
    expect($relatedModel->id)->toBe($this->payment->id);
    expect($relatedModel->amount)->toBe('100.00');
})->group('history');

it('can query history for specific model instance', function () {
    $payment2 = Payment::create([
        'state' => PaymentStateEnum::Pending,
        'amount' => 200.00,
    ]);

    TransitionHistory::create([
        'model_type' => Payment::class,
        'model_id' => $this->payment->id,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Approved,
    ]);

    TransitionHistory::create([
        'model_type' => Payment::class,
        'model_id' => $payment2->id,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Rejected,
    ]);

    $payment1History = TransitionHistory::where('model_id', $this->payment->id)
        ->where('model_type', Payment::class)
        ->get();

    expect($payment1History)->toHaveCount(1);
    expect($payment1History->first()->to_state)->toBe(PaymentStateEnum::Approved);
})->group('history');

it('can order history by creation date', function () {
    $history1 = TransitionHistory::create([
        'model_type' => Payment::class,
        'model_id' => $this->payment->id,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Approved,
    ]);

    sleep(1);

    $history2 = TransitionHistory::create([
        'model_type' => Payment::class,
        'model_id' => $this->payment->id,
        'from_state' => PaymentStateEnum::Approved,
        'to_state' => PaymentStateEnum::Completed,
    ]);

    $latest = TransitionHistory::where('model_id', $this->payment->id)
        ->latest()
        ->first();

    expect($latest->id)->toBe($history2->id);
    expect($latest->to_state)->toBe(PaymentStateEnum::Completed);
})->group('history');

it('can update transition history record', function () {
    $history = TransitionHistory::create([
        'model_type' => Payment::class,
        'model_id' => $this->payment->id,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Approved,
        'description' => 'Initial description',
    ]);

    $history->update(['description' => 'Updated description']);

    expect($history->fresh()->description)->toBe('Updated description');
})->group('history');

it('can delete transition history record', function () {
    $history = TransitionHistory::create([
        'model_type' => Payment::class,
        'model_id' => $this->payment->id,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Approved,
    ]);

    $id = $history->id;
    $history->delete();

    expect(TransitionHistory::find($id))->toBeNull();
})->group('history');

it('stores timestamps correctly', function () {
    $history = TransitionHistory::create([
        'model_type' => Payment::class,
        'model_id' => $this->payment->id,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Approved,
    ]);

    expect($history->created_at)->not->toBeNull();
    expect($history->updated_at)->not->toBeNull();
    expect($history->created_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
})->group('history');

it('can store complex custom_properties', function () {
    $complexProperties = [
        'user_id' => 123,
        'reason' => 'Customer requested',
        'metadata' => [
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
        ],
        'tags' => ['urgent', 'vip'],
    ];

    $history = TransitionHistory::create([
        'model_type' => Payment::class,
        'model_id' => $this->payment->id,
        'from_state' => PaymentStateEnum::Pending,
        'to_state' => PaymentStateEnum::Approved,
        'custom_properties' => $complexProperties,
    ]);

    $retrieved = $history->fresh();

    expect($retrieved->custom_properties)->toBe($complexProperties);
    expect($retrieved->custom_properties['metadata']['ip_address'])->toBe('192.168.1.1');
    expect($retrieved->custom_properties['tags'])->toBe(['urgent', 'vip']);
})->group('history');
