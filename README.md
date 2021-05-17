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

- Create a Model

```php
<?php
namespace App\Elasticsearch;

use FriendsOfHyperf\Elasticsearch\Model;

class Test extends Model
{
    protected $index = 'test';
}
```

- Query

```php
use App\Elasticsearch\Test;

Test::query()->where(...)->search();
```

- UpdateByQuery

```php
use App\Elasticsearch\Test;

Test::query()->where(...)->script(['source' => 'ctx.source.xxx = value'])->updateByQuery();
```
