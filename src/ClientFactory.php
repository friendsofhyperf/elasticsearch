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

use FriendsOfHyperf\Elasticsearch\Exception\InvalidClientProxyException;
use Hyperf\Contract\ConfigInterface;

class ClientFactory
{
    /**
     * @return ClientProxy
     */
    public function get(string $poolName)
    {
        return make(ClientProxy::class, ['pool' => $poolName]);
    }
}
