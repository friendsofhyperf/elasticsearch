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

use Elasticsearch\Client;
use FriendsOfHyperf\Elasticsearch\Query\Builder;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Elasticsearch\ClientBuilderFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;

/**
 * @mixin \FriendsOfHyperf\Elasticsearch\Query\Builder
 * @mixin \Elasticsearch\Client
 */
class Model
{
    /**
     * @var string
     */
    protected $index;

    /**
     * @var string
     */
    protected $type = '_doc';

    /**
     * @var array
     */
    protected $hosts;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Builder
     */
    private $query;

    public function __call(string $name, array $arguments)
    {
        if (is_callable([$this->query, $name])) {
            $result = $this->query->{$name}(...$arguments);

            if (! ($result instanceof Builder)) {
                return $result;
            }

            return $this;
        }

        if (is_callable([$this->client, $name])) {
            if (isset($arguments[0]) && is_array($arguments[0])) {
                $params = $arguments[0];
            } else {
                $params = $this->query->compileSearch();
            }

            if ($name == 'count') {
                unset($params['_source']);
            }

            return $this->client->{$name}($params);
        }
    }

    public static function query(): self
    {
        return (new static())->newQuery();
    }

    public function newQuery(): self
    {
        /** @var ContainerInterface $container */
        $container = ApplicationContext::getContainer();
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        if (is_null($this->hosts)) {
            $this->hosts = $config->get('elasticsearch.hosts', []);
        }
        $this->client = $container->get(ClientBuilderFactory::class)
            ->create()
            ->setHosts($this->hosts)
            ->build();
        $this->query = new Builder($this->index);

        return $this;
    }
}
