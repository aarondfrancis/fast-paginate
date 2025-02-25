# Fast Paginate for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aaronfrancis/fast-paginate)](https://packagist.org/packages/aaronfrancis/fast-paginate)
[![Total Downloads](https://img.shields.io/packagist/dt/aaronfrancis/fast-paginate)](https://packagist.org/packages/aaronfrancis/fast-paginate)
[![License](https://img.shields.io/packagist/l/aaronfrancis/fast-paginate)](https://packagist.org/packages/aaronfrancis/fast-paginate)

## About

This is a fast `limit`/`offset` pagination macro for Laravel. It can be used in place of the standard `paginate` methods. 

This package uses a SQL method similar to a "deferred join" to achieve this speedup. A deferred join is a technique that defers access to requested columns until _after_ the `offset` and `limit` have been applied.

In our case we don't actually do a join, but rather a `where in` with a subquery. Using this technique we create a subquery that can be optimized with specific indexes for maximum speed and then use those results to fetch the full rows.

The SQL looks something like this:

```sql
select * from contacts              -- The full data that you want to show your users.
    where contacts.id in (          -- The "deferred join" or subquery, in our case.
        select id from contacts     -- The pagination, accessing as little data as possible - ID only.
        limit 15 offset 150000      
    )
```

> You might get an error trying to run the query above! Something like `This version of MySQL doesn't yet support 'LIMIT & IN/ALL/ANY/SOME subquery.`
> In this package, we run them as [two _separate_ queries](https://github.com/aarondfrancis/fast-paginate/blob/154da286f8160a9e75e64e8025b0da682aa2ba23/src/BuilderMixin.php#L62-L79) to get around that!  

The benefits can vary based on your dataset, but this method allows the database to examine as little data as possible to satisfy the user's intent.

It's unlikely that this method will ever perform worse than traditional `offset` / `limit`, although it is possible, so be
sure to test on your data!

> If you want to read 3,000 words on the theory of this package, you can head over to [aaronfrancis.com/2022/efficient-pagination-using-deferred-joins](https://aaronfrancis.com/2022/efficient-pagination-using-deferred-joins).

## Installation

This package supports Laravel 8, 9, 10, and 11. (Laravel 8 must be 8.37 or higher.)

To install, require the package via composer:

```
composer require aaronfrancis/fast-paginate
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

Some community results so far: 
* [30 seconds --> 250ms](https://twitter.com/mdavis1982/status/1482429071288066054) 
* [28 seconds --> 2 seconds](https://twitter.com/joecampo/status/1483550610028957701) 
* [7.5x faster](https://twitter.com/max_eckel/status/1483764319372333057) 
* [1.1 seconds --> 0.1 seconds](https://twitter.com/max_eckel/status/1483852300414337032) 
* [20 seconds --> 2 seconds](https://twitter.com/1ralphmorris/status/1484242437618941957) 
* [2 seconds --> .2 seconds](https://twitter.com/julioelpoeta/status/1549524738980077568) 

## License

The MIT License (MIT).

## Support

This is free! If you want to support me:

- Sponsor my open source work: [aaronfrancis.com/backstage](https://aaronfrancis.com/backstage)
- Check out my courses:
    - [Mastering Postgres](https://masteringpostgres.com)
    - [High Performance SQLite](https://highperformancesqlite.com)
    - [Screencasting](https://screencasting.com)
- Help spread the word about things I make

## Credits

Solo was developed by Aaron Francis. If you like it, please let me know!

- Twitter: https://twitter.com/aarondfrancis
- Website: https://aaronfrancis.com
- YouTube: https://youtube.com/@aarondfrancis
- GitHub: https://github.com/aarondfrancis/solo
