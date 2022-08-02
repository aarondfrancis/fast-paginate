<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Tests\Integration;

use Hammerstone\FastPaginate\Tests\Support\UserScout;

class ScoutTest extends BaseTest
{
    /** @test */
    public function basic_scout_test()
    {
        $queries = $this->withQueriesLogged(function () {
            UserScout::search('Person')->paginate();
            UserScout::search('Person')->fastPaginate();
        });

        $this->assertEquals(
            $queries[1]['query'],
            $queries[3]['query']
        );
    }
}
