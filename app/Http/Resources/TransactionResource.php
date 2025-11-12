<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'reference' => $this->reference,
            'type' => $this->type,
            'montant' => $this->montant,
            'frais' => $this->frais,
            'montant_total' => $this->montant_total,
            'destinataire_numero' => $this->destinataire_numero,
            'destinataire_nom' => $this->destinataire_nom,
            'statut' => $this->statut,
            'code_verification' => $this->code_verification,
            'code_verifie' => $this->code_verifie,
            'description' => $this->description,
            'date_transaction' => $this->date_transaction,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'emetteur' => $this->whenLoaded('emetteur'),
            'destinataire' => $this->whenLoaded('destinataire'),
            'marchand' => $this->whenLoaded('marchand'),
        ];
    }
}
