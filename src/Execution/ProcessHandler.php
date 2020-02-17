<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Execution;

use PhpUnitGen\Console\Commands\HasOutput;
use PhpUnitGen\Console\Contracts\Config\ConsoleConfig;
use PhpUnitGen\Console\Contracts\Execution\ProcessHandler as ProcessHandlerContract;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Symfony\Component\VarDumper\VarDumper;
use Throwable;
use Tightenco\Collect\Support\Collection;

/**
 * Class ProcessHandler.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class ProcessHandler implements ProcessHandlerContract
{
    use HasOutput;

    /**
     * The name of the process on stopwatch.
     */
    protected const STOPWATCH_PROCESS = 'phpunitgen';

    /**
     * The number of displayed sources generation for one output line.
     */
    protected const SOURCES_PER_LINE = 50;

    /**
     * @var Stopwatch
     */
    protected $stopwatch;

    /**
     * @var Collection|string[] The identified sources.
     */
    protected $sources;

    /**
     * @var Collection|string[] The list of successful generation (map between source and target).
     */
    protected $successes;

    /**
     * @var Collection|string[] The list of warnings which occurred (map between source and warning).
     */
    protected $warnings;

    /**
     * @var Collection|Throwable[] The list of exceptions which occurred (map between source and exception).
     */
    protected $errors;

    /**
     * ProcessHandler constructor.
     *
     * @param Stopwatch $stopwatch
     */
    public function __construct(Stopwatch $stopwatch)
    {
        $this->stopwatch = $stopwatch;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(OutputInterface $output): void
    {
        $this->output = $output;

        $this->stopwatch->start(self::STOPWATCH_PROCESS);
    }

    /**
     * {@inheritdoc}
     */
    public function handleStart(ConsoleConfig $config, Collection $sources): void
    {
        $this->sources = $sources;
        $this->successes = new Collection();
        $this->warnings = new Collection();
        $this->errors = new Collection();

        $configPath = $config->getPath();
        $this->write('Starting process using ');
        if ($configPath) {
            $this->writeln("config file at '{$configPath}'.");
        } else {
            $this->writeln('default config.');
        }
        $this->writeln();
    }

    /**
     * {@inheritdoc}
     */
    public function handleSuccess(string $absoluteSourcePath, string $absoluteTargetPath): void
    {
        $this->successes->put($absoluteSourcePath, $absoluteTargetPath);

        $this->writeProgress('.');
    }

    /**
     * {@inheritdoc}
     */
    public function handleWarning(string $absoluteSourcePath, string $warningMessage): void
    {
        $this->warnings->put($absoluteSourcePath, $warningMessage);

        $this->writeProgress('W', self::$WARNING_FOREGROUND);
    }

    /**
     * {@inheritdoc}
     */
    public function handleError(string $absoluteSourcePath, Throwable $exception): void
    {
        $this->errors->put($absoluteSourcePath, $exception);

        $this->writeProgress('E', self::$ERROR_FOREGROUND);
    }

    /**
     * {@inheritdoc}
     */
    public function handleCriticalError(Throwable $exception): void
    {
        $this->writeln()
            ->error('Critical error during execution: '.$exception->getMessage());

        if ($this->output->isVeryVerbose()) {
            VarDumper::dump($exception);
        } else {
            $this->writeErrorsDumpTip();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handleEnd(): void
    {
        $stopwatchEvent = $this->stopwatch->stop(self::STOPWATCH_PROCESS);

        if ($this->warnings->isNotEmpty() || $this->errors->isNotEmpty()) {
            $this->writeErrors();
            if (! $this->output->isVeryVerbose()) {
                $this->writeErrorsDumpTip();
            }
        }

        $this->writeln()
            ->writeln('Generation is finished!')
            ->writeln()
            ->writeln($this->sources->count().' source(s) identified')
            ->success($this->successes->count().' success(es)')
            ->warning($this->warnings->count().' warning(s)')
            ->error($this->errors->count().' error(s)')
            ->writeln()
            ->writeln("Execution time: {$this->getFormattedDuration($stopwatchEvent)}")
            ->writeln("Memory usage: {$this->getFormattedMemory($stopwatchEvent)}");
    }

    /**
     * {@inheritdoc}
     */
    public function hasWarnings(): bool
    {
        return $this->warnings->isNotEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function hasErrors(): bool
    {
        return $this->errors->isNotEmpty();
    }

    /**
     * @return Collection|string[]
     */
    public function getSources()
    {
        return $this->sources;
    }

    /**
     * @return Collection|string[]
     */
    public function getSuccesses()
    {
        return $this->successes;
    }

    /**
     * @return Collection|string[]
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * @return Collection|Throwable[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Write the progress with a char and a foreground on output.
     *
     * @param string      $char
     * @param string|null $foreground
     *
     * @return static
     */
    protected function writeProgress(string $char, ?string $foreground = null): self
    {
        $this->write($char, $foreground);

        $alreadyOnLineCount = $this->getProcessedSourcesCount() % self::SOURCES_PER_LINE;
        $isFinished = $this->getProcessedSourcesCount() === $this->sources->count();

        if ($alreadyOnLineCount !== 0 && $isFinished) {
            $this->write(str_repeat(' ', self::SOURCES_PER_LINE - $alreadyOnLineCount));
        }

        if ($alreadyOnLineCount === 0 || $isFinished) {
            $this->writeln("    ({$this->getProcessedSourcesCount()} / {$this->sources->count()})");
        }

        return $this;
    }

    /**
     * Get the number of processed sources.
     *
     * @return int
     */
    protected function getProcessedSourcesCount(): int
    {
        return $this->successes->count() + $this->warnings->count() + $this->errors->count();
    }

    /**
     * Write warnings/errors on output.
     */
    protected function writeErrors(): void
    {
        $this->sources->each(function (string $source) {
            $warning = $this->warnings->get($source);
            if ($warning !== null) {
                $this->writeln()
                    ->warning("Warning with {$source}: {$warning}");

                return;
            }

            $error = $this->errors->get($source);
            if ($error !== null) {
                $this->writeln()
                    ->error("Error with {$source}: {$error->getMessage()}");

                if ($this->output->isVeryVerbose()) {
                    VarDumper::dump($error);
                }
            }
        });
    }

    /**
     * Write a tip about exception dumping.
     */
    protected function writeErrorsDumpTip(): void
    {
        $this->writeln()
            ->writeln('Increase verbosity to see errors dump.');
    }

    /**
     * Format the memory usage to MB.
     *
     * @param StopwatchEvent $stopwatchEvent
     *
     * @return string
     */
    protected function getFormattedMemory(StopwatchEvent $stopwatchEvent)
    {
        return number_format($stopwatchEvent->getMemory() / 1024 / 1024, 2).' MB';
    }

    /**
     * Format the time in microseconds to seconds.
     *
     * @param StopwatchEvent $stopwatchEvent
     *
     * @return string
     */
    protected function getFormattedDuration(StopwatchEvent $stopwatchEvent)
    {
        return number_format($stopwatchEvent->getDuration() / 1000, 3).' s';
    }
}
