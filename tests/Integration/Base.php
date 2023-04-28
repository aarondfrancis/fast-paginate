<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\FastPaginate\Tests\Integration;

use Hammerstone\FastPaginate\FastPaginateProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Scout\ScoutServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class Base extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            FastPaginateProvider::class,
            ScoutServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutDeprecationHandling')) {
            $this->withoutDeprecationHandling();
        }

        Schema::dropIfExists('users');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notification_user');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('name');
            $table->integer('views');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->text('message');
        });

        Schema::create('notification_user', function (Blueprint $table) {
            $table->unsignedBigInteger('notification_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        for ($i = 1; $i < 30; $i++) {
            DB::table('users')->insert([[
                'id' => $i,
                'name' => "Person $i",
            ]]);

            DB::table('posts')->insert([[
                'name' => "Post $i",
                'user_id' => $i,
                'views' => 1,
            ]]);

            DB::table('notifications')->insert([[
                'id' => $i,
                'message' => str_repeat('a', $i),
            ]]);

            DB::table('notification_user')->insert([[
                'notification_id' => $i,
                'user_id' => $i,
            ]]);
        }
    }

    protected function seedStringNotifications()
    {
        Schema::dropIfExists('notifications');

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('body');
        });

        for ($i = 1; $i < 30; $i++) {
            DB::table('notifications')->insert([[
                'id' => "64bf6df6-06d7-11ed-b939-000$i",
                'body' => "Message $i",
            ]]);
        }
    }

    public function withQueriesLogged($cb)
    {
        DB::enableQueryLog();
        $cb();
        DB::disableQueryLog();

        return DB::getQueryLog();
    }
}
