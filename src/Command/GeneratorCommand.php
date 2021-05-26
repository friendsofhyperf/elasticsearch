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

use Hyperf\Command\Annotation\Command;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\CodeGen\Project;
use Hyperf\Utils\Str;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command as BasCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @Command
 */
class GeneratorCommand extends BasCommand
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct()
    {
        parent::__construct('gen:index');
        $this->setDescription('Create a new index class');
    }

    public function configure()
    {
        foreach ($this->getArguments() as $argument) {
            $this->addArgument(...$argument);
        }

        foreach ($this->getOptions() as $option) {
            $this->addOption(...$option);
        }
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $name = $this->qualifyClass($this->getNameInput());
        $path = $this->getPath($name);

        // First we will check to see if the class already exists. If it does, we don't want
        // to create the class and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        if (($input->getOption('force') === false) && $this->alreadyExists($this->getNameInput())) {
            $output->writeln(sprintf('<fg=red>%s</>', $name . ' already exists!'));
            return 0;
        }

        // Next, we will generate the path to the location where this class' file should get
        // written. Then, we will build the class and make the proper replacements on the
        // stub files so that it gets the correctly formatted namespace and class name.
        $this->makeDirectory($path);

        file_put_contents($path, $this->buildClass($name));

        $output->writeln(sprintf('<info>%s</info>', $name . ' created successfully.'));

        return 0;
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param string $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        $name = ltrim($name, '\\/');
        $name = str_replace('/', '\\', $name);
        $namespace = $this->input->getOption('namespace');

        if (empty($namespace)) {
            $namespace = $this->getDefaultNamespace();
        }

        return $namespace . '\\' . $name;
    }

    /**
     * Determine if the class already exists.
     *
     * @param string $rawName
     * @return bool
     */
    protected function alreadyExists($rawName)
    {
        return is_file($this->getPath($this->qualifyClass($rawName)));
    }

    /**
     * Get the destination class path.
     *
     * @param string $name
     * @return string
     */
    protected function getPath($name)
    {
        $project = new Project();
        return BASE_PATH . '/' . $project->path($name);
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param string $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        return $path;
    }

    /**
     * Build the class with the given name.
     *
     * @param string $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = file_get_contents($this->getStub());

        return $this->replaceNamespace($stub, $name)
            ->replaceIndex($stub)
            ->replacePool($stub)
            ->replaceClass($stub, $name);
    }

    protected function replaceIndex(&$stub)
    {
        $stub = str_replace(
            ['%INDEX%'],
            [$this->getIndexInput()],
            $stub
        );

        return $this;
    }

    /**
     * Replace the namespace for the given stub.
     *
     * @param string $stub
     * @param string $name
     * @return $this
     */
    protected function replaceNamespace(&$stub, $name)
    {
        $stub = str_replace(
            ['%NAMESPACE%'],
            [$this->getNamespace($name)],
            $stub
        );

        return $this;
    }

    /**
     * Get the full namespace for a given class, without the class name.
     *
     * @param string $name
     * @return string
     */
    protected function getNamespace($name)
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param string $stub
     * @param string $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $class = str_replace($this->getNamespace($name) . '\\', '', $name);

        return str_replace('%CLASS%', $class, $stub);
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        $index = $this->getIndexInput();

        if ($this->input->getOption('prefix')) {
            $index = Str::replaceFirst($this->input->getOption('prefix'), '', $index);
        }

        return Str::studly(Str::singular($index));
    }

    /**
     * @throws InvalidArgumentException
     * @return string
     */
    protected function getIndexInput()
    {
        return trim($this->input->getArgument('index'));
    }

    /**
     * @param string $stub
     * @param string $pool
     * @return $this
     */
    protected function replacePool(&$stub)
    {
        $stub = str_replace(
            ['%NAMESPACE%'],
            [$this->getPoolInput()],
            $stub
        );

        return $this;
    }

    /**
     * @throws InvalidArgumentException
     * @return string
     */
    protected function getPoolInput()
    {
        return trim($this->input->getArgument('pool'));
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['index', InputArgument::REQUIRED, 'Which index you want to associated with the Index.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Whether force to rewrite.'],
            ['namespace', 'N', InputOption::VALUE_OPTIONAL, 'The namespace for class.', null],
            ['pool', 'p', InputOption::VALUE_OPTIONAL, 'The pool for index.', 'default'],
            ['prefix', 'P', InputOption::VALUE_OPTIONAL, 'The prefix for index.', null],
        ];
    }

    /**
     * Get the custom config for generator.
     */
    protected function getConfig(): array
    {
        $key = 'devtool.generator.index';
        return $this->getContainer()->get(ConfigInterface::class)->get($key) ?? [];
    }

    protected function getContainer(): ContainerInterface
    {
        return ApplicationContext::getContainer();
    }

    protected function getStub(): string
    {
        return $this->getConfig()['stub'] ?? __DIR__ . '/stubs/index.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return $this->getConfig()['namespace'] ?? 'App\\Indices';
    }
}
