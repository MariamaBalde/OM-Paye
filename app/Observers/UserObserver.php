<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Compte;
use App\Models\Client;

class UserObserver
{
    public function created(User $user): void
    {
        // Assigner automatiquement le rôle client aux nouveaux utilisateurs
        $user->assignRole('client');

        // Créer le compte automatiquement
        $compte = Compte::create([
            'user_id' => $user->id,
            'numero_compte' => 'OMCPT' . time() . $user->id,
            'solde' => 0.00,
            'qr_code' => 'QR_' . $user->telephone,
            'code_secret' => bcrypt('1234'),
            'plafond_journalier' => 500000.00,
            'statut' => 'actif',
            'date_ouverture' => now(),
        ]);

        // Créer le profil client automatiquement
        Client::create([
            'compte_id' => $compte->id,
            'type_client' => 'particulier',
            // SUPPRIMER numero_client (n'existe pas dans la migration)
            'contacts_favoris' => json_encode([]),
            'date_naissance' => null,
            'adresse' => null,
            'ville' => null,
            'pays' => 'Sénégal',
            'piece_identite_type' => null,
            'piece_identite_numero' => null,
        ]);
    }

    public function updated(User $user): void
    {
        // Logique pour mise à jour (si nécessaire)
    }

    public function deleted(User $user): void
    {
        // Désactiver le compte au lieu de supprimer
        $user->compte()->update(['statut' => 'ferme']);
    }
}


// namespace App\Observers;

// use App\Models\User;
// use App\Models\Compte;
// use App\Models\Client;

// class UserObserver
// {
//     /**
//      * Handle the User "created" event.
//      * Single Responsibility: Créer automatiquement les données liées à l'utilisateur
//      */
//     public function created(User $user): void
//     {
//         // Créer le compte principal automatiquement
//         $comptePrincipal = Compte::create([
//             'user_id' => $user->id,
//             'numero_compte' => 'OMPRI' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
//             'type' => 'principal',
//             'solde' => 0.00,
//             'qr_code' => 'QR_' . $user->telephone,
//             'code_secret' => bcrypt('1234'), // Code par défaut, à changer
//             'plafond_journalier' => 500000.00,
//             'statut' => 'actif',
//             'date_ouverture' => now(),
//         ]);

//         // Créer le profil client automatiquement
//         Client::create([
//             'user_id' => $user->id,
//             'numero_client' => 'OMCLI' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
//             'contacts_favoris' => json_encode([]), // Liste vide au départ
//             'preferences' => json_encode([
//                 'notifications' => true,
//                 'biometrie' => false,
//                 'alertes_solde' => true,
//             ]),
//             'statut' => 'actif',
//             'date_inscription' => now(),
//         ]);
//     }

//     /**
//      * Handle the User "updated" event.
//      */
//     public function updated(User $user): void
//     {
//         // Logique pour mise à jour (si nécessaire)
//     }

//     /**
//      * Handle the User "deleted" event.
//      */
//     public function deleted(User $user): void
//     {
//         // Désactiver les comptes au lieu de supprimer
//         $user->comptes()->update(['statut' => 'ferme']);
//         $user->client()->update(['statut' => 'inactif']);
//     }
// }
