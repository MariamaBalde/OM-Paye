<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'compte_id',
        'type_client',
        'date_naissance',
        'adresse',
        'ville',
        'pays',
        'piece_identite_type',
        'piece_identite_numero',
        'contacts_favoris',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'contacts_favoris' => 'array',
    ];

    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }

    public function user()
    {
        return $this->hasOneThrough(User::class, Compte::class, 'id', 'id', 'compte_id', 'user_id');
    }
}
