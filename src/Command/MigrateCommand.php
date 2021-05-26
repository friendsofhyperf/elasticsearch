<?php

declare(strict_types=1);
/**
 * This file is part of elasticsearch.
 *
 * @link     https://github.com/friendsofhyperf/elasticsearch
 * @document https://github.com/friendsofhyperf/elasticsearch/blob/main/README.md
 * @contact  hdj@addcn.com
 */
namespace FriendsOfHyperf\Elasticsearch\Command;

use FriendsOfHyperf\Elasticsearch\ClientFactory;
use FriendsOfHyperf\Elasticsearch\ClientProxy;
use FriendsOfHyperf\Elasticsearch\Model\Contract\MigrateAble;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * @Command
 */
class MigrateCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    protected $signature = 'elasticsearch:migrate {model : Model} {--create : Create new index} {--update : Update a existed index} {--recreate : Create index}';

    /**
     * @var ClientProxy
     */
    protected $client;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct();
        $this->clientFactory = $container->get(ClientFactory::class);
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Build index by model.');
    }

    public function handle()
    {
        $modelClass = $this->input->getArgument('model');

        if (! class_exists($modelClass)) {
            $this->output->error($modelClass . ' not exists!');
            return;
        }

        /** @var MigrateAble $model */
        $model = new $modelClass();

        if (! ($model instanceof MigrateAble)) {
            $this->output->error($modelClass . ' must be implement by ' . MigrateAble::class);
            return;
        }

        $pool = $model->getPool();
        $index = $model->getIndex();
        $type = $model->getType();
        $settings = $model->getSettings();
        $properties = $model->getProperties();

        $this->client = $this->clientFactory->get($pool);

        if ($this->input->getOption('create')) {
            $this->create($index, $type, $settings, $properties);
            return;
        }

        if ($this->input->getOption('recreate')) {
            $this->recreate($index, $type, $settings, $properties);
            return;
        }

        if ($this->input->getOption('update')) {
            $this->update($index, $type, $settings, $properties);
            return;
        }

        $this->output->info('Done!');
    }

    protected function create(string $index, string $type, array $settings, array $properties)
    {
        if ($this->client->indices()->exists(['index' => $index])) {
            $this->output->warning($index . ' exists.');
            return;
        }

        try {
            $this->client->indices()->create([
                'index' => $index . '_0',
                'body' => [
                    'settings' => $settings,
                    'mappings' => [$type => $properties],
                    'aliases' => [$index => new \stdClass()],
                ],
            ]);
        } catch (Throwable $e) {
            $this->output->error($e->getMessage());
        }
    }

    protected function recreate(string $index, string $type = '_doc', array $settings = [], array $properties = [])
    {
        try {
            $this->client->indices()->close(['index' => $index]);

            $info = $this->client->indices()->getAlias(['index' => $index]);
            $old = array_keys($info)[0];
            $new = $this->getNewIndexName($index);

            $this->client->indices()->create([
                'index' => $new,
                'body' => [
                    'settings' => $settings,
                    'mappings' => [$type => $properties],
                ],
            ]);

            $this->client->indices()->putAlias(['index' => $new, 'name' => $index]);

            $this->client->indices()->delete(['index' => $old]);
        } catch (Throwable $e) {
            $this->output->error($e->getMessage());
        }
    }

    protected function update(string $index, string $type = '_doc', array $settings = [], array $properties = [])
    {
        if (! $this->client->indices()->exists(['index' => $index])) {
            $this->output->warning($index . ' not exists.');
            return;
        }

        try {
            $this->client->indices()->close(['index' => $index]);

            $this->client->indices()->putSettings([
                'index' => $index,
                'body' => $settings,
            ]);

            $this->client->indices()->putMapping([
                'index' => $index,
                'type' => $type,
                'body' => [$type => $properties],
            ]);
        } catch (Throwable $e) {
            $this->output->error($e->getMessage());
        } finally {
            $this->client->indices()->open(['index' => $index]);
        }
    }

    protected function getNewIndexName(string $index)
    {
        $i = 0;

        while (true) {
            if (! $this->client->indices()->exists(['index' => $index . '_' . $i])) {
                return $index . '_' . $i;
            }

            ++$i;
        }
    }
}
