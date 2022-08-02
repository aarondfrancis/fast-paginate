<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\FastPaginate;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ScoutMixin
{
    public function fastPaginate($perPage = null, $pageName = 'page', $page = null)
    {
        return function ($perPage = null, $pageName = 'page', $page = null) {
            // Just defer to the Scout Builder for DX purposes. 
            $this->paginate($perPage, $pageName, $page);
        };
    }
}
