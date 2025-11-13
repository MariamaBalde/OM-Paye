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
                'compte' => $this->whenLoaded('compte', function () {
                    return [
                        'id' => $this->compte->id,
                        'numeroCompte' => $this->compte->numero_compte,
                        'solde' => (float) $this->compte->solde,
                        'statut' => $this->compte->statut
                    ];
                }),
                'roles' => $this->whenLoaded('roles', function () {
                    return $this->roles->pluck('name');
                })
            ])
        ];
    }
}
