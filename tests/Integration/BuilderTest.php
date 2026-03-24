<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace AaronFrancis\FastPaginate\Tests\Integration;

use AaronFrancis\FastPaginate\Tests\Support\NotificationStringKey;
use AaronFrancis\FastPaginate\Tests\Support\User;
use AaronFrancis\FastPaginate\Tests\Support\UserCollection;
use AaronFrancis\FastPaginate\Tests\Support\UserCustomPage;
use AaronFrancis\FastPaginate\Tests\Support\UserCustomTable;
use AaronFrancis\FastPaginate\Tests\Support\UserMutatedId;
use AaronFrancis\FastPaginate\Tests\Support\UserWithCustomCollection;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use PHPUnit\Framework\Attributes\Test;

class BuilderTest extends Base
{
    private const TOTAL_USERS = 29;

    private const TOTAL_POSTS_FIRST_USER = 1;

    #[Test]
    public function basic_test()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->fastPaginate();
        });

        $this->assertInstanceOf(LengthAwarePaginator::class, $results);
        /** @var LengthAwarePaginator $results */
        $this->assertEquals(15, $results->count());
        $this->assertEquals('Person 15', $results->last()->name);
        $this->assertCount(3, $queries);

        $this->assertEquals(
            'select * from `users` where `users`.`id` in (1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15) order by `users`.`id` asc limit 16 offset 0',
            $queries[2]['query']
        );

        $this->assertTrue($results->hasMorePages());
        $this->assertEquals(1, $results->currentPage());
        $this->assertEquals(self::TOTAL_USERS, $results->total());
    }

    #[Test]
    public function different_page_size()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->fastPaginate(5);
        });

        /** @var LengthAwarePaginator $results */
        $this->assertEquals(5, $results->count());

        $this->assertEquals(
            'select * from `users` where `users`.`id` in (1, 2, 3, 4, 5) order by `users`.`id` asc limit 6 offset 0',
            $queries[2]['query']
        );

        $this->assertTrue($results->hasMorePages());
        $this->assertEquals(1, $results->currentPage());
        $this->assertEquals(self::TOTAL_USERS, $results->total());
    }

    #[Test]
    public function page_2()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->fastPaginate(5, ['*'], 'page', 2);
        });

        /** @var LengthAwarePaginator $results */
        $this->assertEquals(5, $results->count());

        $this->assertEquals(
            'select * from `users` where `users`.`id` in (6, 7, 8, 9, 10) order by `users`.`id` asc limit 6 offset 0',
            $queries[2]['query']
        );

        $this->assertTrue($results->hasMorePages());
        $this->assertEquals(2, $results->currentPage());
        $this->assertEquals(self::TOTAL_USERS, $results->total());
    }

    #[Test]
    public function total_can_be_passed_to_skip_count_query()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            // Pass a custom total to skip the COUNT(*) query
            $results = User::query()->fastPaginate(5, ['*'], 'page', 1, 100);
        });

        /** @var LengthAwarePaginator $results */
        $this->assertEquals(5, $results->count());

        // The $total parameter was added in Laravel 11. On Laravel 10,
        // we can't pass it through, so the COUNT query still runs.
        if (version_compare(app()->version(), '11.0.0', '>=')) {
            // Should only be 2 queries (inner select + outer select), no COUNT query
            $this->assertCount(2, $queries);

            // The total should be the custom value we passed
            $this->assertEquals(100, $results->total());
            $this->assertTrue($results->hasMorePages());
        }
    }

    #[Test]
    public function pk_attribute_mutations_are_skipped()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = UserMutatedId::query()->fastPaginate(5);
        });

        /** @var LengthAwarePaginator $results */
        $this->assertEquals(5, $results->count());

        $this->assertEquals(
            'select * from `users` where `users`.`id` in (1, 2, 3, 4, 5) order by `users`.`id` asc limit 6 offset 0',
            $queries[2]['query']
        );
    }

    #[Test]
    public function custom_page_is_preserved()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = UserCustomPage::query()->fastPaginate();
        });

        /** @var LengthAwarePaginator $results */
        $this->assertEquals(2, $results->count());

        $this->assertEquals(
            'select * from `users` where `users`.`id` in (1, 2) order by `users`.`id` asc limit 3 offset 0',
            $queries[2]['query']
        );

        $this->assertTrue($results->hasMorePages());
        $this->assertEquals(1, $results->currentPage());
        $this->assertEquals(self::TOTAL_USERS, $results->total());
    }

    #[Test]
    public function not_exists_page_is_preserved()
    {
        $exists = User::query()->fastPaginate();

        $queries = $this->withQueriesLogged(function () use (&$doesnt) {
            $doesnt = User::query()->fastPaginate(2, ['*'], 'page', 16);
        });

        $this->assertEquals(get_class($exists), get_class($doesnt));

        /** @var LengthAwarePaginator $doesnt */
        $this->assertEquals(0, $doesnt->count());
        $this->assertArrayNotHasKey(2, $queries);

        $this->assertFalse($doesnt->hasMorePages());
    }

    #[Test]
    public function custom_table_is_preserved()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage("Base table or view not found: 1146 Table 'fast_paginate.custom_table'");

        UserCustomTable::query()->fastPaginate();
    }

    #[Test]
    public function default_order_by_primary_key_when_no_order_specified()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->fastPaginate(5);
        });

        // Inner query should have order by primary key to ensure deterministic results
        $this->assertEquals(
            'select `users`.`id` from `users` order by `users`.`id` asc limit 5 offset 0',
            $queries[1]['query']
        );

        // Outer query should also preserve the order
        $this->assertEquals(
            'select * from `users` where `users`.`id` in (1, 2, 3, 4, 5) order by `users`.`id` asc limit 6 offset 0',
            $queries[2]['query']
        );
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function eager_loads_are_loaded_on_outer_query()
    {
        $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->with('posts')->fastPaginate();
        });

        $this->assertTrue($results->first()->relationLoaded('posts'));
        $this->assertEquals(1, $results->first()->posts->count());
    }

    #[Test]
    public function selects_are_overwritten()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->selectRaw('(select 1 as complicated_subquery)')->fastPaginate();
        });

        // Dropped for our inner query (default order by primary key is added)
        $this->assertEquals(
            'select `users`.`id` from `users` order by `users`.`id` asc limit 15 offset 0',
            $queries[1]['query']
        );

        // Restored for the user's query (with default order)
        $this->assertEquals(
            'select (select 1 as complicated_subquery) from `users` where `users`.`id` in (1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15) order by `users`.`id` asc limit 16 offset 0',
            $queries[2]['query']
        );
    }

    #[Test]
    public function unquoted_selects_are_preserved_if_used_in_order_by()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->selectRaw('(select 1) as computed_column')->orderBy('computed_column')->fastPaginate();
        });

        $this->assertEquals(
            'select `users`.`id`, (select 1) as computed_column from `users` order by `computed_column` asc limit 15 offset 0',
            $queries[1]['query']
        );
    }

    #[Test]
    public function using_expressions_for_order_work()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->selectRaw('(select 1) as computed_column')->orderBy(
                User::query()->select('name')->orderBy('name')->limit(1)->getQuery()
            )->fastPaginate();
        });

        $this->assertEquals(
            'select `users`.`id` from `users` order by (select `name` from `users` order by `name` asc limit 1) asc limit 15 offset 0',
            $queries[1]['query']
        );
    }

    #[Test]
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

    #[Test]
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

        /** @var LengthAwarePaginator $results */
        $this->assertTrue($results->hasMorePages());
        $this->assertEquals(1, $results->currentPage());
        $this->assertEquals(self::TOTAL_USERS, $results->total());
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function uuids_are_bound_correctly()
    {
        $this->seedStringNotifications();

        $queries = $this->withQueriesLogged(function () use (&$results) {
            NotificationStringKey::query()->fastPaginate();
        });

        $this->assertCount(3, $queries);
        $this->assertEquals(
            'select * from `notifications` where `notifications`.`id` in (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) order by `notifications`.`id` asc limit 16 offset 0',
            $queries[2]['query']
        );

        $this->assertCount(15, $queries[2]['bindings']);
        $this->assertEquals('64bf6df6-06d7-11ed-b939-0001', $queries[2]['bindings'][0]);
    }

    #[Test]
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

    #[Test]
    public function basic_simple_test()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->simpleFastPaginate();
        });

        /** @var Paginator $results */
        $this->assertInstanceOf(Paginator::class, $results);
        $this->assertEquals(15, $results->count());
        $this->assertEquals('Person 15', $results->last()->name);
        $this->assertCount(2, $queries);

        $this->assertEquals(
            'select * from `users` where `users`.`id` in (1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15) order by `users`.`id` asc limit 16 offset 0',
            $queries[1]['query']
        );

        $this->assertTrue($results->hasMorePages());
        $this->assertEquals(1, $results->currentPage());
    }

    #[Test]
    public function basic_simple_test_page_two()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::query()->simpleFastPaginate(5, ['*'], 'page', 2);
        });

        /** @var Paginator $results */
        $this->assertInstanceOf(Paginator::class, $results);
        $this->assertEquals(5, $results->count());
        $this->assertEquals('Person 10', $results->last()->name);
        $this->assertCount(2, $queries);

        $this->assertEquals(
            'select * from `users` where `users`.`id` in (6, 7, 8, 9, 10) order by `users`.`id` asc limit 6 offset 0',
            $queries[1]['query']
        );

        $this->assertTrue($results->hasMorePages());
        $this->assertEquals(2, $results->currentPage());
    }

    #[Test]
    public function basic_simple_test_from_relation()
    {
        $queries = $this->withQueriesLogged(function () use (&$results) {
            $results = User::first()->posts()->simpleFastPaginate();
        });

        /** @var Paginator $results */
        $this->assertInstanceOf(Paginator::class, $results);
        $this->assertEquals(1, $results->count());
        $this->assertEquals('Post 1', $results->last()->name);
        $this->assertCount(3, $queries);

        $this->assertEquals(
            'select * from `posts` where `posts`.`user_id` = ? and `posts`.`user_id` is not null and `posts`.`id` in (1) order by `posts`.`id` asc limit 16 offset 0',
            $queries[2]['query']
        );

        $this->assertFalse($results->hasMorePages());
        $this->assertEquals(1, $results->currentPage());
    }

    #[Test]
    public function custom_collection_is_preserved()
    {
        $results = UserWithCustomCollection::query()->simpleFastPaginate();

        $this->assertInstanceOf(UserCollection::class, $results->getCollection());
    }

    #[Test]
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
