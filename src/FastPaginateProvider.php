<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\FastPaginate;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class FastPaginateProvider extends ServiceProvider
{
    public function boot()
    {
        Builder::mixin(new BuilderMixin());
        Relation::mixin(new RelationMixin());
    }
}
