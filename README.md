# Fast Paginate for Laravel

## About

This is a fast `limit`/`offset` pagination macro for Laravel. It can be used in place of the standard `paginate` methods. 

This packages uses a SQL method similar to a "deferred join" to achieve this speedup. A deferred join is a technique that defers access to requested columns until _after_ the `offset` and `limit` have been applied.

In our case we don't actually do a join, but rather a `where in` with a subquery. Using this technique we create a subquery that can be optimized with specific indexes for maximum speed and then use those results to fetch the full rows.

The SQL looks something like this:

```sql
select * from contacts              -- The full data that you want to show your users.
    where contacts.id in (          -- The "deferred join" or subquery, in our case.
        select id from contacts     -- The pagination, accessing as little data as possible - ID only.
        limit 15 offset 150000      
    ) as tmp using(id)
```

The benefits can vary based on your dataset, but this method allows the database to examine as little data as possible satisfy the user's intent.

It's unlikely that this method will ever perform worse than traditional `offset` / `limit`, although it is possible, so be
sure to test on your data!

> If you want to read 3,000 words on the theory of this package, you can head over to [aaronfrancis.com/2022/efficient-pagination-using-deferred-joins](https://aaronfrancis.com/2022/efficient-pagination-using-deferred-joins).

## Installation

This package supports Laravel 8 and 9. (Laravel 8 must be 8.37 or higher.)

To install, require the package via composer:

```
composer require hammerstone/fast-paginate
```

There is nothing further you need to do. The service provider will be loaded automatically by Laravel.

## Usage

Anywhere you would use `Model::query()->paginate()`, you can use `Model::query()->fastPaginate()`! That's it! The method signature is the same.

Relationships are supported as well: 

```php
User::first()->posts()->fastPaginate();
```

## A Favor

If this helps you, please [tweet at me](https://twitter.com/aarondfrancis) with before and after times! I'd love to know :D 