<?php

declare(strict_types=1);
/**
 * This file is part of elasticsearch.
 *
 * @link     https://github.com/friendsofhyperf/elasticsearch
 * @document https://github.com/friendsofhyperf/elasticsearch/blob/main/README.md
 * @contact  hdj@addcn.com
 */
namespace FriendsOfHyperf\Elasticsearch\Model\Traits;

trait Migrate
{
    public function getPool(): string
    {
        return $this->pool;
    }

    public function getIndex(): string
    {
        return $this->index;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSettings(): array
    {
        return $this->settings ?? [];
    }

    public function getProperties(): array
    {
        return $this->properties ?? [];
    }
}
