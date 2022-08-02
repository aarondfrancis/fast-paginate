<?php

namespace Hammerstone\FastPaginate\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */
class UserScout extends Model
{
    use Searchable;

    protected $table = 'users';

    public function toSearchableArray()
    {
        return $this->toArray();
    }
}
