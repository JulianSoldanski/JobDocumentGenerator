<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Beispiel-Anschreiben und die daraus destillierten, vom Nutzer korrigierten
 * Stilregeln. Beim Generieren geht nur die Regelliste in den Prompt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('writing_styles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->longText('example')->nullable();
            $table->json('rules');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('writing_styles');
    }
};
