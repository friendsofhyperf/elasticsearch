<?php

declare(strict_types=1);
/**
 * This file is part of elasticsearch.
 *
 * @link     https://github.com/friendsofhyperf/elasticsearch
 * @document https://github.com/friendsofhyperf/elasticsearch/blob/main/README.md
 * @contact  hdj@addcn.com
 */
namespace FriendsOfHyperf\Elasticsearch\Index\Traits;

use Closure;

trait Migrateable
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * @var array
     */
    protected $properties;

    public function getSettings()
    {
        return $this->settings;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function getMigration(): ?Closure
    {
        return null;
    }
}
