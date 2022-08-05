<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\FastPaginate;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class RelationMixin
{
    public function fastPaginate()
    {
        return function ($perPage = null, $columns = ['*'], $pageName = 'page', $page = null) {
            /** @var \Illuminate\Database\Eloquent\Relations\Relation $this */
            if ($this instanceof HasManyThrough || $this instanceof BelongsToMany) {
                $this->query->addSelect($this->shouldSelect($columns));
            }

            return tap($this->query->fastPaginate($perPage, $columns, $pageName, $page), function ($paginator) {
                if ($this instanceof BelongsToMany) {
                    $this->hydratePivotRelation($paginator->items());
                }
            });
        };
    }

    public function simpleFastPaginate()
    {
        return function ($perPage = null, $columns = ['*'], $pageName = 'page', $page = null) {
            /** @var \Illuminate\Database\Eloquent\Relations\Relation $this */
            if ($this instanceof HasManyThrough || $this instanceof BelongsToMany) {
                $this->query->addSelect($this->shouldSelect($columns));
            }

            return tap($this->query->simpleFastPaginate($perPage, $columns, $pageName, $page), function ($paginator) {
                if ($this instanceof BelongsToMany) {
                    $this->hydratePivotRelation($paginator->items());
                }
            });
        };
    }
}
