<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Tests\Integration;

use Hammerstone\FastPaginate\Tests\Support\User;

class RelationTest extends BaseTest
{
    /** @test */
    public function basic_test(): void
    {
        /** @var \Hammerstone\FastPaginate\Tests\Support\User $user */
        $user = User::first();
        $queries = $this->withQueriesLogged(function () use ($user, &$results) {
            $results = $user->posts()->fastPaginate();
        });

        $this->assertEquals(
            'select * from "posts" where "posts"."user_id" = ? and "posts"."user_id" is not null and "posts"."id" in (1) limit 16 offset 0',
            $queries[2]['query']
        );
    }

    // @TODO Test hydrating pivots for BelongsToMany
    // @TODO Test adding selects for BelongsToMany and HasManyThrough
}
