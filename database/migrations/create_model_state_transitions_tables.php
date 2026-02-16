<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $config = config('model-state-transitions');

        Schema::create($config['transitions_table'], function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->string('from_state');
            $table->string('to_state');
            $table->timestamps();

            $table->unique(['model_type', 'from_state', 'to_state']);
        });

        Schema::create($config['transition_history_table'], function (Blueprint $table) use ($config) {
            $table->id();
            $table->morphs('model');
            $table->string('from_state');
            $table->string('to_state');
            $table->text('description')->nullable();
            $table->json('custom_properties')->nullable();
            $table->timestamps();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained((new $config['user_model'])->getTable());
        });

        Schema::create($config['pivot_table'], function (Blueprint $table) use ($config) {
            $table->foreignId('transition_id')
                ->constrained($config['transitions_table'])
                ->cascadeOnDelete();
            $table->morphs('model');
            $table->timestamps();

            $table->primary(['transition_id', 'model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        $config = config('model-state-transitions');

        Schema::drop($config['pivot_table']);
        Schema::drop($config['transition_history_table']);
        Schema::drop($config['transitions_table']);
    }
};
