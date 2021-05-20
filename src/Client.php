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
use Elasticsearch\ClientBuilder;
use FriendsOfHyperf\Elasticsearch\Exception\MissingConfigException;
use FriendsOfHyperf\Elasticsearch\Query\Builder;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Guzzle\RingPHP\CoroutineHandler;
use Hyperf\Guzzle\RingPHP\PoolHandler;
use Hyperf\Utils\Coroutine;

/**
 * @mixin \Elasticsearch\Client
 */
class Client
{
    /**
     * @var string
     */
    protected $poolName = 'default';

    /**
     * @var \Elasticsearch\Client
     */
    protected $client;

    public function __construct(ConfigInterface $config)
    {
        if (! $config->has($configKey = 'elasticsearch.' . $this->poolName)) {
            throw new MissingConfigException('Config item ' . $configKey . ' is missing.');
        }

        /** @var array */
        $poolConfig = $config->get($configKey);
        $hosts = data_get($poolConfig, 'hosts', []);
        $builder = ClientBuilder::create();

        if (Coroutine::inCoroutine()) {
            $maxConnections = (int) data_get($poolConfig, 'pool.max_connections');

            if ($maxConnections > 0) {
                $handler = make(PoolHandler::class, [
                    'option' => [
                        'max_connections' => (int) $maxConnections,
                    ],
                ]);
            } else {
                $handler = new CoroutineHandler();
            }

            $builder->setHandler($handler);
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
