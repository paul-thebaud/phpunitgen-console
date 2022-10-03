<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Adapters\Laravel;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpUnitGen\Console\Commands\HasOutput;
use PhpUnitGen\Console\Contracts\Config\ConfigResolver;
use PhpUnitGen\Console\Contracts\Execution\Runner;
use PhpUnitGen\Console\Contracts\Files\Filesystem;
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
    use HasOutput;

    /**
     * @var Application|mixed The Laravel or Lumen app instance, used to resolve app base path.
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
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * CommandFinishedListener constructor.
     *
     * @param Application|mixed $application
     * @param ConfigResolver    $configResolver
     * @param PhpUnitGenCommand $phpUnitGenCommand
     * @param Runner            $runner
     * @param Filesystem        $filesystem
     */
    public function __construct(
        $application,
        ConfigResolver $configResolver,
        PhpUnitGenCommand $phpUnitGenCommand,
        Runner $runner,
        Filesystem $filesystem
    ) {
        $this->application = $application;
        $this->configResolver = $configResolver;
        $this->phpUnitGenCommand = $phpUnitGenCommand;
        $this->runner = $runner;
        $this->filesystem = $filesystem;
    }

    /**
     * Handle the command finished event, and try to generate associated tests
     * if possible and enabled. Returns count of managed sources.
     *
     * @param CommandFinished $event
     *
     * @return int
     */
    public function handle(CommandFinished $event): int
    {
        $this->output = $event->output;

        if (! $this->shouldHandleEvent($event)) {
            return 0;
        }

        $config = $this->configResolver->resolve();
        if (! $config->generateOnMake()) {
            return 0;
        }

        $sources = $this->getSources($event->command, $event->input);

        if ($sources->isEmpty()) {
            return 0;
        }

        $sources->each(function (string $relativeSource) {
            $returnCode = $this->runner->run(
                $this->createRunnerInput($relativeSource),
                $this->createRunnerOutput()
            );

            $this->writeRunnerResult($relativeSource, $returnCode);
        });

        return $sources->count();
    }

    /*
     |--------------------------------------------------------------------------
     | Runner execution.
     |--------------------------------------------------------------------------
     */

    /**
     * Create the runner input.
     *
     * @param string $relativeSource
     *
     * @return InputInterface
     */
    protected function createRunnerInput(string $relativeSource): InputInterface
    {
        return new ArrayInput(
            ['source' => $this->getAbsoluteSource($relativeSource)],
            $this->phpUnitGenCommand->getDefinition()
        );
    }

    /**
     * Create the runner output.
     *
     * @return OutputInterface
     */
    protected function createRunnerOutput(): OutputInterface
    {
        return $this->output->isVeryVerbose() ? $this->output : new NullOutput();
    }

    /**
     * Write the runner result to output.
     *
     * @param string $relativeSource
     * @param int    $returnCode
     */
    protected function writeRunnerResult(string $relativeSource, int $returnCode): void
    {
        if ($returnCode === 0) {
            $this->success("Test generated for \"{$relativeSource}\".");

            return;
        }

        $this->error("Test generation failed for \"{$relativeSource}\".");
    }

    /*
     |--------------------------------------------------------------------------
     | Event handling checks.
     |--------------------------------------------------------------------------
     */

    /**
     * Check if command event should be handled by PhpUnitGen.
     *
     * @param CommandFinished $event
     *
     * @return bool
     */
    protected function shouldHandleEvent(CommandFinished $event): bool
    {
        return Str::startsWith($event->command, 'make:')
            && $event->exitCode === 0
            && ! $event->input->getOption('help')
            && ! $event->input->getOption('version');
    }

    /*
     |--------------------------------------------------------------------------
     | Sources resolving.
     |--------------------------------------------------------------------------
     */

    /**
     * Get the absolute path of a source.
     *
     * @param string $relativeSource
     *
     * @return string
     */
    protected function getAbsoluteSource(string $relativeSource): string
    {
        return $this->application->basePath(
            'app/'.$relativeSource.'.php'
        );
    }

    /**
     * Get the relative sources list for the given command and input.
     *
     * @param string         $command
     * @param InputInterface $input
     *
     * @return Collection
     */
    protected function getSources(string $command, InputInterface $input): Collection
    {
        $sources = new Collection();
        $qualifiedName = $this->getQualifiedName($input);
        $objectName = Str::replaceFirst('make:', '', $command);

        if ($objectName === 'model') {
            $this->addSourcesForModel($sources, $qualifiedName, $input);

            return $sources->unique();
        }

        $possibleSources = $this->getSourcesNames();
        if (isset($possibleSources[$objectName])) {
            return $sources->add(
                $possibleSources[$objectName].$qualifiedName
            );
        }

        return $sources;
    }

    /*
     |--------------------------------------------------------------------------
     | Sources' names resolving input name.
     |--------------------------------------------------------------------------
     */

    /**
     * Qualify the name to get the full path from project root.
     *
     * @param InputInterface $input
     *
     * @return string
     */
    protected function getQualifiedName(InputInterface $input): string
    {
        return str_replace('\\', '/', ltrim(trim($input->getArgument('name')), '\\/'));
    }

    /*
     |--------------------------------------------------------------------------
     | Sources collection resolving from input.
     |--------------------------------------------------------------------------
     */

    /**
     * Add sources for the "make:model" command.
     *
     * @param Collection     $sources
     * @param string         $name
     * @param InputInterface $input
     *
     * @see CommandFinishedListener::getSources()
     */
    protected function addSourcesForModel(Collection $sources, string $name, InputInterface $input)
    {
        if (! $this->filesystem->has($this->getAbsoluteSource($name))) {
            $name = 'Models/'.$name;
        }

        $sources->add($name);

        if ($input->getOption('controller')) {
            $sources->add('Http/Controllers/'.$name.'Controller');
        }
    }

    /*
     |--------------------------------------------------------------------------
     | Sources' names resolving input name.
     |--------------------------------------------------------------------------
     */

    /**
     * Get the mapping between class type and source name.
     *
     * @return array
     */
    protected function getSourcesNames(): array
    {
        return [
            'channel'      => 'Broadcasting/',
            'command'      => 'Console/Commands/',
            'controller'   => 'Http/Controllers/',
            'event'        => 'Events/',
            'exception'    => 'Exceptions/',
            'job'          => 'Jobs/',
            'listener'     => 'Listeners/',
            'mail'         => 'Mail/',
            'middleware'   => 'Http/Middleware/',
            'notification' => 'Notifications/',
            'observer'     => 'Observers/',
            'policy'       => 'Policies/',
            'provider'     => 'Providers/',
            'request'      => 'Http/Requests/',
            'resource'     => 'Http/Resources/',
            'rule'         => 'Rules/',
        ];
    }
}
