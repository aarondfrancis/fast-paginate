<?php

Builder::macro('deferredPaginate', function ($perPage = null, $columns = ['*'], $pageName = 'page', $page = null) {
    $model = $this->newModelInstance();
    $key = $model->getKeyName();
    $table = $model->getTable();

    $paginator = $this->clone()
        // We don't need them for this query, they'll remain
        // on the query that actually gets the records.
        ->setEagerLoads([])
        // Only select the primary key, we'll get the full
        // records in a second query below.
        ->paginate($perPage, ["$table.$key"], $pageName, $page);

    // Add our values in directly using "raw," instead of adding new bindings.
    // This is basically the `whereIntegerInRaw` that Laravel uses in some
    // places, but we're not guaranteed the primary keys are integers, so
    // we can't use that. We're sure that these values are safe because
    // they came directly from the database in the first place.
    $this->query->wheres[] = [
        'type'   => 'InRaw',
        'column' => "$table.$key",
        // Get the key values from the records on the *current* page, without mutating them.
        'values'  => $paginator->getCollection()->map->getRawOriginal($key)->toArray(),
        'boolean' => 'and'
    ];

    // simplePaginate increments by one to see if there's another page. We'll
    // decrement to counteract that since it's unnecessary in our situation.
    $page = $this->simplePaginate($paginator->perPage() - 1, $columns, null, 1);

    // Create a new paginator so that we can put our full records in,
    // not the ones that we modified to select only the primary key.
    return new LengthAwarePaginator(
        $page->items(),
        $paginator->total(),
        $paginator->perPage(),
        $paginator->currentPage(),
        $paginator->getOptions()
    );
});

Relation::macro('deferredPaginate', function ($perPage = null, $columns = ['*'], $pageName = 'page', $page = null) {
    if ($this instanceof HasManyThrough || $this instanceof BelongsToMany) {
        $this->query->addSelect($this->shouldSelect($columns));
    }

    return tap($this->query->deferredPaginate($perPage, $columns, $pageName, $page), function ($paginator) {
        if ($this instanceof BelongsToMany) {
            $this->hydratePivotRelation($paginator->items());
        }
    });
});
