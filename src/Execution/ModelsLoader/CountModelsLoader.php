<?php declare(strict_types=1);

namespace Nuwave\Lighthouse\Execution\ModelsLoader;

use GraphQL\Utils\Utils;
use Illuminate\Database\Eloquent\Model;

class CountModelsLoader extends AggregateModelsLoader
{
    public function __construct(
        protected string $relation,
        protected \Closure $decorateBuilder,
    ) {
        parent::__construct(
            relation: $relation,
            column: '*',
            function: 'count',
            decorateBuilder: $decorateBuilder,
        );
    }

    public static function extractCount(Model $model, string $relationName): int
    {
        $count = static::extractAggregate($model, $relationName, '*', 'count');
        if (! is_numeric($count)) {
            $nonNumericCount = Utils::printSafe($count);
            throw new \Exception("Expected numeric count, got: {$nonNumericCount}.");
        }

        return (int) $count;
    }
}
