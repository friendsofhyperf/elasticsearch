# elasticsearch

[![Latest Stable Version](https://poser.pugx.org/friendsofhyperf/elasticsearch/version.png)](https://packagist.org/packages/friendsofhyperf/elasticsearch)
[![Total Downloads](https://poser.pugx.org/friendsofhyperf/elasticsearch/d/total.png)](https://packagist.org/packages/friendsofhyperf/elasticsearch)
[![GitHub license](https://img.shields.io/github/license/friendsofhyperf/elasticsearch)](https://github.com/friendsofhyperf/elasticsearch)

A component for elasticsearch

## Installation

```bash
composer require friendsofhyperf/elasticsearch
```

## Publish configure

```bash
php bin/hyperf.php vendor:publish friendsofhyperf/elasticsearch
```

## Usage

### Index

- Create

```php
namespace App\Indices;

use FriendsOfHyperf\Elasticsearch\Index\AbstractIndex;

class Test extends AbstractIndex
{
    protected $index = 'test';
}
```

- Create by command

```bash
php bin/hyperf.php gen:index test
```

- Query

```php
use App\Indices\Test;

Test::query()->where(...)->search();
```

- UpdateByQuery

```php
use App\Indices\Test;

Test::query()->where(...)->script(['source' => 'ctx.source.xxx = value'])->updateByQuery();
```

- Count

```php
use App\Indices\Test;

Test::query()->where(...)->count();
```

## Migrate

- Index

```php
namespace App\Indices;

use FriendsOfHyperf\Elasticsearch\Index\AbstractIndex;

class Test extends AbstractIndex
{
    protected $index = 'test';
    protected $type = '_doc';
    protected $settings = [
        // your settings
    ];
    protected $properties = [
        // your properties
    ];

    public function getMigration(): Closure
    {
        return function ($index) {
            // migrate data
        };
    }
}
```

- Run migrate

```bash
php bin/hyperf.php elasticsearch:migrate "App\\Indices\\Test" [--migrate] [--update] [--recreate]
```

### ClientProxy

```php
namespace App\Proxy;

use FriendsOfHyperf\Elasticsearch\ClientProxy;

class FooClient extends ClientProxy
{
    protected $poolName = 'foo';
}
```
