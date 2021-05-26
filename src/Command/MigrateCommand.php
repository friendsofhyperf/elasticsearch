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

    protected $signature = 'elasticsearch:migrate {model : Model} {--update : Update a existed index} {--recreate : Create index}';

    /**
     * @var ClientProxy
     */
    protected $client;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct();
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

        $this->client = $this->container->get(ClientFactory::class)->get($pool);

        if ($this->input->getOption('recreate')) {
            $this->recreate($index, $type, $settings, $properties);
            return;
        }
        if ($this->input->getOption('update')) {
            $this->update($index, $type, $settings, $properties);
            return;
        }

        $this->create($index, $type, $settings, $properties);
    }

    protected function create(string $index, string $type, array $settings, array $properties)
    {
        if ($this->client->indices()->exists(['index' => $index])) {
            $this->output->warning('Index ' . $index . ' exists.');
            return;
        }

        try {
            $indexName = $index . '_0';

            $this->client->indices()->create([
                'index' => $indexName,
                'body' => [
                    'settings' => $settings,
                    'mappings' => [$type => ['properties' => $properties]],
                    'aliases' => [$index => new \stdClass()],
                ],
            ]);

            $this->output->info('Index ' . $indexName . ' created.');
        } catch (Throwable $e) {
            $this->output->error($e->getMessage());
        }
    }

    protected function recreate(string $index, string $type = '_doc', array $settings = [], array $properties = [])
    {
        try {
            // $this->client->indices()->close(['index' => $index]);
            // $this->output->warning('Index ' . $index . ' closed.');

            $info = $this->client->indices()->getAlias(['index' => $index]);
            $old = array_keys($info)[0];
            $new = $this->getNewIndexName($index);

            $this->client->indices()->create([
                'index' => $new,
                'body' => [
                    'settings' => $settings,
                    'mappings' => [$type => ['properties' => $properties]],
                ],
            ]);
            $this->output->info('Index ' . $new . ' created.');

            $this->client->indices()->putAlias(['index' => $new, 'name' => $index]);
            $this->output->info('Index ' . $index . ' alias to ' . $index . '.');

            $this->client->indices()->delete(['index' => $old]);
            $this->output->warning('Index ' . $old . ' deleted.');
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
            $this->output->warning('Index ' . $index . ' closed.');

            $this->client->indices()->putSettings([
                'index' => $index,
                'body' => $settings,
            ]);
            $this->output->info('Index ' . $index . ' settings updated.');

            $this->client->indices()->putMapping([
                'index' => $index,
                'type' => $type,
                'body' => [$type => ['properties' => $properties]],
            ]);
            $this->output->info('Index ' . $index . ' mappings updated.');
        } catch (Throwable $e) {
            $this->output->error($e->getMessage());
        } finally {
            $this->client->indices()->open(['index' => $index]);
            $this->output->info('Index ' . $index . ' opened.');
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
