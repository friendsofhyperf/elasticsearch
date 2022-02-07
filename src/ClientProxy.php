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
    public function __construct(ConfigInterface $config, protected string $poolName)
    {
        parent::__construct($config);
    }
}
