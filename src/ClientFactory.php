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
     * @var ClientProxy[]
     */
    protected $proxies;

    public function __construct(ConfigInterface $config)
    {
        foreach ($config->get('elasticsearch') as $poolName => $configure) {
            $this->proxies[$poolName] = make(ClientProxy::class, ['pool' => $poolName]);
        }
    }

    /**
     * @return ClientProxy
     */
    public function get(string $poolName)
    {
        $proxy = $this->proxies[$poolName] ?? null;
        if (! $proxy instanceof ClientProxy) {
            throw new InvalidClientProxyException('Invalid Client proxy.');
        }

        return $proxy;
    }
}
