<?php

namespace AaronFrancis\FastPaginate\Tests\Support;

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */
class UserMutatedId extends User
{
    public function getIdAttribute($val)
    {
        return "id:$val";
    }
}
