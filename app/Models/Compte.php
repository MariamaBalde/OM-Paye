<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Compte extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'numero_compte',
        'solde',
        'qr_code',
        'code_secret',
        'plafond_journalier',
        'statut',
        'date_ouverture',
    ];

    protected $casts = [
        'solde' => 'decimal:2',
        'plafond_journalier' => 'decimal:2',
        'date_ouverture' => 'datetime',
    ];

    // Générer automatiquement le numéro de compte
    protected static function booted()
    {
        // Scope global pour filtrer automatiquement les comptes actifs
        static::addGlobalScope('actif', function (Builder $builder) {
            $builder->where('statut', 'actif');
        });

        static::creating(function ($compte) {
            if (empty($compte->numero_compte)) {
                $compte->numero_compte = 'OMCPT' . str_pad($compte->user_id . rand(100, 999), 10, '0', STR_PAD_LEFT);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function marchand()
    {
        return $this->hasOne(Marchand::class);
    }

    public function transactionsEmises()
    {
        return $this->hasMany(Transaction::class, 'compte_emetteur_id');
    }

    public function transactionsRecues()
    {
        return $this->hasMany(Transaction::class, 'compte_destinataire_id');
    }

    public function transactions()
    {
        return Transaction::where('compte_emetteur_id', $this->id)
            ->orWhere('compte_destinataire_id', $this->id);
    }

    // Scope pour comptes actifs
    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    // Scope global pour comptes non archivés (actifs)
    public function scopeNonArchive($query)
    {
        return $query->whereNotIn('statut', ['ferme', 'supprime']);
    }

    // Scope local scopeNumero: permet de récupérer un compte par son numéro
    public function scopeNumero($query, $numero)
    {
        return $query->where('numero_compte', $numero);
    }

    // Scope local scopeClient: permet de récupérer les comptes d'un client basé sur le téléphone
    public function scopeClient($query, $telephone)
    {
        return $query->whereHas('user', function ($q) use ($telephone) {
            $q->where('telephone', $telephone);
        });
    }
}
