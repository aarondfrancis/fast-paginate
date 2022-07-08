<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Tests\Integration;

use Hammerstone\FastPaginate\FastPaginateProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

abstract class BaseTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            FastPaginateProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('name');
        });

        for ($i = 1; $i < 30; $i++) {
            DB::table('users')->insert([[
                'id' => $i,
                'name' => "Person $i",
            ]]);

            DB::table('posts')->insert([[
                'name' => "Post $i",
                'user_id' => $i,
            ]]);
        }
    }

    /**
     * @param \Closure(): void  $cb
     * @phpstan-return list<array{query: string, bindings: array<array-key, int|resource|string>, time: float|null}>
     */
    public function withQueriesLogged(callable $cb): array
    {
        DB::enableQueryLog();
        $cb();
        DB::disableQueryLog();

        return DB::getQueryLog();
    }
}
