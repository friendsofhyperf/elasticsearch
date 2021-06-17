<?php

declare(strict_types=1);
/**
 * This file is part of elasticsearch.
 *
 * @link     https://github.com/friendsofhyperf/elasticsearch
 * @document https://github.com/friendsofhyperf/elasticsearch/blob/main/README.md
 * @contact  huangdijia@gmail.com
 */
namespace FriendsOfHyperf\Elasticsearch;

use Hyperf\Contract\ConfigInterface;

class ClientProxy extends Client
{
    /**
     * @var string
     */
    protected $poolName;

    public function __construct(ConfigInterface $config, string $pool)
    {
        $this->poolName = $pool;
        parent::__construct($config);
    }
}
