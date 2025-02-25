<?php

/**
 * @author Aaron Francis <aaron@tryhardstudios.com>
 */

namespace AaronFrancis\FastPaginate;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class FastPaginateProvider extends ServiceProvider
{
    public function boot()
    {
        Builder::mixin(new BuilderMixin);
        Relation::mixin(new RelationMixin);

        if (class_exists(\Laravel\Scout\Builder::class)) {
            \Laravel\Scout\Builder::mixin(new ScoutMixin);
        }
    }
}
