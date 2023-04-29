<?php

namespace Hammerstone\FastPaginate\Tests\Support;

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */
class UserWithCustomCollection extends User
{
    public function newCollection(array $models = [])
    {
        return new UserCollection($models);
    }
}
