<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Eloquent repository implementation.
 *
 * Concrete repositories should extend this class and implement model(): string.
 */
abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get the model class name.
     */
    abstract public function model(): string;

    /**
     * Get underlying query builder.
     */
    protected function query(): Builder
    {
        return $this->model->newQuery();
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->query()->get($columns);
    }

    public function find(int $id, array $columns = ['*'])
    {
        return $this->query()->find($id, $columns);
    }

    public function findOrFail(int $id, array $columns = ['*'])
    {
        return $this->query()->findOrFail($id, $columns);
    }

    public function create(array $attributes)
    {
        return $this->query()->create($attributes);
    }

    public function update(int $id, array $attributes): bool
    {
        return (bool) $this->query()->where('id', $id)->update($attributes);
    }

    public function delete(int $id): bool
    {
        return (bool) $this->query()->where('id', $id)->delete();
    }

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->query()->paginate($perPage, $columns);
    }

    public function where(string $column, $operator = null, $value = null, string $boolean = 'and'): static
    {
        $this->model = $this->query()->where($column, $operator, $value, $boolean)->getModel();

        return $this;
    }

    public function with(array $relations): static
    {
        $this->model = $this->query()->with($relations)->getModel();

        return $this;
    }
}


