<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'role',
        'telephone',
        'statut',
        'langue',
        'theme_sombre',
        'scanner_actif',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'theme_sombre' => 'boolean',
        'scanner_actif' => 'boolean',
    ];

    public function comptes()
    {
        return $this->hasMany(Compte::class);
    }


    public function verificationCodes()
    {
        return $this->hasMany(VerificationCode::class);
    }

    // Compte principal (pour compatibilité)
    public function comptePrincipal()
    {
        return $this->hasOne(Compte::class)->where('type', 'principal');
    }

    // Solde total de tous les comptes actifs
    public function getSoldeTotalAttribute()
    {
        return $this->comptes()->actif()->sum('solde');
    }
}
