<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace AaronFrancis\FastPaginate;

class ScoutMixin
{
    public function fastPaginate($perPage = null, $pageName = 'page', $page = null, $total = null)
    {
        return function ($perPage = null, $pageName = 'page', $page = null, $total = null) {
            // Just defer to the Scout Builder for DX purposes.
            return $this->paginate($perPage, $pageName, $page, $total);
        };
    }
}
