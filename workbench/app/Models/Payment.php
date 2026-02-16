<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Jenishev\Laravel\ModelStateTransitions\Concerns\HasStateTransitions as HasStateTransitionsConcern;
use Jenishev\Laravel\ModelStateTransitions\Contracts\HasStateTransitions as HasStateTransitionsContract;
use Workbench\App\Enums\PaymentStateEnum;

class Payment extends Model implements HasStateTransitionsContract
{
    use HasStateTransitionsConcern;

    protected $fillable = ['state', 'amount'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public static function resolveStateEnum(): string
    {
        return PaymentStateEnum::class;
    }
}
