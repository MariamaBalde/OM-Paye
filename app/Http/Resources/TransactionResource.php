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
            'montant' => (float) $this->montant,
            'frais' => (float) $this->frais,
            'montantTotal' => (float) $this->montant_total,
            'destinataireNumero' => $this->destinataire_numero,
            'destinataireNom' => $this->destinataire_nom,
            'statut' => $this->statut,
            'description' => $this->description,
            'dateTransaction' => $this->date_transaction?->toISOString(),
            'metadata' => [
                'derniereModification' => $this->updated_at?->toISOString(),
                'version' => 1,
                'codeVerifie' => (bool) $this->code_verifie
            ],
            'relations' => $this->when($request->has('include'), [
                'emetteur' => $this->whenLoaded('emetteur', function () {
                    return $this->emetteur ? [
                        'id' => $this->emetteur->id,
                        'numeroCompte' => $this->emetteur->numero_compte,
                        'titulaire' => $this->emetteur->user ? $this->emetteur->user->nom . ' ' . $this->emetteur->user->prenom : null
                    ] : null;
                }),
                'destinataire' => $this->whenLoaded('destinataire', function () {
                    return $this->destinataire ? [
                        'id' => $this->destinataire->id,
                        'numeroCompte' => $this->destinataire->numero_compte,
                        'titulaire' => $this->destinataire->user ? $this->destinataire->user->nom . ' ' . $this->destinataire->user->prenom : null
                    ] : null;
                }),
                'marchand' => $this->whenLoaded('marchand', function () {
                    return $this->marchand ? [
                        'id' => $this->marchand->id,
                        'nomCommercial' => $this->marchand->nom_commercial,
                        'codeMarchand' => $this->marchand->code_marchand
                    ] : null;
                })
            ])
        ];
    }
}
