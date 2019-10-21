<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Adapters\Laravel;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
        if (! $this->shouldHandleEvent($event)) {
            return;
        }

        $config = $this->configResolver->resolve();
        if (! $config->generateOnMake()) {
            return;
        }

        $sources = $this->getSources($event->command, $event->input);

        if ($sources->isEmpty()) {
            return;
        }

        $sources->each(function (string $relativeSource) use ($event) {
            $returnCode = $this->runner->run(
                $this->createRunnerInput($relativeSource),
                $this->createRunnerOutput($event->output)
            );

            $this->writeRunnerResult($event->output, $relativeSource, $returnCode);
        });
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
     * @param OutputInterface $eventOutput
     *
     * @return OutputInterface
     */
    protected function createRunnerOutput(OutputInterface $eventOutput): OutputInterface
    {
        return $eventOutput->isVeryVerbose() ? $eventOutput : new NullOutput();
    }

    /**
     * Write the runner result to output.
     *
     * @param OutputInterface $eventOutput
     * @param string          $relativeSource
     * @param int             $returnCode
     */
    protected function writeRunnerResult(OutputInterface $eventOutput, string $relativeSource, int $returnCode): void
    {
        if ($returnCode === 0) {
            $this->success($eventOutput, "Test generated for \"{$relativeSource}\".");

            return;
        }

        $this->error($eventOutput, "Test generation failed for \"{$relativeSource}\".");
    }

    /*
     |--------------------------------------------------------------------------
     | Output.
     |--------------------------------------------------------------------------
     */

    /**
     * Write a success message.
     *
     * @param OutputInterface $output
     * @param string          $message
     */
    protected function success(OutputInterface $output, string $message): void
    {
        $this->writeln($output, $message, 'green');
    }

    /**
     * Write an error message.
     *
     * @param OutputInterface $output
     * @param string          $message
     */
    protected function error(OutputInterface $output, string $message): void
    {
        $this->writeln($output, $message, 'red');
    }

    /**
     * Write a message with the given foreground on output.
     *
     * @param OutputInterface $output
     * @param string          $message
     * @param string          $foreground
     */
    protected function writeln(OutputInterface $output, string $message, string $foreground): void
    {
        if ($output->isQuiet()) {
            return;
        }

        $output->writeln("<fg={$foreground}>{$message}</>");
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
            && $event->input !== null
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
        $objectName = Str::ucfirst(Str::replaceFirst('make:', '', $command));
        $addSourceMethod = 'addSourcesFor'.$objectName;

        if (method_exists($this, $addSourceMethod)) {
            call_user_func_array(
                [$this, $addSourceMethod],
                [$sources, $this->getQualifiedName($input), $input]
            );

            return $sources->unique();
        }

        $getSourceNameMethod = "get{$objectName}SourceName";
        if (method_exists($this, $getSourceNameMethod)) {
            return $sources->add(
                call_user_func([$this, $getSourceNameMethod], $qualifiedName)
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
        $sources->add($this->getModelSourceName($name));

        if ($input->getOption('controller')) {
            $sources->add($this->getControllerSourceName($name.'Controller'));
        }
    }

    /*
     |--------------------------------------------------------------------------
     | Sources' names resolving input name.
     |--------------------------------------------------------------------------
     */

    /**
     * Get the channel name.
     *
     * @param string $name
     *
     * @return string
     *
     * @see CommandFinishedListener::getSources()
     */
    protected function getChannelSourceName(string $name): string
    {
        return 'Broadcasting/'.$name;
    }

    /**
     * Get the command name.
     *
     * @param string $name
     *
     * @return string
     *
     * @see CommandFinishedListener::getSources()
     */
    protected function getCommandSourceName(string $name): string
    {
        return 'Console/Commands/'.$name;
    }

    /**
     * Get the controller name.
     *
     * @param string $name
     *
     * @return string
     *
     * @see CommandFinishedListener::getSources()
     */
    protected function getControllerSourceName(string $name): string
    {
        return "Http/Controllers/{$name}";
    }

    /**
     * Get the event name.
     *
     * @param string $name
     *
     * @return string
     *
     * @see CommandFinishedListener::getSources()
     */
    protected function getEventSourceName(string $name): string
    {
        return 'Events/'.$name;
    }

    /**
     * Get the exception name.
     *
     * @param string $name
     *
     * @return string
     *
     * @see CommandFinishedListener::getSources()
     */
    protected function getExceptionSourceName(string $name): string
    {
        return 'Exceptions/'.$name;
    }

    /**
     * Get the job name.
     *
     * @param string $name
     *
     * @return string
     *
     * @see CommandFinishedListener::getSources()
     */
    protected function getJobSourceName(string $name): string
    {
        return 'Jobs/'.$name;
    }

    /**
     * Get the listener name.
     *
     * @param string $name
     *
     * @return string
     *
     * @see CommandFinishedListener::getSources()
     */
    protected function getListenerSourceName(string $name): string
    {
        return 'Listeners/'.$name;
    }

    /**
     * Get the mail name.
     *
     * @param string $name
     *
     * @return string
     *
     * @see CommandFinishedListener::getSources()
     */
    protected function getMailSourceName(string $name): string
    {
        return 'Mail/'.$name;
    }

    /**
     * Get the middleware name.
     *
     * @param string $name
     *
     * @return string
     *
     * @see CommandFinishedListener::getSources()
     */
    protected function getMiddlewareSourceName(string $name): string
    {
        return 'Http/Middleware/'.$name;
    }

    /**
     * Get the model name.
     *
     * @param string $name
     *
     * @return string
     */
    protected function getModelSourceName(string $name): string
    {
        return $name;
    }

    /**
     * Get the notification name.
     *
     * @param string $name
     *
     * @return string
     *
     * @see CommandFinishedListener::getSources()
     */
    protected function getNotificationSourceName(string $name): string
    {
        return 'Notifications/'.$name;
    }

    /**
     * Get the observer name.
     *
     * @param string $name
     *
     * @return string
     *
     * @see CommandFinishedListener::getSources()
     */
    protected function getObserverSourceName(string $name): string
    {
        return 'Observers/'.$name;
    }

    /**
     * Get the policy name.
     *
     * @param string $name
     *
     * @return string
     *
     * @see CommandFinishedListener::getSources()
     */
    protected function getPolicySourceName(string $name): string
    {
        return 'Policies/'.$name;
    }

    /**
     * Get the provider name.
     *
     * @param string $name
     *
     * @return string
     *
     * @see CommandFinishedListener::getSources()
     */
    protected function getProviderSourceName(string $name): string
    {
        return 'Providers/'.$name;
    }

    /**
     * Get the request name.
     *
     * @param string $name
     *
     * @return string
     *
     * @see CommandFinishedListener::getSources()
     */
    protected function getRequestSourceName(string $name): string
    {
        return 'Http/Requests/'.$name;
    }

    /**
     * Get the resource name.
     *
     * @param string $name
     *
     * @return string
     *
     * @see CommandFinishedListener::getSources()
     */
    protected function getResourceSourceName(string $name): string
    {
        return 'Http/Resources/'.$name;
    }

    /**
     * Get the rule name.
     *
     * @param string $name
     *
     * @return string
     *
     * @see CommandFinishedListener::getSources()
     */
    protected function getRuleSourceName(string $name): string
    {
        return 'Rules/'.$name;
    }
}
