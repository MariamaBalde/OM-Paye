<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'compte_emetteur_id',
        'compte_destinataire_id',
        'marchand_id',
        'type',
        'montant',
        'frais',
        'montant_total',
        'destinataire_numero',
        'destinataire_nom',
        'statut',
        'code_verification',
        'code_verifie',
        'reference',
        'description',
        'date_transaction',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'frais' => 'decimal:2',
        'montant_total' => 'decimal:2',
        'code_verifie' => 'boolean',
        'date_transaction' => 'datetime',
    ];

    // Générer automatiquement la référence et description
    protected static function booted()
    {
        static::creating(function ($transaction) {
            if (empty($transaction->reference)) {
                $transaction->reference = 'TXN' . date('YmdHis') . rand(100, 999);
            }
            $transaction->montant_total = $transaction->montant + $transaction->frais;
            $transaction->description = $transaction->generateDescription();
        });
    }

    private function generateDescription()
    {
        switch ($this->type) {
            case 'transfert':
                return "Transfert de {$this->montant}FCFA vers {$this->destinataire_nom}";

            case 'paiement':
                $marchand = $this->marchand;
                $nomMarchand = $marchand ? $marchand->nom_commercial : 'Marchand inconnu';
                return "Paiement de {$this->montant}FCFA chez {$nomMarchand}";

            case 'depot':
                return "Dépôt de {$this->montant}FCFA sur votre compte";

            case 'retrait':
                return "Retrait de {$this->montant}FCFA";

            case 'achat_credit':
                return "Achat de crédit de {$this->montant}FCFA";

            default:
                return "Transaction de {$this->montant}FCFA";
        }
    }

    public function emetteur()
    {
        return $this->belongsTo(Compte::class, 'compte_emetteur_id');
    }

    public function destinataire()
    {
        return $this->belongsTo(Compte::class, 'compte_destinataire_id');
    }

    public function marchand()
    {
        return $this->belongsTo(Marchand::class);
    }

    public function verificationCode()
    {
        return $this->hasOne(VerificationCode::class);
    }

    // Scope pour transactions validées (pour l'historique)
    public function scopeValidee($query)
    {
        return $query->where('statut', 'validee');
    }

    // Scope pour transactions récentes
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
