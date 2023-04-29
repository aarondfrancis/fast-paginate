<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\FastPaginate\Tests\Integration;

use Hammerstone\FastPaginate\Tests\Support\NotificationStringKey;
use Hammerstone\FastPaginate\Tests\Support\User;
use Hammerstone\FastPaginate\Tests\Support\UserCollection;
use Hammerstone\FastPaginate\Tests\Support\UserCustomPage;
use Hammerstone\FastPaginate\Tests\Support\UserCustomTable;
use Hammerstone\FastPaginate\Tests\Support\UserMutatedId;
use Hammerstone\FastPaginate\Tests\Support\UserWithCustomCollection;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class BuilderTest extends Base
{
    private const TOTAL_USERS = 29;

    private const TOTAL_POSTS_FIRST_USER = 1;

    /** @test */
    public function basic_test()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->fastPaginate();
        });

        $this->assertInstanceOf(LengthAwarePaginator::class, $results);
        /** @var \Illuminate\Pagination\LengthAwarePaginator $results */
        $this->assertEquals(15, $results->count());
        $this->assertEquals('Person 15', $results->last()->name);
        $this->assertCount(3, $queries);

        $this->assertEquals(
            'select * from `users` where `users`.`id` in (1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15) limit 16 offset 0',
            $queries[2]['query']
        );

        $this->assertTrue($results->hasMorePages());
        $this->assertEquals(1, $results->currentPage());
        $this->assertEquals(self::TOTAL_USERS, $results->total());
    }

    /** @test */
    public function different_page_size()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->fastPaginate(5);
        });

        /** @var \Illuminate\Pagination\LengthAwarePaginator $results */
        $this->assertEquals(5, $results->count());

        $this->assertEquals(
            'select * from `users` where `users`.`id` in (1, 2, 3, 4, 5) limit 6 offset 0',
            $queries[2]['query']
        );

        $this->assertTrue($results->hasMorePages());
        $this->assertEquals(1, $results->currentPage());
        $this->assertEquals(self::TOTAL_USERS, $results->total());
    }

    /** @test */
    public function page_2()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->fastPaginate(5, ['*'], 'page', 2);
        });

        /** @var \Illuminate\Pagination\LengthAwarePaginator $results */
        $this->assertEquals(5, $results->count());

        $this->assertEquals(
            'select * from `users` where `users`.`id` in (6, 7, 8, 9, 10) limit 6 offset 0',
            $queries[2]['query']
        );

        $this->assertTrue($results->hasMorePages());
        $this->assertEquals(2, $results->currentPage());
        $this->assertEquals(self::TOTAL_USERS, $results->total());
    }

    /** @test */
    public function pk_attribute_mutations_are_skipped()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = UserMutatedId::query()->fastPaginate(5);
        });

        /** @var \Illuminate\Pagination\LengthAwarePaginator $results */
        $this->assertEquals(5, $results->count());

        $this->assertEquals(
            'select * from `users` where `users`.`id` in (1, 2, 3, 4, 5) limit 6 offset 0',
            $queries[2]['query']
        );
    }

    /** @test */
    public function custom_page_is_preserved()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = UserCustomPage::query()->fastPaginate();
        });

        /** @var \Illuminate\Pagination\LengthAwarePaginator $results */
        $this->assertEquals(2, $results->count());

        $this->assertEquals(
            'select * from `users` where `users`.`id` in (1, 2) limit 3 offset 0',
            $queries[2]['query']
        );

        $this->assertTrue($results->hasMorePages());
        $this->assertEquals(1, $results->currentPage());
        $this->assertEquals(self::TOTAL_USERS, $results->total());
    }

    /** @test */
    public function custom_table_is_preserved()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage("Base table or view not found: 1146 Table 'fast_paginate.custom_table'");

        UserCustomTable::query()->fastPaginate();
    }

    /** @test */
    public function order_is_propagated()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->orderBy('name')->fastPaginate(5);
        });

        $this->assertEquals(
            'select * from `users` where `users`.`id` in (1, 10, 11, 12, 13) order by `name` asc limit 6 offset 0',
            $queries[2]['query']
        );
    }

    /** @test */
    public function order_by_raw_is_propagated()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->orderByRaw('id % 2')->orderBy('id')->fastPaginate(5);
        });

        $this->assertEquals(
            'select * from `users` where `users`.`id` in (2, 4, 6, 8, 10) order by id % 2, `id` asc limit 6 offset 0',
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
            'select * from `posts` where `posts`.`user_id` in (1, 2, 3, 4, 5)',
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
            'select `users`.`id` from `users` limit 15 offset 0',
            $queries[1]['query']
        );

        // Restored for the user's query
        $this->assertEquals(
            'select (select 1 as complicated_subquery) from `users` where `users`.`id` in (1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15) limit 16 offset 0',
            $queries[2]['query']
        );
    }

    /** @test */
    public function havings_defer()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            User::query()
                ->selectRaw('*, concat(name, id) as name_id')
                ->having('name_id', '!=', '')
                ->fastPaginate();
        });

        $this->assertCount(2, $queries);
        $this->assertEquals(
            'select *, concat(name, id) as name_id from `users` having `name_id` != ? limit 15 offset 0',
            $queries[1]['query']
        );
    }

    /** @test */
    public function standard_with_count_works()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->withCount('posts')->orderByDesc('posts_count')->fastPaginate();
        });

        $this->assertCount(3, $queries);
        $this->assertEquals(
            'select `users`.`id`, (select count(*) from `posts` where `users`.`id` = `posts`.`user_id`) as `posts_count` from `users` order by `posts_count` desc limit 15 offset 0',
            $queries[1]['query']
        );

        /** @var \Illuminate\Pagination\LengthAwarePaginator $results */
        $this->assertTrue($results->hasMorePages());
        $this->assertEquals(1, $results->currentPage());
        $this->assertEquals(self::TOTAL_USERS, $results->total());
    }

    /** @test */
    public function aliased_with_count()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            User::query()->withCount('posts as posts_ct')->orderByDesc('posts_ct')->fastPaginate();
        });

        $this->assertCount(3, $queries);
        $this->assertEquals(
            'select `users`.`id`, (select count(*) from `posts` where `users`.`id` = `posts`.`user_id`) as `posts_ct` from `users` order by `posts_ct` desc limit 15 offset 0',
            $queries[1]['query']
        );
    }

    /** @test */
    public function unordered_with_count_is_ignored()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            User::query()->withCount('posts')->orderByDesc('id')->fastPaginate();
        });

        $this->assertCount(3, $queries);
        $this->assertEquals(
            'select `users`.`id` from `users` order by `id` desc limit 15 offset 0',
            $queries[1]['query']
        );
    }

    /** @test */
    public function uuids_are_bound_correctly()
    {
        $this->seedStringNotifications();

        $queries = $this->withQueriesLogged(function () use (&$results) {
            NotificationStringKey::query()->fastPaginate();
        });

        $this->assertCount(3, $queries);
        $this->assertEquals(
            'select * from `notifications` where `notifications`.`id` in (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) limit 16 offset 0',
            $queries[2]['query']
        );

        $this->assertCount(15, $queries[2]['bindings']);
        $this->assertEquals('64bf6df6-06d7-11ed-b939-0001', $queries[2]['bindings'][0]);
    }

    /** @test */
    public function groups_are_skipped()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            User::query()->select(['name'])->groupBy('name')->fastPaginate();
        });

        $this->assertCount(2, $queries);
        $this->assertEquals(
            'select `name` from `users` group by `name` limit 15 offset 0',
            $queries[1]['query']
        );
    }

    /** @test */
    public function basic_simple_test()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->simpleFastPaginate();
        });

        /** @var \Illuminate\Pagination\Paginator $results */
        $this->assertInstanceOf(Paginator::class, $results);
        $this->assertEquals(15, $results->count());
        $this->assertEquals('Person 15', $results->last()->name);
        $this->assertCount(2, $queries);

        $this->assertEquals(
            'select * from `users` where `users`.`id` in (1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15) limit 16 offset 0',
            $queries[1]['query']
        );

        $this->assertTrue($results->hasMorePages());
        $this->assertEquals(1, $results->currentPage());
    }

    /** @test */
    public function basic_simple_test_page_two()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->simpleFastPaginate(5, ['*'], 'page', 2);
        });

        /** @var \Illuminate\Pagination\Paginator $results */
        $this->assertInstanceOf(Paginator::class, $results);
        $this->assertEquals(5, $results->count());
        $this->assertEquals('Person 10', $results->last()->name);
        $this->assertCount(2, $queries);

        $this->assertEquals(
            'select * from `users` where `users`.`id` in (6, 7, 8, 9, 10) limit 6 offset 0',
            $queries[1]['query']
        );

        $this->assertTrue($results->hasMorePages());
        $this->assertEquals(2, $results->currentPage());
    }

    /** @test */
    public function basic_simple_test_from_relation()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::first()->posts()->simpleFastPaginate();
        });

        /** @var \Illuminate\Pagination\Paginator $results */
        $this->assertInstanceOf(Paginator::class, $results);
        $this->assertEquals(1, $results->count());
        $this->assertEquals('Post 1', $results->last()->name);
        $this->assertCount(3, $queries);

        $this->assertEquals(
            'select * from `posts` where `posts`.`user_id` = ? and `posts`.`user_id` is not null and `posts`.`id` in (1) limit 16 offset 0',
            $queries[2]['query']
        );

        $this->assertFalse($results->hasMorePages());
        $this->assertEquals(1, $results->currentPage());
    }

    /** @test */
    public function custom_collection_is_preserved()
    {
        $results = UserWithCustomCollection::query()->simpleFastPaginate();

        $this->assertInstanceOf(UserCollection::class, $results->getCollection());
    }

    /** @test */
    public function with_sum_has_the_correct_number_of_parameters()
    {
        $queries = $this->withQueriesLogged(function () use (&$fast, &$regular) {
            $fast = User::query()
                ->withSum([
                    'posts as views_count' => function ($query) {
                        $query->where('views', '>', 0);
                    },
                ], 'views')
                ->orderBy('views_count')
                ->fastPaginate();

            $regular = User::query()
                ->withSum([
                    'posts as views_count' => function ($query) {
                        $query->where('views', '>', 0);
                    },
                ], 'views')
                ->orderBy('views_count')
                ->paginate();
        });

        $this->assertEquals($queries[0]['query'], $queries[2]['query']);
        $this->assertEquals($queries[0]['bindings'], $queries[2]['bindings']);

        $this->assertEquals($queries[1]['query'], $queries[3]['query']);
        $this->assertEquals($queries[1]['bindings'], $queries[3]['bindings']);

        $this->assertEquals($fast->toArray(), $regular->toArray());

        $this->assertEquals(get_class($fast), get_class($regular));
    }

    public function test_for_union_query()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::where('id', '<', 10)
                ->unionAll(User::where('id', '>', 10))
                ->fastPaginate(2);
        });

        $this->assertEquals($queries[0]['query'],
            'select count(*) as aggregate from ((select * from `users` where `id` < ?) union all (select * from `users` where `id` > ?)) as `temp_table`');

        $this->assertEquals($queries[1]['query'],
            '(select * from `users` where `id` < ?) union all (select * from `users` where `id` > ?) limit 2 offset 0');
    }
}
