# Schema plugin for CakePHP 4.x

for usage in CakePHP 3.x see the [2.x branch](https://github.com/raul338/cakephp-schema/tree/2.x)

Save the schema into one file and use as an automatic schema for Fixtures. The schema is automatically saved when executing `cake migrations migrate`.
This also allows for local testing with test suite data for debugging.

## Supported datasources

- Postgres
- MySQL
- SQLite
- ~~SQL Server~~ not tested yet

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```
composer require raul338/cakephp-schema
```

Update your `Application` class:

```PHP
public function bootstrapCli()
{
    // ....
    $this->addOptionalPlugin('Schema');
}
```

## Usage

The plugin saves the schema of the `default` connection to the `config/schema.php` file. The structure is similar to the fixtures fields.

```
cake schema save
```

To load the schema back, useful for debug with test data. Run:

```
cake schema load
```

### Seed

The Schema plugin allows you to seed data from the `config/seed.php` file.
This also is useful to save unrelated tables (like tables from acl plugin, or `sphinxlog` table)
The `seed.php` file should return array of tables and rows:

```
<?php
    // You can work with custom libraries here or use the Cake's ORM
    return [
        'articles' => [
            [
                'id' => 1,
                'category_id' => 1,
                'label' => 'CakePHP'
            ],
            [
                'id' => 2,
                'label' => 'Schema plugin',
                'json_type_field' => [
                    'i' => 'will convert',
                    'to' => 'json'
                ]
            ]
        ],
        'categories' => [
            [
                'id' => 2,
                'label' => 'Frameworks'
            ]
        ]
    ];
```

The Seed commands support the CakePHP ORM's type mapping. So for example, if you're using the JsonType example from the cookbook, the seed commands will automatically convert an array to JSON.

You can use the `schema generateseed` command to automatically generate a seed.php file based on your database contents.

Use `schema seed` for importing the contents of the `seed.php` into your DB.

Seed commands will take the following options:

- `connection` Database connection to use.
- `seed` Path to the seed file to generate (Defaults to "config/seed.php")
- `path` Path to the schema.php file (Defaults to "config/schema.php")



### Other examples

    cake schema save --connection test
    cake schema save --path config/schema/schema.lock
    cake schema load --connection test --path config/schema/schema.lock --no-interaction

To only drop all tables in database

    cake schema drop
    cake schema drop --connection test

Seeding Examples

    cake schema seed --truncate
    cake schema generateseed --seed config/my_seed.php

In case you are using [Tree Behavior](https://book.cakephp.org/4/en/orm/behaviors/tree.html) in your table, you can recover
the tree from seed data: (it will recalculate `lft` & `rght` values)

    cake recover_tree categories

## Fixture generation

This plugins allows to use generated schema and seeds as fixture model and data, by using a `SchemaFixture`. You can extend your fixtures just like the book indicates.

Example usage:

    bin/cake bake fixture --theme Schema Users

```php
<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Schema\TestSuite\Fixture\SchemaFixture;

class UsersFixture extends SchemaFixture
{
    // This class reads schema from users key in config/schema.php
    // and reads records from users key in config/seed.php if exists
}
```

## TODO

- [x] Auto-creation of the schema.php file after `cake migrations migrate`
- [x] Data seeding
- [x] Tests
- [ ] More options and configuration
- [ ] Refactoring and cleaning the code
