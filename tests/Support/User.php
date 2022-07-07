<?php

namespace Hammerstone\FastPaginate\Tests\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */
class User extends Model
{
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
