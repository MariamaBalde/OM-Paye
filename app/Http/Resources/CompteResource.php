<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource pour formater les données des comptes selon US 2.0
 */
class CompteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numeroCompte' => $this->numero_compte,
            'titulaire' => $this->user ? $this->user->nom . ' ' . $this->user->prenom : null,
            'type' => $this->type,
            'solde' => (float) $this->solde,
            'devise' => 'FCFA',
            'dateCreation' => $this->date_ouverture?->toISOString(),
            'statut' => $this->statut,
            'motifBlocage' => $this->statut === 'bloque' ? 'Inactivité de 30+ jours' : null,
            'metadata' => [
                'derniereModification' => $this->updated_at?->toISOString(),
                'version' => 1
            ]
        ];
    }
}