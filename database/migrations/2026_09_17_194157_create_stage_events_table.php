<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Der Status einer Bewerbung wird nicht überschrieben: jeder Wechsel ist ein
 * Ereignis mit Zeitstempel, das nur angehängt wird. Der aktuelle Status ist
 * der letzte Eintrag. Deshalb gibt es hier bewusst kein `updated_at` — die
 * Zeile wird nach dem Anlegen nie mehr angefasst.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('stage');
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['application_id', 'occurred_at']);
            $table->index('stage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_events');
    }
};
