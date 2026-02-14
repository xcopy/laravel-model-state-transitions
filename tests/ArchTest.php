<?php

use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelPackageTools\PackageServiceProvider;

// Prevent debugging functions from being committed
arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->each->not->toBeUsed();

// Ensure models extend Eloquent Model
arch('models should extend Eloquent Model')
    ->expect('Jenishev\Laravel\ModelStateTransitions\Models')
    ->toExtend(Model::class);

// Ensure traits are in Concerns namespace
arch('traits should be in Concerns namespace')
    ->expect('Jenishev\Laravel\ModelStateTransitions\Concerns')
    ->toBeTraits();

// Ensure contracts are interfaces
arch('contracts should only define interfaces')
    ->expect('Jenishev\Laravel\ModelStateTransitions\Contracts')
    ->toBeInterfaces();

// Service provider must extend the correct base class
arch('service provider extends PackageServiceProvider')
    ->expect('Jenishev\Laravel\ModelStateTransitions\ModelStateTransitionsServiceProvider')
    ->toExtend(PackageServiceProvider::class);

// Prevent dangerous functions
arch('no exit or die statements')
    ->expect(['exit', 'die'])
    ->each->not->toBeUsed();

// Models should not use facades (prefer dependency injection)
arch('models should not use facades')
    ->expect('Jenishev\Laravel\ModelStateTransitions\Models')
    ->not->toUse('Illuminate\Support\Facades');
