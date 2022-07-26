<?php

namespace Hammerstone\FastPaginate\Tests\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */
class NotificationStringKey extends Model
{
    protected $table = 'notifications';

    protected $keyType = 'string';
}
