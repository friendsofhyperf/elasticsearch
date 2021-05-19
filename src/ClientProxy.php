<?php

declare(strict_types=1);
/**
 * This file is part of elasticsearch.
 *
 * @link     https://github.com/friendsofhyperf/elasticsearch
 * @document https://github.com/friendsofhyperf/elasticsearch/blob/main/README.md
 * @contact  hdj@addcn.com
 */
namespace FriendsOfHyperf\Elasticsearch;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Elasticsearch\ClientBuilderFactory;

class ClientProxy extends Client
{
    /**
     * @var string
     */
    protected $poolName;

    public function __construct(ClientBuilderFactory $factory, ConfigInterface $config, string $pool)
    {
        $this->poolName = $pool;
        parent::__construct($factory, $config);
    }
}
