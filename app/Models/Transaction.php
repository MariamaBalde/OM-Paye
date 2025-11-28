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
        'date_transaction',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'frais' => 'decimal:2',
        'montant_total' => 'decimal:2',
        'code_verifie' => 'boolean',
        'date_transaction' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($transaction) {
            if (empty($transaction->reference)) {
                $transaction->reference = 'TXN' . date('YmdHis') . rand(100, 999);
            }
            $transaction->montant_total = $transaction->montant + $transaction->frais;
        });
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

    public function scopeValidee($query)
    {
        return $query->where('statut', 'validee');
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopePourUtilisateur($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->whereHas('emetteur', function ($subQ) use ($userId) {
                $subQ->where('user_id', $userId);
            })->orWhereHas('destinataire', function ($subQ) use ($userId) {
                $subQ->where('user_id', $userId);
            });
        });
    }

    public function scopeMontantEntre($query, $min, $max)
    {
        return $query->whereBetween('montant', [$min, $max]);
    }

    public function scopePeriode($query, $periode)
    {
        switch ($periode) {
            case 'today':
                return $query->whereDate('created_at', today());
            case 'week':
                return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            case 'month':
                return $query->whereMonth('created_at', now()->month)
                           ->whereYear('created_at', now()->year);
            case 'year':
                return $query->whereYear('created_at', now()->year);
            default:
                return $query;
        }
    }

    public function scopeRechercher($query, $terme)
    {
        return $query->where(function ($q) use ($terme) {
            $q->where('reference', 'like', "%{$terme}%")
              ->orWhere('destinataire_nom', 'like', "%{$terme}%")
              ->orWhere('destinataire_numero', 'like', "%{$terme}%");
        });
    }

    public function scopeTrierPar($query, $colonne, $direction = 'desc')
    {
        $colonnesAutorisees = ['created_at', 'montant', 'montant_total', 'date_transaction'];

        if (in_array($colonne, $colonnesAutorisees)) {
            return $query->orderBy($colonne, $direction);
        }

        return $query->orderBy('created_at', 'desc');
    }
}
