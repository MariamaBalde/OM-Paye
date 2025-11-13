<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comptes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->unique();
            $table->string('numero_compte')->unique();
            $table->decimal('solde', 15, 2)->default(0);
            $table->string('qr_code')->nullable();
            $table->string('code_secret')->nullable(); // 4 chiffres hashés
            $table->decimal('plafond_journalier', 15, 2)->default(500000);
            $table->enum('statut', ['actif', 'bloque', 'ferme'])->default('actif');
            $table->timestamp('date_ouverture')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comptes');
    }
};
