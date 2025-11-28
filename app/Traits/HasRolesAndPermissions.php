<?php

namespace App\Traits;

use App\Models\Role;
use App\Models\Permission;

/**
 * Trait pour gérer les rôles et permissions des utilisateurs
 * Single Responsibility: Gestion centralisée des autorisations
 *
 * @method bool hasRole(string $roleName)
 * @method bool hasAnyRole(array $roles)
 * @method bool hasPermission(string $permissionName)
 * @method bool hasAnyPermission(array $permissions)
 * @method bool isAdmin()
 * @method bool isClient()
 * @method bool isMarchand()
 */
trait HasRolesAndPermissions
{
    /**
     * Relation many-to-many avec les rôles
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Assigner un rôle à l'utilisateur
     */
    public function assignRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $this->roles()->sync([$role->id]);
        }
    }

    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Vérifier si l'utilisateur a l'un des rôles spécifiés
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Vérifier si l'utilisateur a une permission spécifique
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->roles()->whereHas('permissions', function ($query) use ($permissionName) {
            $query->where('name', $permissionName);
        })->exists();
    }

    /**
     * Vérifier si l'utilisateur a l'une des permissions spécifiées
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->roles()->whereHas('permissions', function ($query) use ($permissions) {
            $query->whereIn('name', $permissions);
        })->exists();
    }

    /**
     * Vérifier si l'utilisateur est admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Vérifier si l'utilisateur est client
     */
    public function isClient(): bool
    {
        return $this->hasRole('client');
    }

    /**
     * Vérifier si l'utilisateur est marchand
     */
    public function isMarchand(): bool
    {
        return $this->hasRole('marchand');
    }

    /**
     * Obtenir le rôle principal de l'utilisateur
     */
    public function getMainRole()
    {
        return $this->roles()->first();
    }

    /**
     * Obtenir toutes les permissions de l'utilisateur
     */
    public function getAllPermissions()
    {
        return Permission::whereHas('roles', function ($query) {
            $query->whereHas('users', function ($subQuery) {
                $subQuery->where('users.id', $this->id);
            });
        })->get();
    }
}