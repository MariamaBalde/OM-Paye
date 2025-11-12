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
        Schema::create('marchands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compte_id')->constrained()->onDelete('cascade');
            $table->string('nom_commercial');
            $table->string('code_marchand')->unique();
            $table->string('qr_code_marchand')->nullable();
            $table->string('secteur_activite')->nullable();
            $table->text('adresse_boutique')->nullable();
            $table->string('ville')->nullable();
            $table->string('telephone_professionnel')->nullable();
            $table->enum('statut', ['actif', 'inactif', 'suspendu'])->default('actif');
            $table->decimal('commission_rate', 5, 4)->default(0.02); // 2%
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marchands');
    }
};
