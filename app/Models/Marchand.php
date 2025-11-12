<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marchand extends Model
{
    use HasFactory;

    protected $fillable = [
        'compte_id',
        'nom_commercial',
        'code_marchand',
        'qr_code_marchand',
        'secteur_activite',
        'adresse_boutique',
        'ville',
        'telephone_professionnel',
        'statut',
        'commission_rate',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:4',
    ];

    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }

    public function user()
    {
        return $this->hasOneThrough(User::class, Compte::class, 'id', 'id', 'compte_id', 'user_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Scope pour marchands actifs
    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }
}
