<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\FastPaginate;

use Illuminate\Database\Query\Expression;
use Illuminate\Pagination\LengthAwarePaginator;

class BuilderMixin
{
    public function fastPaginate()
    {
        return function ($perPage = null, $columns = ['*'], $pageName = 'page', $page = null) {
            /** @var \Illuminate\Database\Eloquent\Builder $this */
            $base = $this->getQuery();

            // Havings and groups don't work well with this paradigm, because we are
            // counting on each row of the inner query to return a primary key
            // that we can use. When grouping, that's not always the case.
            if (filled($base->havings) || filled($base->groups)) {
                return $this->paginate($perPage, $columns, $pageName, $page);
            }

            $model = $this->newModelInstance();
            $key = $model->getKeyName();
            $table = $model->getTable();

            // Collect all of the `orders` off of the base query and pluck
            // the column out. Based on what orders are present, we may
            // have to include certain columns in the inner query.
            $orders = collect($base->orders)
                ->pluck('column')
                ->map(function ($column) use ($base) {
                    // Use the grammar to wrap them, so that our `str_contains`
                    // (further down) doesn't return any false positives.
                    return $base->grammar->wrap($column);
                });

            $innerSelectColumns = collect($base->columns)
                ->filter(function ($column) use ($orders, $base) {
                    $column = $column instanceof Expression ? $column->getValue() : $base->grammar->wrap($column);
                    foreach ($orders as $order) {
                        // If we're ordering by this column, then we need to
                        // keep it in the inner query.
                        if (str_contains($column, "as $order")) {
                            return true;
                        }
                    }

                    // Otherwise we don't.
                    return false;
                })
                ->prepend("$table.$key")
                ->unique()
                ->values()
                ->toArray();

            // This is the copy of the query that becomes
            // the inner query that selects keys only.
            $paginator = $this->clone()
                // Only select the primary key, we'll get the full
                // records in a second query below.
                ->select($innerSelectColumns)
                // We don't need eager loads for this cloned query, they'll
                // remain on the query that actually gets the records.
                // (withoutEagerLoads not available on Laravel 8.)
                ->setEagerLoads([])
                ->paginate($perPage, ['*'], $pageName, $page);

            // Get the key values from the records on the current page without mutating them.
            $ids = $paginator->getCollection()->map->getRawOriginal($key)->toArray();

            if ($model->getKeyType() === 'int') {
                $this->query->whereIntegerInRaw("$table.$key", $ids);
            } else {
                $this->query->whereIn("$table.$key", $ids);
            }

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
