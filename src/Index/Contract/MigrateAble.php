<?php

declare(strict_types=1);
/**
 * This file is part of elasticsearch.
 *
 * @link     https://github.com/friendsofhyperf/elasticsearch
 * @document https://github.com/friendsofhyperf/elasticsearch/blob/main/README.md
 * @contact  hdj@addcn.com
 */
namespace FriendsOfHyperf\Elasticsearch\Index\Contract;

use Closure;

interface MigrateAble
{
    public function getPool(): string;

    public function getIndex(): string;

    public function getType(): string;

    public function getSettings(): array;

    public function getProperties(): array;

    public function getMigration(): ?Closure;
}
