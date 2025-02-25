<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace AaronFrancis\FastPaginate;

class BuilderMixin
{
    public function simpleFastPaginate()
    {
        return (new FastPaginate)->simpleFastPaginate();
    }

    public function fastPaginate()
    {
        return (new FastPaginate)->fastPaginate();
    }
}
