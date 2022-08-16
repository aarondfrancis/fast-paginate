<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\FastPaginate;

class BuilderMixin
{
    /**
     * @deprecated deprecated, use fastSimplePaginate
     */
    public function simpleFastPaginate()
    {
        return $this->fastSimplePaginate();
    }

    public function fastSimplePaginate()
    {
        return (new FastPaginate())->fastSimplePaginate();
    }

    public function fastPaginate()
    {
        return (new FastPaginate())->fastPaginate();
    }
}
