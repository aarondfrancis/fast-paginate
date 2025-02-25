<?php

namespace AaronFrancis\FastPaginate\Tests\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */
class Notification extends Model
{
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
