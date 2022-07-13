<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\FastPaginate;

use Illuminate\Pagination\LengthAwarePaginator;

class BuilderMixin
{
    public function fastPaginate()
    {
        return function ($perPage = null, $columns = ['*'], $pageName = 'page', $page = null) {
            /** @var \Illuminate\Database\Eloquent\Builder $this */

            // If there is a `having` clause then it relies on certain columns being present
            // in the select, which we overwrite. In that case it's safest to just defer.
            if (filled($this->getQuery()->havings)) {
                return $this->paginate($perPage, $columns, $pageName, $page);
            }

            $model = $this->newModelInstance();
            $key = $model->getKeyName();
            $table = $model->getTable();

            // This is the copy of the query that becomes
            // the inner query that selects keys only.
            $paginator = $this->clone()
                // Only select the primary key, we'll get the full
                // records in a second query below.
                ->select(["$table.$key"])
                // We don't need eager loads for this cloned query, they'll
                // remain on the query that actually gets the records.
                // (withoutEagerLoads not available on Laravel 8.)
                ->setEagerLoads([])
                ->withCount([])
                ->reorder()
                ->paginate($perPage, ['*'], $pageName, $page);

            // Get the key values from the records on the current page without mutating them.
            $ids = $paginator->getCollection()->map->getRawOriginal($key)->toArray();

            // Add our values in directly using "raw" instead of adding new bindings.
            // This is basically the `whereIntegerInRaw` that Laravel uses in some
            // places, but we're not guaranteed the primary keys are integers, so
            // we can't use that. We're sure that these values are safe because
            // they came directly from the database in the first place.
            $this->query->wheres[] = [
                'type' => 'InRaw',
                'column' => "$table.$key",
                'values' => $ids,
                'boolean' => 'and',
            ];

            // The $paginator is full of records that are primary keys only. Here,
            // we create a new paginator with all of the *stats* from the index-
            // only paginator, but the *items* from the outer query.
            return new LengthAwarePaginator(
                $this->simplePaginate($perPage, $columns, $pageName, 1)->items(),
                $paginator->total(),
                $paginator->perPage(),
                $paginator->currentPage(),
                $paginator->getOptions()
            );
        };
    }
}
