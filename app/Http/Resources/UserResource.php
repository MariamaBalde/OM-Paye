<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'telephone' => $this->telephone,
            'solde_fcfa' => $this->solde_fcfa,
            'qr_code' => $this->qr_code,
            'langue' => $this->langue,
            'theme_sombre' => $this->theme_sombre,
            'scanner_actif' => $this->scanner_actif,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
