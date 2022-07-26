<?php

namespace Hammerstone\FastPaginate\Tests\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */
class Notification extends Model
{
    protected $keyType = 'string';

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
