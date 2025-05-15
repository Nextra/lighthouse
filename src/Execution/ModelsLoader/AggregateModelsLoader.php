<?php declare(strict_types=1);

namespace Nuwave\Lighthouse\Execution\ModelsLoader;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AggregateModelsLoader implements ModelsLoader
{
    public function __construct(
        protected string $relation,
        protected string $column,
        protected string $function,
        protected \Closure $decorateBuilder,
    ) {}

    public function load(EloquentCollection $parents): void
    {
        $parents->loadAggregate([$this->relation => $this->decorateBuilder], $this->column, $this->function);
    }

    public function extract(Model $model): mixed
    {
        return static::extractAggregate($model, $this->relation, $this->column, $this->function);
    }

    public static function extractAggregate(Model $model, string $relationName, string $column, string $function): mixed
    {
        /**
         * This is the name that Eloquent gives to the attribute that contains the aggregate.
         *
         * @see \Illuminate\Database\Eloquent\Concerns\QueriesRelationships::withAggregate()
         */
        $segments = explode(' ', $relationName);

        if (count($segments) === 3 && strtolower($segments[1]) === 'as') {
            $attribute = $segments[2];
        }

        $attribute ??= Str::snake(
            \Safe\preg_replace('/[^[:alnum:][:space:]_]/u', '', "{$relationName} {$function} {$column}"),
        );

        return $model->getAttribute($attribute);
    }
}
