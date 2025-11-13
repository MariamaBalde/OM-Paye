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
            'nomComplet' => $this->nom . ' ' . $this->prenom,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'statut' => $this->statut,
            'langue' => $this->langue,
            'themeSombre' => (bool) $this->theme_sombre,
            'scannerActif' => (bool) $this->scanner_actif,
            'soldeTotal' => (float) $this->getSoldeTotalAttribute(),
            'metadata' => [
                'derniereModification' => $this->updated_at?->toISOString(),
                'dateCreation' => $this->created_at?->toISOString(),
                'version' => 1
            ],
            'relations' => $this->when($request->has('include'), [
                'comptes' => $this->whenLoaded('comptes', function () {
                    return $this->comptes->map(function ($compte) {
                        return [
                            'id' => $compte->id,
                            'numeroCompte' => $compte->numero_compte,
                            'type' => $compte->type,
                            'solde' => (float) $compte->solde,
                            'statut' => $compte->statut
                        ];
                    });
                }),
                'roles' => $this->whenLoaded('roles', function () {
                    return $this->roles->pluck('name');
                })
            ])
        ];
    }
}
