<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Tests\Integration;

use Hammerstone\FastPaginate\Tests\Support\User;
use Hammerstone\FastPaginate\Tests\Support\UserCustomPage;
use Hammerstone\FastPaginate\Tests\Support\UserCustomTable;
use Hammerstone\FastPaginate\Tests\Support\UserMutatedId;
use Illuminate\Database\QueryException;

class BuilderTest extends BaseTest
{
    /** @test */
    public function basic_test()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->fastPaginate();
        });

        $this->assertEquals(15, $results->count());
        $this->assertEquals('Person 15', $results->last()->name);
        $this->assertCount(3, $queries);

        $this->assertEquals(
            'select * from "users" where "users"."id" in (1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15) limit 16 offset 0',
            $queries[2]['query']
        );
    }

    /** @test */
    public function different_page_size()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->fastPaginate(5);
        });

        $this->assertEquals(5, $results->count());

        $this->assertEquals(
            'select * from "users" where "users"."id" in (1, 2, 3, 4, 5) limit 6 offset 0',
            $queries[2]['query']
        );
    }

    /** @test */
    public function page_2()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->fastPaginate(5, ['*'], 'page', 2);
        });

        $this->assertEquals(5, $results->count());

        $this->assertEquals(
            'select * from "users" where "users"."id" in (6, 7, 8, 9, 10) limit 6 offset 0',
            $queries[2]['query']
        );
    }

    /** @test */
    public function pk_attribute_mutations_are_skipped()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = UserMutatedId::query()->fastPaginate(5);
        });

        $this->assertEquals(5, $results->count());

        $this->assertEquals(
            'select * from "users" where "users"."id" in (1, 2, 3, 4, 5) limit 6 offset 0',
            $queries[2]['query']
        );
    }

    /** @test */
    public function custom_page_is_preserved()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = UserCustomPage::query()->fastPaginate();
        });

        $this->assertEquals(2, $results->count());

        $this->assertEquals(
            'select * from "users" where "users"."id" in (1, 2) limit 3 offset 0',
            $queries[2]['query']
        );
    }

    /** @test */
    public function custom_table_is_preserved()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('no such table: custom_table');

        UserCustomTable::query()->fastPaginate();
    }

    /** @test */
    public function order_is_propagated()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->orderBy('name')->fastPaginate(5);
        });

        $this->assertEquals(
            'select * from "users" where "users"."id" in (1, 10, 11, 12, 13) order by "name" asc limit 6 offset 0',
            $queries[2]['query']
        );
    }

    /** @test */
    public function eager_loads_are_cleared_on_inner_query()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->with('posts')->fastPaginate(5);
        });

        // If we didn't clear the eager loads, there would be 5 queries.
        $this->assertCount(4, $queries);

        // The eager load should come last, after the outer query has run.
        $this->assertEquals(
            'select * from "posts" where "posts"."user_id" in (1, 2, 3, 4, 5)',
            $queries[3]['query']
        );
    }

    /** @test */
    public function eager_loads_are_loaded_on_outer_query()
    {
        $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->with('posts')->fastPaginate();
        });

        $this->assertTrue($results->first()->relationLoaded('posts'));
        $this->assertEquals(1, $results->first()->posts->count());
    }


    /** @test */
    public function selects_are_overwritten()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->selectRaw('(select 1 as complicated_subquery)')->fastPaginate();
        });

        // Dropped for our inner query
        $this->assertEquals(
            'select "users"."id" from "users" limit 15 offset 0',
            $queries[1]['query']
        );

        // Restored for the user's query
        $this->assertEquals(
            'select (select 1 as complicated_subquery) from "users" where "users"."id" in (1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15) limit 16 offset 0',
            $queries[2]['query']
        );
    }
}
