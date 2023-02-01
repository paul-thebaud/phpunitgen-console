<?php

namespace PhpUnitGen\Console\Reporters;

use PhpUnitGen\Console\Commands\HasOutput;
use PhpUnitGen\Console\Contracts\Config\ConsoleConfig;
use PhpUnitGen\Console\Contracts\Reporters\Reporter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Symfony\Component\VarDumper\VarDumper;
use Throwable;
use Tightenco\Collect\Support\Collection;

/**
 * Class AbstractReporter.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
abstract class AbstractReporter implements Reporter
{
    use HasOutput;

    /**
     * @var string The name of the StopWatch process.
     */
    private const STOPWATCH_PROCESS_NAME = 'phpunitgen';

    /**
     * @var Stopwatch The currently recording StopWatch instance.
     */
    private Stopwatch $stopwatch;

    /**
     * @var Collection<int, string> The identified sources.
     */
    protected Collection $sources;

    /**
     * @var Collection<string, string> The list of successful generation (map between source and target).
     */
    protected Collection $successes;

    /**
     * @var Collection<string, string> The list of warnings which occurred (map between source and warning message).
     */
    protected Collection $warnings;

    /**
     * @var Collection<string, Throwable> The list of exceptions which occurred (map between source and exception).
     */
    protected Collection $errors;

    /**
     * AbstractReporter constructor.
     *
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->withStopwatch(new Stopwatch());
        $this->output = $output;
        $this->sources = new Collection();
        $this->successes = new Collection();
        $this->warnings = new Collection();
        $this->errors = new Collection();
    }

    /**
     * Start stopwatch using given instance.
     *
     * @param Stopwatch $stopwatch
     *
     * @return void
     */
    public function withStopwatch(Stopwatch $stopwatch): void
    {
        $this->stopwatch = $stopwatch;
        $this->stopwatch->start(self::STOPWATCH_PROCESS_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function onStart(ConsoleConfig $config, Collection $sources): void
    {
        $this->sources = $sources;
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess(string $absoluteSourcePath, string $absoluteTargetPath): void
    {
        $this->successes->put($absoluteSourcePath, $absoluteTargetPath);
    }

    /**
     * {@inheritdoc}
     */
    public function onWarning(string $absoluteSourcePath, string $warningMessage): void
    {
        $this->warnings->put($absoluteSourcePath, $warningMessage);
    }

    /**
     * {@inheritdoc}
     */
    public function onError(string $absoluteSourcePath, Throwable $exception): void
    {
        $this->errors->put($absoluteSourcePath, $exception);
    }

    /**
     * {@inheritdoc}
     */
    public function onCriticalError(Throwable $exception): int
    {
        $this->writeln()->error('Critical error during execution:');

        VarDumper::dump($exception);

        return 1;
    }

    /**
     * Terminate the reporting and retrieve StopWatch stop event.
     *
     * @return StopwatchEvent
     */
    protected function terminateReport(): StopwatchEvent
    {
        return $this->stopwatch->stop(self::STOPWATCH_PROCESS_NAME);
    }

    /**
     * Determine the correct exit status code.
     *
     * @return int
     */
    protected function determineExitCode(): int
    {
        if ($this->errors->isNotEmpty()) {
            return 100;
        }

        if ($this->warnings->isNotEmpty()) {
            return 101;
        }

        return 0;
    }

    /**
     * @return Collection<int, string>
     */
    public function getSources(): Collection
    {
        return $this->sources;
    }

    /**
     * @return Collection<string, string>
     */
    public function getSuccesses(): Collection
    {
        return $this->successes;
    }

    /**
     * @return Collection<string, string>
     */
    public function getWarnings(): Collection
    {
        return $this->warnings;
    }

    /**
     * @return Collection<string, Throwable>
     */
    public function getErrors(): Collection
    {
        return $this->errors;
    }
}
