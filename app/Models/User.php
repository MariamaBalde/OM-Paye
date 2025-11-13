<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use App\Traits\HasRolesAndPermissions;

/**
 * @method \Laravel\Passport\PersonalAccessTokenResult createToken(string $name, array $scopes = [])
 * @method \Laravel\Passport\Token|null token()
 */

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRolesAndPermissions;

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

    public function compte()
    {
        return $this->hasOne(Compte::class);
    }

    public function comptePrincipal()
    {
        return $this->compte();
    }

    public function verificationCodes()
    {
        return $this->hasMany(VerificationCode::class);
    }

    // Solde total du compte unique
    public function getSoldeTotalAttribute()
    {
        // Cache the result for 5 minutes to avoid repeated queries
        return cache()->remember(
            "user_{$this->id}_solde_total",
            300, // 5 minutes
            fn() => $this->compte?->solde ?? 0
        );
    }
}
