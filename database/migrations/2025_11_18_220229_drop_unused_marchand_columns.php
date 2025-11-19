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
        Schema::table('marchands', function (Blueprint $table) {
            $table->dropColumn([
                'qr_code_marchand',
                'secteur_activite',
                'adresse_boutique',
                'ville',
                'telephone_professionnel',
                'commission_rate'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marchands', function (Blueprint $table) {
            $table->string('qr_code_marchand')->nullable();
            $table->string('secteur_activite')->nullable();
            $table->text('adresse_boutique')->nullable();
            $table->string('ville')->nullable();
            $table->string('telephone_professionnel')->nullable();
            $table->decimal('commission_rate', 5, 4)->default(0.02);
        });
    }
};
