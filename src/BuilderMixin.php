<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\FastPaginate;

use Illuminate\Pagination\Paginator;
use Illuminate\Database\Query\Expression;
use Illuminate\Pagination\LengthAwarePaginator;

class BuilderMixin
{
    public function simpleFastPaginate() {
        return (new FastPaginate())->simpleFastPaginate();
    }

    public function fastPaginate()
    {
        return (new FastPaginate())->fastPaginate();
    }
}
