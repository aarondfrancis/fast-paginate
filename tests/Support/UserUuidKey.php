<?php

namespace Hammerstone\FastPaginate\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class UserUuidKey extends Model
{
    protected $table = 'users_uuid_primary';

    protected $guarded = [];

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    public static function booted()
    {
        static::creating(function ($model) {
            if (! $model->getKey()) {
                $model->setAttribute($model->getKeyName(), (string) Str::uuid());
            }
        });
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }
}

