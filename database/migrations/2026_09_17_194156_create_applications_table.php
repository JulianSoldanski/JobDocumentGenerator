<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eine Bewerbung je Stelle. `company_key`/`position_key` sind die normalisierte
 * Form von Unternehmen und Position: über sie dedupliziert das Generieren,
 * damit mehrfaches Erzeugen keine Karteileichen produziert.
 *
 * `current_stage` ist abgeleitet (das letzte Stufen-Ereignis) und wird
 * ausschließlich vom StageRecorder fortgeschrieben.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('company')->default('');
            $table->string('position')->default('');
            $table->string('company_key')->default('');
            $table->string('position_key')->default('');
            $table->string('job_url')->nullable();
            $table->longText('job_posting')->nullable();
            $table->date('applied_on')->nullable();
            $table->text('feedback')->nullable();
            $table->unsignedInteger('research_seconds')->default(0);
            $table->string('current_stage')->default('created');
            $table->string('legacy_id')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'company_key', 'position_key']);
            $table->unique(['user_id', 'legacy_id']);
            $table->index(['user_id', 'current_stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
