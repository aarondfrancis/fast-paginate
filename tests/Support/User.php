<?php

namespace Hammerstone\FastPaginate\Tests\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */
class User extends Model
{
    protected $table = 'users';

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
