<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Base Repository interface for common data access operations.
 *
 * This interface defines the minimal contract that all repositories should implement.
 */
interface RepositoryInterface
{
    /**
     * Get all records.
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Find a record by its primary key.
     */
    public function find(int $id, array $columns = ['*']);

    /**
     * Find a record by its primary key or fail.
     */
    public function findOrFail(int $id, array $columns = ['*']);

    /**
     * Create a new record.
     */
    public function create(array $attributes);

    /**
     * Update a record by its primary key.
     */
    public function update(int $id, array $attributes): bool;

    /**
     * Delete a record by its primary key.
     */
    public function delete(int $id): bool;

    /**
     * Get paginated records.
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Add a where condition.
     */
    public function where(string $column, $operator = null, $value = null, string $boolean = 'and'): static;

    /**
     * Eager load relations.
     */
    public function with(array $relations): static;
}


