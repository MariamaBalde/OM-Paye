<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'compte_id',
    ];

    protected $casts = [
        //
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
