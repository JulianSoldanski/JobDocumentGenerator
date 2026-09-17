<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kontaktdaten stehen im Kopf von Lebenslauf und Anschreiben und werden
 * getrennt vom übrigen Profil gehalten, weil sie nirgendwo sonst hingehören.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name')->default('');
            $table->string('street')->default('');
            $table->string('postal_code')->default('');
            $table->string('city')->default('');
            $table->string('phone')->default('');
            $table->string('email')->default('');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_details');
    }
};
