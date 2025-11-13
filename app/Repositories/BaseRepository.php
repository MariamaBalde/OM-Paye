<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Classe de base pour tous les repositories
 * Open/Closed Principle: Extensible sans modification
 * Single Responsibility: Gestion des opérations CRUD communes
 */
abstract class BaseRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Récupère tous les enregistrements
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Recherche par ID
     */
    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * Recherche par ID ou exception
     */
    public function findOrFail(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Crée un nouvel enregistrement
     */
    public function create(array $attributes): Model
    {
        return $this->model->create($attributes);
    }

    /**
     * Met à jour un enregistrement
     */
    public function update(Model $model, array $attributes): bool
    {
        return $model->update($attributes);
    }

    /**
     * Supprime un enregistrement
     */
    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    /**
     * Pagination avec filtres
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->applyFilters($this->model->newQuery(), $filters);
        return $query->paginate($perPage);
    }

    /**
     * Applique les filtres à la requête
     * Open/Closed: Peut être étendu par les classes enfants
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $filter => $value) {
            if (method_exists($this, 'filter' . ucfirst($filter))) {
                $method = 'filter' . ucfirst($filter);
                $query = $this->$method($query, $value);
            }
        }
        return $query;
    }

    /**
     * Filtre par date de création
     */
    protected function filterCreatedAt(Builder $query, $value): Builder
    {
        return $query->whereDate('created_at', $value);
    }

    /**
     * Filtre par statut
     */
    protected function filterStatus(Builder $query, $value): Builder
    {
        return $query->where('statut', $value);
    }

    /**
     * Tri par colonne
     */
    public function orderBy(string $column, string $direction = 'asc'): Builder
    {
        return $this->model->orderBy($column, $direction);
    }

    /**
     * Charge les relations
     */
    public function with(array $relations): Builder
    {
        return $this->model->with($relations);
    }

    /**
     * Recherche avec conditions
     */
    public function where(array $conditions): Builder
    {
        return $this->model->where($conditions);
    }

    /**
     * Recherche avec relation
     */
    public function whereHas(string $relation, \Closure $callback): Builder
    {
        return $this->model->whereHas($relation, $callback);
    }
}