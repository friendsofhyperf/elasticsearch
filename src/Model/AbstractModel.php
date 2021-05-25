<?php

declare(strict_types=1);
/**
 * This file is part of elasticsearch.
 *
 * @link     https://github.com/friendsofhyperf/elasticsearch
 * @document https://github.com/friendsofhyperf/elasticsearch/blob/main/README.md
 * @contact  hdj@addcn.com
 */
namespace FriendsOfHyperf\Elasticsearch\Model;

use FriendsOfHyperf\Elasticsearch\ClientFactory;
use FriendsOfHyperf\Elasticsearch\ClientProxy;
use FriendsOfHyperf\Elasticsearch\Query\Builder;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;

/**
 * @mixin \FriendsOfHyperf\Elasticsearch\Query\Builder
 * @mixin \Elasticsearch\Client
 */
abstract class AbstractModel
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
    protected $settings = [];

    /**
     * @var array
     */
    protected $mappings = [];

    /**
     * @var string
     */
    protected $pool = 'default';

    /**
     * @var ClientProxy
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

    public static function getIndex(): string
    {
        return (new static())->index;
    }

    public static function getType(): string
    {
        return (new static())->type;
    }

    public static function getSettings(): array
    {
        return (new static())->settings ?? [];
    }

    public static function getMappings(): array
    {
        return (new static())->mappings ?? [];
    }

    public function newQuery(): self
    {
        /** @var ContainerInterface $container */
        $container = ApplicationContext::getContainer();
        /** @var ClientFactory $clientFactory */
        $clientFactory = $container->get(ClientFactory::class);
        $this->client = $clientFactory->get($this->pool);
        $this->query = new Builder($this->index);

        return $this;
    }
}
