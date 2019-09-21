<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Adapters\Laravel;

use Closure;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use PhpUnitGen\Console\Contracts\Config\ConfigResolver;
use PhpUnitGen\Console\Contracts\Execution\Runner;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CommandFinishedListener.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class CommandFinishedListener
{
    /**
     * @var Application
     */
    protected $application;

    /**
     * @var ConfigResolver
     */
    protected $configResolver;

    /**
     * @var PhpUnitGenCommand
     */
    protected $phpUnitGenCommand;

    /**
     * @var Runner
     */
    protected $runner;

    /**
     * CommandFinishedListener constructor.
     *
     * @param Application       $application
     * @param ConfigResolver    $configResolver
     * @param PhpUnitGenCommand $phpUnitGenCommand
     * @param Runner            $runner
     */
    public function __construct(
        Application $application,
        ConfigResolver $configResolver,
        PhpUnitGenCommand $phpUnitGenCommand,
        Runner $runner
    ) {
        $this->application = $application;
        $this->configResolver = $configResolver;
        $this->phpUnitGenCommand = $phpUnitGenCommand;
        $this->runner = $runner;
    }

    /**
     * Handle the command finished event, and try to generate associated tests
     * if possible and enabled.
     *
     * @param CommandFinished $event
     */
    public function handle(CommandFinished $event): void
    {
        if ($event->exitCode !== 0) {
            return;
        }

        $sourceCallback = $this->getArgumentsCallback($event->command);
        if (! $sourceCallback) {
            return;
        }

        $config = $this->configResolver->resolve();
        if (! $config->generateOnMake()) {
            return;
        }

        $this->writeln($event->output, 'Generating associated tests with PhpUnitGen.', 'yellow');

        $source = $sourceCallback($event->input);
        $result = $this->runner->run(
            new ArrayInput(compact('source'), $this->phpUnitGenCommand->getDefinition()),
            $event->output->isVeryVerbose() ? $event->output : new NullOutput()
        );

        if ($result === 0) {
            $this->writeln($event->output, 'Generated associated tests with PhpUnitGen.', 'green');
        } else {
            $this->writeln($event->output, 'Generation of associated tests with PhpUnitGen failed.', 'red');
        }
    }

    /**
     * Write a message with the given foreground on output.
     *
     * @param OutputInterface $output
     * @param string          $string
     * @param string          $foreground
     */
    protected function writeln(OutputInterface $output, string $string, string $foreground): void
    {
        if ($output->isQuiet()) {
            return;
        }

        $output->writeln("<fg={$foreground}>{$string}</>");
    }

    /**
     * Get the source/target path compilation callback for the given command.
     *
     * @param string $command
     *
     * @return Closure|null
     */
    protected function getArgumentsCallback(string $command): ?Closure
    {
        return $this->getArgumentsCallbacks()->get($command);
    }

    /**
     * Get the mapping between listened commands and their source/target path compilation.
     *
     * @return Collection
     */
    protected function getArgumentsCallbacks(): Collection
    {
        return new Collection([
            'make:model'      => $this->buildArgumentsCallback(),
            'make:controller' => $this->buildArgumentsCallback(),
            'make:policy'     => $this->buildArgumentsCallback('Policies'),
        ]);
    }

    /**
     * Create a callback to retrieve source and target from input.
     *
     * @param string $subDirectory
     *
     * @return Closure
     */
    protected function buildArgumentsCallback(string $subDirectory = ''): Closure
    {
        return function (InputInterface $input) use ($subDirectory) {
            if ($subDirectory !== '') {
                $subDirectory .= '/';
            }

            return $this->application->basePath(
                'app/'.$subDirectory.$input->getArgument('name').'.php'
            );
        };
    }
}
