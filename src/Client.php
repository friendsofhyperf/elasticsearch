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

use Closure;
use Elasticsearch\Client as ElasticsearchClient;
use Elasticsearch\ClientBuilder;
use FriendsOfHyperf\Elasticsearch\Query\Builder;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Elasticsearch\ClientBuilderFactory;
use Hyperf\Guzzle\RingPHP\PoolHandler;
use Hyperf\Utils\Coroutine;

class Client
{
    /**
     * @var string
     */
    protected $poolName = 'default';

    /**
     * @var ElasticsearchClient
     */
    protected $client;

    public function __construct(ClientBuilderFactory $factory, ConfigInterface $config)
    {
        /** @var array */
        $poolConfig = $config->get('elasticsearch.' . $this->poolName);
        $hosts = data_get($poolConfig, 'hosts', []);
        /** @var ClientBuilder $builder */
        $builder = $factory->create();

        if ($maxConnections = data_get($poolConfig, 'pool.max_connections') > 0) {
            if (Coroutine::inCoroutine()) {
                $handler = make(PoolHandler::class, [
                    'option' => [
                        'max_connections' => (int) $maxConnections,
                    ],
                ]);
                $builder->setHandler($handler);
            }
        }

        $this->client = $builder->setHosts($hosts)->build();
    }

    public function __call($name, $arguments)
    {
        if (isset($arguments[0])) {
            if ($arguments[0] instanceof Closure) {
                $arguments[0] = $arguments[0](new Builder());
            }

            if ($arguments[0] instanceof Builder) {
                $arguments[0] = $arguments[0]->compileSearch();
            }
        }

        return $this->client->{$name}(...$arguments);
    }
}
