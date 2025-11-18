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
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'type_client',
                'date_naissance',
                'adresse',
                'ville',
                'pays',
                'piece_identite_type',
                'piece_identite_numero',
                'contacts_favoris'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->enum('type_client', ['particulier', 'professionnel'])->default('particulier');
            $table->date('date_naissance')->nullable();
            $table->text('adresse')->nullable();
            $table->string('ville')->nullable();
            $table->string('pays')->default('Sénégal');
            $table->enum('piece_identite_type', ['CNI', 'passeport', 'permis'])->nullable();
            $table->string('piece_identite_numero')->nullable();
            $table->json('contacts_favoris')->nullable();
        });
    }
};
