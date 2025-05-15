<?php declare(strict_types=1);

namespace Tests\Integration\Schema\Directives;

use Nuwave\Lighthouse\Exceptions\DefinitionException;
use Tests\DBTestCase;
use Tests\Utils\Models\Task;
use Tests\Utils\Models\User;

final class WithCountDirectiveTest extends DBTestCase
{
    public function testEagerLoadsRelationCount(): void
    {
        $this->schema = /** @lang GraphQL */ '
        type Query {
            users: [User!] @all
        }

        type User {
            tasksCountLoaded: Boolean!
                @withCount(relation: "tasks")
                @method
        }
        ';

        factory(User::class, 3)->create()
            ->each(static function (User $user): void {
                factory(Task::class, 3)->create([
                    'user_id' => $user->getKey(),
                ]);
            });

        $this->assertQueryCountMatches(2, function (): void {
            $this->graphQL(/** @lang GraphQL */ '
            {
                users {
                    tasksCountLoaded
                }
            }
            ')->assertExactJson([
                'data' => [
                    'users' => [
                        [
                            'tasksCountLoaded' => true,
                        ],
                        [
                            'tasksCountLoaded' => true,
                        ],
                        [
                            'tasksCountLoaded' => true,
                        ],
                    ],
                ],
            ]);
        });
    }

    public function testFailsToEagerLoadRelationCountWithoutRelation(): void
    {
        $this->schema = /** @lang GraphQL */ '
        type Query {
            users: [User!] @all
        }

        type User {
            name: String! @withCount
        }
        ';

        factory(User::class)->create();

        $this->expectException(DefinitionException::class);
        $this->graphQL(/** @lang GraphQL */ '
        {
            users {
                name
            }
        }
        ');
    }

    public function testAliasedRelationCountWithDifferentScopes(): void
    {
        $this->schema = /** @lang GraphQL */ '
        type Query {
            users: [User!] @all
        }

        type User {
            tasksCount: Int
                @withCount(relation: "tasks")
                @method

            tasksCompleted: Int
                @withCount(relation: "tasks as tasks_completed", scopes: ["completed"])
                @method
        }
        ';

        factory(User::class, 3)->create()
            ->each(static function (User $user, int $index): void {
                factory(Task::class, 3 - $index)->create([
                    'user_id' => $user->getKey(),
                ]);

                factory(Task::class, 3 - $index)->create([
                    'user_id' => $user->getKey(),
                    'completed_at' => now(),
                ]);
            });

        $this->assertQueryCountMatches(3, function (): void {
            $this->graphQL(/** @lang GraphQL */ '
            {
                users {
                    tasksCount
                    tasksCompleted
                }
            }
            ')->assertExactJson([
                'data' => [
                    'users' => [
                        [
                            'tasksCount' => 6,
                            'tasksCompleted' => 3,
                        ],
                        [
                            'tasksCount' => 4,
                            'tasksCompleted' => 2,
                        ],
                        [
                            'tasksCount' => 2,
                            'tasksCompleted' => 1,
                        ],
                    ],
                ],
            ]);
        });
    }
}
