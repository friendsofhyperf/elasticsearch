<?php

declare(strict_types=1);
/**
 * This file is part of elasticsearch.
 *
 * @link     https://github.com/friendsofhyperf/elasticsearch
 * @document https://github.com/friendsofhyperf/elasticsearch/blob/main/README.md
 * @contact  huangdijia@gmail.com
 */
namespace FriendsOfHyperf\Elasticsearch\Command;

use Closure;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Exception;
use FriendsOfHyperf\Elasticsearch\ClientFactory;
use FriendsOfHyperf\Elasticsearch\ClientProxy;
use FriendsOfHyperf\Elasticsearch\Index\AbstractIndex;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * @Command
 */
class MigrateCommand extends HyperfCommand
{
    protected ?string $signature = 'elasticsearch:migrate
        {index : Index name}
        {--update : Update a existed index}
        {--recreate : Re-create index}
        {--migrate : Re-migrate data}
    ';

    /**
     * @var ClientProxy
     */
    protected $client;

    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct();
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Build index.');
    }

    public function handle()
    {
        $indexClass = $this->input->getArgument('index');

        if (! class_exists($indexClass)) {
            $this->output->error($indexClass . ' not exists!');
            return;
        }

        $instance = make($indexClass);

        if (! ($instance instanceof AbstractIndex)) {
            $this->output->error($indexClass . ' must be instanceof ' . AbstractIndex::class);
            return;
        }

        /** @var AbstractIndex $instance */
        $pool = $instance->getPool();
        $index = $instance->getIndex();
        $type = $instance->getType();
        $settings = $instance->getSettings();
        $properties = $instance->getProperties();
        $migration = $this->input->getOption('migrate') ? $instance->getMigration() : null;

        if (! is_array($settings)) {
            $this->output->error(sprintf('Property %s of %s must be array, s% given.', 'settings', $indexClass, gettype($settings)));
            return;
        }

        if (! is_array($properties)) {
            $this->output->error(sprintf('Property %s of %s must be array, s% given.', 'properties', $indexClass, gettype($properties)));
            return;
        }

        $this->client = $this->container->get(ClientFactory::class)->get($pool);

        if ($this->input->getOption('recreate')) {
            $this->recreate($index, $migration, $type, $settings, $properties);
            return;
        }

        if ($this->input->getOption('update')) {
            $this->update($index, $migration, $type, $settings, $properties);
            return;
        }

        $this->create($index, $type, $settings, $properties, $migration);
    }

    protected function create(string $index, string $type, array $settings, array $properties, ?Closure $migration)
    {
        if ($this->client->indices()->exists(['index' => $index])) {
            $this->output->warning('Index [' . $index . '] exists.');
            return;
        }

        try {
            $new = $index . '_0';

            $this->client->indices()->create([
                'index' => $new,
                'body' => [
                    'settings' => $settings,
                    'mappings' => [$type => ['properties' => $properties]],
                    'aliases' => [$index => new \stdClass()],
                ],
            ]);

            $this->output->info('Index [' . $new . '] created.');

            if ($migration) {
                $this->output->info('Data loading.');
                $migration($new, $this->client);
                $this->output->info('Data loaded.');
            }
        } catch (Throwable $e) {
            $this->output->error($e->getMessage());
        }
    }

    protected function recreate(string $index, ?Closure $migration, string $type = '_doc', array $settings = [], array $properties = [])
    {
        try {
            if ($this->client->indices()->exists(['index' => $index])) {
                $info = $this->client->indices()->getAlias(['index' => $index]);
                $old = array_keys($info)[0] ?? null;
            }

            $new = $this->generateNewIndexName($index);

            $this->client->indices()->create([
                'index' => $new,
                'body' => [
                    'settings' => $settings,
                    'mappings' => [$type => ['properties' => $properties]],
                ],
            ]);
            $this->output->info('Index [' . $new . '] created.');

            if ($migration) {
                $this->output->info('Data loading.');
                $migration($new, $this->client);
                $this->output->info('Data loaded.');
            }

            $this->client->indices()->putAlias(['index' => $new, 'name' => $index]);
            $this->output->info('Index [' . $new . '] alias to [' . $index . '].');

            if (isset($old)) {
                $this->client->indices()->delete(['index' => $old]);
                $this->output->warning('Index [' . $old . '] deleted.');
            }
        } catch (Throwable $e) {
            $this->output->error($e->getMessage());
        }
    }

    /**
     * @throws NoNodesAvailableException
     * @throws Exception
     */
    protected function update(string $index, ?Closure $migration, string $type = '_doc', array $settings = [], array $properties = [])
    {
        if (! $this->client->indices()->exists(['index' => $index])) {
            $this->output->warning('Index [' . $index . '] not exists.');
            return;
        }

        try {
            $this->client->indices()->close(['index' => $index]);
            $this->output->warning('Index [' . $index . '] closed.');

            $this->client->indices()->putSettings([
                'index' => $index,
                'body' => $settings,
            ]);
            $this->output->info('Index [' . $index . '] settings updated.');

            $this->client->indices()->putMapping([
                'index' => $index,
                'type' => $type,
                'body' => [$type => ['properties' => $properties]],
            ]);
            $this->output->info('Index [' . $index . '] mappings updated.');
        } catch (Throwable $e) {
            $this->output->error($e->getMessage());
        } finally {
            $this->client->indices()->open(['index' => $index]);
            $this->output->info('Index [' . $index . '] opened.');
        }
    }

    /**
     * @throws NoNodesAvailableException
     * @throws Exception
     * @return string
     */
    protected function generateNewIndexName(string $index)
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
