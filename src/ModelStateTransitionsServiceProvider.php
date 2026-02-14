<?php

namespace Jenishev\Laravel\ModelStateTransitions;

use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Service provider for the Model State Transitions package.
 *
 * Registers the package configuration, migrations, and install command
 * with the Laravel application using Spatie's Laravel Package Tools.
 */
class ModelStateTransitionsServiceProvider extends PackageServiceProvider
{
    /**
     * Configure the package.
     *
     * Registers the package name, configuration file, database migrations,
     * and the installation command for easy setup via Artisan.
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-model-state-transitions')
            ->hasConfigFile()
            ->hasMigration('create_model_state_transitions_tables')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command->publishConfigFile();
                $command->publishMigrations();
            });
    }
}
