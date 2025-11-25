# Guide Débutant - Création d'une API de Paiement avec Laravel (Om-Paye)

Ce guide vous accompagne pas à pas dans la création d'une API de paiement similaire à Orange Money en utilisant Laravel. Le projet final sera une API RESTful permettant la gestion des comptes, transactions et authentification.

## Table des Matières
1. [Installation et Configuration](#installation-et-configuration)
2. [Planification des Entités et Relations](#planification-des-entités-et-relations)
3. [Création des Migrations](#création-des-migrations)
4. [Création des Modèles](#création-des-modèles)
5. [Configuration de l'Authentification](#configuration-de-lauthentification)
6. [Création des Contrôleurs](#création-des-contrôleurs)
7. [Création des Services](#création-des-services)
8. [Configuration des Routes](#configuration-des-routes)
9. [Tests et Validation](#tests-et-validation)
10. [Déploiement](#déploiement)

## 1. Installation et Configuration

### Prérequis
- PHP 8.1 ou supérieur
- Composer
- MySQL ou PostgreSQL
- Node.js et npm (pour les assets frontend si nécessaire)

### Installation de Laravel
```bash
composer create-project laravel/laravel om-paye
cd om-paye
```

### Configuration de l'environnement
```bash
cp .env.example .env
php artisan key:generate
```

Modifiez le fichier `.env` pour configurer la base de données :
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=om_paye
DB_USERNAME=votre_username
DB_PASSWORD=votre_password
```

### Installation des dépendances
```bash
composer require laravel/passport laravel/sanctum darkaonline/l5-swagger guzzlehttp/guzzle twilio/sdk
composer require --dev laravel/pint fakerphp/faker
```

### Configuration de Passport pour l'authentification OAuth
```bash
php artisan passport:install
php artisan migrate
```

## 2. Planification des Entités et Relations

Notre système de paiement aura les entités suivantes :

### Entités Principales
- **User** : Utilisateur de l'application
- **Compte** : Compte bancaire virtuel
- **Client** : Informations client (personnel ou professionnel)
- **Marchand** : Informations marchand pour recevoir des paiements
- **Transaction** : Toutes les opérations financières
- **Role** / **Permission** : Système de rôles et permissions

### Relations
```
User (1) ──── (1) Compte
                  │
                  ├── (1) Client
                  └── (1) Marchand

Transaction ──── (1) Compte (émetteur)
         │
         └── (1) Compte (destinataire)
         │
         └── (1) Marchand (optionnel)

Role ──── (*) Permission (many-to-many)
User ──── (*) Role (many-to-many)
```

### Types de Transactions
- `transfert` : Transfert entre comptes
- `paiement` : Paiement chez un marchand
- `depot` : Dépôt d'argent
- `retrait` : Retrait d'argent
- `achat_credit` : Achat de crédit téléphonique

## 3. Création des Migrations

Créons les migrations pour nos entités :

### Migration des Comptes
```bash
php artisan make:migration create_comptes_table
```

```php
// database/migrations/xxxx_xx_xx_create_comptes_table.php
public function up(): void
{
    Schema::create('comptes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade')->unique();
        $table->string('numero_compte')->unique();
        $table->decimal('solde', 15, 2)->default(0);
        $table->string('qr_code')->nullable();
        $table->string('code_secret')->nullable(); // 4 chiffres hashés
        $table->decimal('plafond_journalier', 15, 2)->default(500000);
        $table->enum('statut', ['actif', 'bloque', 'ferme'])->default('actif');
        $table->timestamp('date_ouverture')->useCurrent();
        $table->timestamps();
    });
}
```

### Migration des Marchands
```bash
php artisan make:migration create_marchands_table
```

```php
public function up(): void
{
    Schema::create('marchands', function (Blueprint $table) {
        $table->id();
        $table->foreignId('compte_id')->constrained()->onDelete('cascade');
        $table->string('nom_commercial');
        $table->string('code_marchand')->unique();
        $table->string('qr_code_marchand')->nullable();
        $table->string('secteur_activite')->nullable();
        $table->text('adresse_boutique')->nullable();
        $table->string('ville')->nullable();
        $table->string('telephone_professionnel')->nullable();
        $table->enum('statut', ['actif', 'inactif', 'suspendu'])->default('actif');
        $table->decimal('commission_rate', 5, 4)->default(0.02); // 2%
        $table->timestamps();
    });
}
```

### Migration des Transactions
```bash
php artisan make:migration create_transactions_table
```

```php
public function up(): void
{
    Schema::create('transactions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('compte_emetteur_id')->constrained('comptes')->onDelete('cascade');
        $table->foreignId('compte_destinataire_id')->nullable()->constrained('comptes')->onDelete('cascade');
        $table->foreignId('marchand_id')->nullable()->constrained()->onDelete('cascade');
        $table->enum('type', ['transfert', 'paiement', 'depot', 'retrait', 'achat_credit']);
        $table->decimal('montant', 15, 2);
        $table->decimal('frais', 10, 2)->default(0);
        $table->decimal('montant_total', 15, 2);
        $table->string('destinataire_numero')->nullable();
        $table->string('destinataire_nom')->nullable();
        $table->enum('statut', ['en_attente', 'validee', 'echouee', 'annulee'])->default('en_attente');
        $table->string('code_verification', 4)->nullable();
        $table->boolean('code_verifie')->default(false);
        $table->string('reference')->unique();
        $table->text('description')->nullable();
        $table->timestamp('date_transaction')->useCurrent();
        $table->timestamps();
    });
}
```

### Migration des Rôles et Permissions
```bash
php artisan make:migration create_roles_table
php artisan make:migration create_permissions_table
php artisan make:migration create_role_user_table
php artisan make:migration create_permission_role_table
```

Exécutez les migrations :
```bash
php artisan migrate
```

## 4. Création des Modèles

### Modèle User
```bash
php artisan make:model User
```

```php
<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use App\Traits\HasRolesAndPermissions;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRolesAndPermissions;

    protected $fillable = [
        'nom', 'prenom', 'email', 'password', 'telephone',
        'role', 'statut', 'langue', 'theme_sombre', 'scanner_actif'
    ];

    protected $hidden = ['password', 'remember_token'];

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

    public function getSoldeTotalAttribute()
    {
        return cache()->remember(
            "user_{$this->id}_solde_total",
            300,
            fn() => $this->compte?->solde ?? 0
        );
    }
}
```

### Modèle Compte
```bash
php artisan make:model Compte
```

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Compte extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'numero_compte', 'solde', 'qr_code',
        'code_secret', 'plafond_journalier', 'statut', 'date_ouverture'
    ];

    protected $casts = [
        'solde' => 'decimal:2',
        'plafond_journalier' => 'decimal:2',
        'date_ouverture' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope('actif', function (Builder $builder) {
            $builder->where('statut', 'actif');
        });

        static::creating(function ($compte) {
            if (empty($compte->numero_compte)) {
                $compte->numero_compte = 'OMCPT' . str_pad($compte->user_id . rand(100, 999), 10, '0', STR_PAD_LEFT);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function marchand()
    {
        return $this->hasOne(Marchand::class);
    }

    public function transactionsEmises()
    {
        return $this->hasMany(Transaction::class, 'compte_emetteur_id');
    }

    public function transactionsRecues()
    {
        return $this->hasMany(Transaction::class, 'compte_destinataire_id');
    }

    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeNumero($query, $numero)
    {
        return $query->where('numero_compte', $numero);
    }
}
```

### Modèle Transaction
```bash
php artisan make:model Transaction
```

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'compte_emetteur_id', 'compte_destinataire_id', 'marchand_id',
        'type', 'montant', 'frais', 'montant_total',
        'destinataire_numero', 'destinataire_nom', 'statut',
        'code_verification', 'code_verifie', 'reference', 'description', 'date_transaction'
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'frais' => 'decimal:2',
        'montant_total' => 'decimal:2',
        'code_verifie' => 'boolean',
        'date_transaction' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($transaction) {
            if (empty($transaction->reference)) {
                $transaction->reference = 'TXN' . date('YmdHis') . rand(100, 999);
            }
            $transaction->montant_total = $transaction->montant + $transaction->frais;
            $transaction->description = $transaction->generateDescription();
        });
    }

    private function generateDescription()
    {
        switch ($this->type) {
            case 'transfert':
                return "Transfert de {$this->montant}FCFA vers {$this->destinataire_nom}";
            case 'paiement':
                $marchand = $this->marchand;
                $nomMarchand = $marchand ? $marchand->nom_commercial : 'Marchand inconnu';
                return "Paiement de {$this->montant}FCFA chez {$nomMarchand}";
            case 'depot':
                return "Dépôt de {$this->montant}FCFA sur votre compte";
            case 'retrait':
                return "Retrait de {$this->montant}FCFA";
            case 'achat_credit':
                return "Achat de crédit de {$this->montant}FCFA";
            default:
                return "Transaction de {$this->montant}FCFA";
        }
    }

    public function emetteur()
    {
        return $this->belongsTo(Compte::class, 'compte_emetteur_id');
    }

    public function destinataire()
    {
        return $this->belongsTo(Compte::class, 'compte_destinataire_id');
    }

    public function marchand()
    {
        return $this->belongsTo(Marchand::class);
    }

    public function scopeValidee($query)
    {
        return $query->where('statut', 'validee');
    }

    public function scopePourUtilisateur($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->whereHas('emetteur', function ($subQ) use ($userId) {
                $subQ->where('user_id', $userId);
            })->orWhereHas('destinataire', function ($subQ) use ($userId) {
                $subQ->where('user_id', $userId);
            });
        });
    }
}
```

## 5. Configuration de l'Authentification

### Configuration de Passport
Dans `config/auth.php`, assurez-vous que :
```php
'guards' => [
    'api' => [
        'driver' => 'passport',
        'provider' => 'users',
    ],
],
```

### Création du Trait HasRolesAndPermissions
```bash
php artisan make:trait HasRolesAndPermissions
```

```php
<?php
namespace App\Traits;

use App\Models\Role;
use App\Models\Permission;

trait HasRolesAndPermissions
{
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function hasRole($role)
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function hasPermission($permission)
    {
        return $this->permissions()->where('name', $permission)->exists() ||
               $this->roles()->whereHas('permissions', function($q) use($permission) {
                   $q->where('name', $permission);
               })->exists();
    }
}
```

## 6. Création des Contrôleurs

### Contrôleur d'Authentification
```bash
php artisan make:controller AuthController
```

```php
<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Compte;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\SmsService;
use App\Traits\ApiResponseTrait;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'telephone' => 'required|string|unique:users',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', $validator->errors(), 422);
        }

        $user = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'password' => Hash::make($request->password),
        ]);

        // Créer le compte
        $compte = Compte::create(['user_id' => $user->id]);

        // Créer le client
        Client::create(['compte_id' => $compte->id]);

        $token = $user->createToken('Personal Access Token')->accessToken;

        return $this->successResponse([
            'user' => $user,
            'token' => $token,
            'compte' => $compte
        ], 'User registered successfully');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'telephone' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', $validator->errors(), 422);
        }

        $user = User::where('telephone', $request->telephone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Invalid credentials', [], 401);
        }

        $token = $user->createToken('Personal Access Token')->accessToken;

        return $this->successResponse([
            'user' => $user,
            'token' => $token
        ], 'Login successful');
    }

    public function profile(Request $request)
    {
        return $this->successResponse($request->user(), 'Profile retrieved successfully');
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return $this->successResponse(null, 'Logged out successfully');
    }
}
```

### Contrôleur de Transactions
```bash
php artisan make:controller TransactionController
```

```php
<?php
namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Compte;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponseTrait;

class TransactionController extends Controller
{
    use ApiResponseTrait;

    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function transfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'montant' => 'required|numeric|min:100',
            'destinataire_numero' => 'required|string',
            'destinataire_nom' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', $validator->errors(), 422);
        }

        try {
            $transaction = $this->transactionService->transfer(
                $request->user(),
                $request->montant,
                $request->destinataire_numero,
                $request->destinataire_nom
            );

            return $this->successResponse($transaction, 'Transfer initiated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 400);
        }
    }

    public function payment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'montant' => 'required|numeric|min:100',
            'marchand_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', $validator->errors(), 422);
        }

        try {
            $transaction = $this->transactionService->payment(
                $request->user(),
                $request->montant,
                $request->marchand_code
            );

            return $this->successResponse($transaction, 'Payment initiated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 400);
        }
    }

    public function deposit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'montant' => 'required|numeric|min:1000',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', $validator->errors(), 422);
        }

        try {
            $transaction = $this->transactionService->deposit(
                $request->user(),
                $request->montant
            );

            return $this->successResponse($transaction, 'Deposit initiated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 400);
        }
    }

    public function withdrawal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'montant' => 'required|numeric|min:100',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', $validator->errors(), 422);
        }

        try {
            $transaction = $this->transactionService->withdrawal(
                $request->user(),
                $request->montant
            );

            return $this->successResponse($transaction, 'Withdrawal initiated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 400);
        }
    }

    public function index(Request $request, $numero_compte)
    {
        $compte = Compte::numero($numero_compte)->first();

        if (!$compte || $compte->user_id !== $request->user()->id) {
            return $this->errorResponse('Unauthorized', [], 403);
        }

        $transactions = Transaction::pourUtilisateur($request->user()->id)
            ->validee()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->successResponse($transactions, 'Transaction history retrieved successfully');
    }

    public function show(Request $request, $id)
    {
        $transaction = Transaction::where('id', $id)
            ->where(function ($q) use ($request) {
                $q->whereHas('emetteur', function ($subQ) use ($request) {
                    $subQ->where('user_id', $request->user()->id);
                })->orWhereHas('destinataire', function ($subQ) use ($request) {
                    $subQ->where('user_id', $request->user()->id);
                });
            })
            ->first();

        if (!$transaction) {
            return $this->errorResponse('Transaction not found', [], 404);
        }

        return $this->successResponse($transaction, 'Transaction details retrieved successfully');
    }
}
```

## 7. Création des Services

### Interface TransactionService
```bash
php artisan make:interface TransactionServiceInterface
```

```php
<?php
namespace App\Interfaces;

interface TransactionServiceInterface
{
    public function transfer($user, $montant, $destinataireNumero, $destinataireNom);
    public function payment($user, $montant, $marchandCode);
    public function deposit($user, $montant);
    public function withdrawal($user, $montant);
    public function verifyTransaction($transactionId, $code);
}
```

### Service TransactionService
```bash
php artisan make:service TransactionService
```

```php
<?php
namespace App\Services;

use App\Models\Transaction;
use App\Models\Compte;
use App\Models\Marchand;
use App\Interfaces\TransactionServiceInterface;
use Illuminate\Support\Facades\DB;
use App\Services\SmsService;

class TransactionService implements TransactionServiceInterface
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function transfer($user, $montant, $destinataireNumero, $destinataireNom)
    {
        $compteEmetteur = $user->compte;

        if ($compteEmetteur->solde < $montant) {
            throw new \Exception('Solde insuffisant');
        }

        $compteDestinataire = Compte::whereHas('user', function($q) use($destinataireNumero) {
            $q->where('telephone', $destinataireNumero);
        })->first();

        if (!$compteDestinataire) {
            throw new \Exception('Compte destinataire introuvable');
        }

        $frais = $this->calculerFrais($montant, 'transfert');

        DB::beginTransaction();
        try {
            $transaction = Transaction::create([
                'compte_emetteur_id' => $compteEmetteur->id,
                'compte_destinataire_id' => $compteDestinataire->id,
                'type' => 'transfert',
                'montant' => $montant,
                'frais' => $frais,
                'destinataire_numero' => $destinataireNumero,
                'destinataire_nom' => $destinataireNom,
                'statut' => 'en_attente',
                'code_verification' => rand(1000, 9999),
            ]);

            // Envoyer SMS avec code de vérification
            $this->smsService->sendVerificationCode($user->telephone, $transaction->code_verification);

            DB::commit();
            return $transaction;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function payment($user, $montant, $marchandCode)
    {
        $compteEmetteur = $user->compte;
        $marchand = Marchand::where('code_marchand', $marchandCode)->first();

        if (!$marchand) {
            throw new \Exception('Marchand introuvable');
        }

        if ($compteEmetteur->solde < $montant) {
            throw new \Exception('Solde insuffisant');
        }

        $frais = $this->calculerFrais($montant, 'paiement');

        DB::beginTransaction();
        try {
            $transaction = Transaction::create([
                'compte_emetteur_id' => $compteEmetteur->id,
                'marchand_id' => $marchand->id,
                'type' => 'paiement',
                'montant' => $montant,
                'frais' => $frais,
                'statut' => 'en_attente',
                'code_verification' => rand(1000, 9999),
            ]);

            $this->smsService->sendVerificationCode($user->telephone, $transaction->code_verification);

            DB::commit();
            return $transaction;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function deposit($user, $montant)
    {
        $compte = $user->compte;

        DB::beginTransaction();
        try {
            $transaction = Transaction::create([
                'compte_emetteur_id' => $compte->id,
                'type' => 'depot',
                'montant' => $montant,
                'frais' => 0,
                'statut' => 'validee',
            ]);

            $compte->increment('solde', $montant);

            DB::commit();
            return $transaction;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function withdrawal($user, $montant)
    {
        $compte = $user->compte;

        if ($compte->solde < $montant) {
            throw new \Exception('Solde insuffisant');
        }

        $frais = $this->calculerFrais($montant, 'retrait');

        DB::beginTransaction();
        try {
            $transaction = Transaction::create([
                'compte_emetteur_id' => $compte->id,
                'type' => 'retrait',
                'montant' => $montant,
                'frais' => $frais,
                'statut' => 'en_attente',
                'code_verification' => rand(1000, 9999),
            ]);

            $this->smsService->sendVerificationCode($user->telephone, $transaction->code_verification);

            DB::commit();
            return $transaction;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function verifyTransaction($transactionId, $code)
    {
        $transaction = Transaction::findOrFail($transactionId);

        if ($transaction->code_verification !== $code) {
            throw new \Exception('Code de vérification incorrect');
        }

        DB::beginTransaction();
        try {
            $transaction->update([
                'statut' => 'validee',
                'code_verifie' => true,
            ]);

            // Mettre à jour les soldes
            if (in_array($transaction->type, ['transfert', 'paiement', 'retrait'])) {
                $emetteur = $transaction->emetteur;
                $emetteur->decrement('solde', $transaction->montant_total);

                if ($transaction->compte_destinataire_id) {
                    $destinataire = $transaction->destinataire;
                    $destinataire->increment('solde', $transaction->montant);
                }
            }

            DB::commit();
            return $transaction;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    private function calculerFrais($montant, $type)
    {
        switch ($type) {
            case 'transfert':
                return $montant > 50000 ? 500 : 200;
            case 'paiement':
                return 0; // Gratuit pour les paiements
            case 'retrait':
                return $montant > 100000 ? 1000 : 500;
            default:
                return 0;
        }
    }
}
```

## 8. Configuration des Routes

Modifiez `routes/api.php` :

```php
<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CompteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Routes publiques
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('verify-code-secret', [AuthController::class, 'verifyCodeSecret']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    // Routes authentifiées
    Route::middleware(['auth:api'])->group(function () {

        // Authentification
        Route::prefix('auth')->group(function () {
            Route::get('profile', [AuthController::class, 'profile']);
            Route::post('logout', [AuthController::class, 'logout']);
        });

        // Comptes
        Route::prefix('comptes')->group(function () {
            Route::get('{numcompte}/balance', [CompteController::class, 'balance']);
        });

        // Transactions
        Route::prefix('transactions')->group(function () {
            Route::get('{id}', [TransactionController::class, 'show']);
            Route::post('transfert', [TransactionController::class, 'transfer']);
            Route::post('paiement', [TransactionController::class, 'payment']);
            Route::post('depot', [TransactionController::class, 'deposit']);
            Route::post('retrait', [TransactionController::class, 'withdrawal']);
            Route::get('{numero_compte}/history', [TransactionController::class, 'index']);
        });

    });

});
```

## 9. Tests et Validation

### Création de Tests
```bash
php artisan make:test AuthTest
php artisan make:test TransactionTest
```

### Exemple de Test d'Authentification
```php
<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $userData = [
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john@example.com',
            'telephone' => '770123456',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'user',
                        'token',
                        'compte'
                    ]
                ]);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'telephone' => '770123456',
            'password' => bcrypt('password123'),
        ]);

        $loginData = [
            'telephone' => '770123456',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/login', $loginData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'user',
                        'token'
                    ]
                ]);
    }
}
```

### Exécution des Tests
```bash
php artisan test
```

## 10. Déploiement

### Configuration pour la Production
1. Configurez les variables d'environnement dans `.env`
2. Optimisez l'application :
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Déploiement sur un Serveur
```bash
# Installation des dépendances
composer install --optimize-autoloader --no-dev

# Configuration
php artisan key:generate
php artisan passport:keys
php artisan migrate --force

# Permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Démarrage du serveur
php artisan serve --host=0.0.0.0 --port=8000
```

### Utilisation de Docker
Créez un `Dockerfile` :
```dockerfile
FROM php:8.1-fpm

# Installation des extensions PHP
RUN docker-php-ext-install pdo pdo_mysql

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --optimize-autoloader --no-dev

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
```

## Conclusion

Vous avez maintenant une API de paiement complète avec Laravel ! Cette API inclut :

- ✅ Authentification avec Passport
- ✅ Gestion des comptes utilisateurs
- ✅ Système de transactions (transferts, paiements, dépôts, retraits)
- ✅ Vérification par SMS
- ✅ Gestion des rôles et permissions
- ✅ API RESTful documentée
- ✅ Tests automatisés

Pour continuer le développement, vous pouvez ajouter :
- Interface d'administration
- Application mobile
- Intégration avec des passerelles de paiement réelles
- Notifications push
- Analytics et rapports

N'hésitez pas à consulter la documentation Laravel officielle pour plus de détails sur chaque fonctionnalité.