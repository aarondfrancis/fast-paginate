<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\FastPaginate\Tests\Integration;

use Hammerstone\FastPaginate\Tests\Support\User;

class RelationTest extends Base
{
    /** @test */
    public function basic_test()
    {
        $user = User::first();
        $queries = $this->withQueriesLogged(function () use ($user) {
            $user->posts()->fastPaginate();
        });

        $this->assertEquals(
            'select * from `posts` where `posts`.`user_id` = ? and `posts`.`user_id` is not null and `posts`.`id` in (1) limit 16 offset 0',
            $queries[2]['query']
        );
    }

    /** @test */
    public function belongs_to_many_test()
    {
        $user = User::first();
        $queries = $this->withQueriesLogged(function () use ($user, &$results) {
            $results = $user->notifications()->orderBy('created_at')->fastPaginate();
        });

        $this->assertEquals(
            'select `notifications`.`id` from `notifications` inner join `notification_user` on `notifications`.`id` = `notification_user`.`notification_id` where `notification_user`.`user_id` = ? order by `created_at` asc limit 15 offset 0',
            $queries[1]['query']
        );
        $this->assertEquals(
            'select `notifications`.*, `notification_user`.`user_id` as `pivot_user_id`, `notification_user`.`notification_id` as `pivot_notification_id` from `notifications` inner join `notification_user` on `notifications`.`id` = `notification_user`.`notification_id` where `notification_user`.`user_id` = ? and `notifications`.`id` in (1) order by `created_at` asc limit 16 offset 0',
            $queries[2]['query']
        );
    }

    /** @test */
    public function belongs_to_many_add_select_alias_test()
    {
        $user = User::first();
        $queries = $this->withQueriesLogged(function () use ($user) {
            $user->notifications()
                ->orderBy('created_at')
                ->addSelect('notification_user.created_at as pivot_created_at')
                ->fastPaginate();
        });

        $this->assertEquals(
            'select `notifications`.`id` from `notifications` inner join `notification_user` on `notifications`.`id` = `notification_user`.`notification_id` where `notification_user`.`user_id` = ? order by `created_at` asc limit 15 offset 0',
            $queries[1]['query']
        );

        $this->assertEquals(
            'select `notification_user`.`created_at` as `pivot_created_at`, `notifications`.*, `notification_user`.`user_id` as `pivot_user_id`, `notification_user`.`notification_id` as `pivot_notification_id` from `notifications` inner join `notification_user` on `notifications`.`id` = `notification_user`.`notification_id` where `notification_user`.`user_id` = ? and `notifications`.`id` in (1) order by `created_at` asc limit 16 offset 0',
            $queries[2]['query']
        );
    }

    /** @test */
    public function belongs_to_many_order_by_alias_test()
    {
        $user = User::first();
        $queries = $this->withQueriesLogged(function () use ($user) {
            $user->notifications()
                ->addSelect('notification_user.created_at as pivot_created_at')
                ->orderBy('pivot_created_at')
                ->fastPaginate();
        });

        $this->assertEquals(
            'select `notifications`.`id`, `notification_user`.`created_at` as `pivot_created_at` from `notifications` inner join `notification_user` on `notifications`.`id` = `notification_user`.`notification_id` where `notification_user`.`user_id` = ? order by `pivot_created_at` asc limit 15 offset 0',
            $queries[1]['query']
        );

        $this->assertEquals(
            'select `notification_user`.`created_at` as `pivot_created_at`, `notifications`.*, `notification_user`.`user_id` as `pivot_user_id`, `notification_user`.`notification_id` as `pivot_notification_id` from `notifications` inner join `notification_user` on `notifications`.`id` = `notification_user`.`notification_id` where `notification_user`.`user_id` = ? and `notifications`.`id` in (1) order by `pivot_created_at` asc limit 16 offset 0',
            $queries[2]['query']
        );
    }

    /** @test */
    public function belongs_to_many_hydrating_pivot_test()
    {
        $user = User::first();
        $queries = $this->withQueriesLogged(function () use ($user) {
            $user->notifications()
                ->withPivot('created_at')
                ->orderBy('pivot_created_at')
                ->fastPaginate();
        });

        $this->assertEquals(
            'select `notifications`.`id`, `notification_user`.`created_at` as `pivot_created_at` from `notifications` inner join `notification_user` on `notifications`.`id` = `notification_user`.`notification_id` where `notification_user`.`user_id` = ? order by `pivot_created_at` asc limit 15 offset 0',
            $queries[1]['query']
        );

        $this->assertEquals(
            'select `notifications`.*, `notification_user`.`user_id` as `pivot_user_id`, `notification_user`.`notification_id` as `pivot_notification_id`, `notification_user`.`created_at` as `pivot_created_at` from `notifications` inner join `notification_user` on `notifications`.`id` = `notification_user`.`notification_id` where `notification_user`.`user_id` = ? and `notifications`.`id` in (1) order by `pivot_created_at` asc limit 16 offset 0',
            $queries[2]['query']
        );
    }
}
