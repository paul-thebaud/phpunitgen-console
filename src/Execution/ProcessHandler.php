<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Execution;

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
    /**
     * The name of the process on stopwatch.
     */
    protected const STOPWATCH_PROCESS = 'phpunitgen';

    /**
     * The number of displayed sources generation for one output line.
     */
    protected const SOURCES_PER_LINE = 50;

    /**
     * The foreground used for success output.
     */
    protected const SUCCESS_FOREGROUND = 'green';

    /**
     * The foreground used for warning output.
     */
    protected const WARNING_FOREGROUND = 'yellow';

    /**
     * The foreground used for error output.
     */
    protected const ERROR_FOREGROUND = 'red';

    /**
     * @var Stopwatch
     */
    protected $stopwatch;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var Collection|string[] The identified sources.
     */
    protected $sources;

    /**
     * @var int The number of identified sources.
     */
    protected $sourcesCount;

    /**
     * @var int The number of already processed sources.
     */
    protected $processedSourcesCount;

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
        $this->sourcesCount = $sources->count();
        $this->processedSourcesCount = 0;
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

        $this->writeProgress('W', self::WARNING_FOREGROUND);
    }

    /**
     * {@inheritdoc}
     */
    public function handleError(string $absoluteSourcePath, Throwable $exception): void
    {
        $this->errors->put($absoluteSourcePath, $exception);

        $this->writeProgress('E', self::ERROR_FOREGROUND);
    }

    /**
     * {@inheritdoc}
     */
    public function handleCriticalError(Throwable $exception): void
    {
        $this->writeln()
            ->writeln("Critical error during execution: {$exception->getMessage()}", self::ERROR_FOREGROUND);

        if ($this->output->isDebug()) {
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

        if ($this->errors->isNotEmpty() || $this->warnings->isNotEmpty()) {
            if ($this->output->isVeryVerbose()) {
                $this->writeErrors();
            } else {
                $this->writeErrorsDumpTip();
            }
        }

        $this->writeln()
            ->writeln('Generation is finished! ')
            ->writeln()
            ->writeln($this->sourcesCount.' source(s) identified')
            ->writeln($this->successes->count().' success(es)', self::SUCCESS_FOREGROUND)
            ->writeln($this->warnings->count().' warning(s)', self::WARNING_FOREGROUND)
            ->writeln($this->errors->count().' errors(s)', self::ERROR_FOREGROUND)
            ->writeln()
            ->writeln("Execution time: {$this->getFormattedDuration($stopwatchEvent)}")
            ->writeln("Memory usage: {$this->getFormattedMemory($stopwatchEvent)}");
    }

    /**
     * Write the given string to output and line jump.
     *
     * @param string      $string
     * @param string|null $foreground
     *
     * @return $this
     */
    protected function writeln(string $string = '', ?string $foreground = null): self
    {
        return $this->write($string, $foreground, true);
    }

    /**
     * Write the given string to output.
     *
     * @param string      $string
     * @param string|null $foreground
     * @param bool        $newLine
     *
     * @return static
     */
    protected function write(string $string = '', ?string $foreground = null, bool $newLine = false): self
    {
        if ($this->output->isQuiet()) {
            return $this;
        }

        if ($foreground !== null) {
            $string = "<fg={$foreground}>{$string}</>";
        }

        $this->output->write($string, $newLine);

        return $this;
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
        $this->processedSourcesCount++;

        $this->write($char, $foreground);

        $alreadyOnLineCount = $this->processedSourcesCount % self::SOURCES_PER_LINE;
        $isFinished = $this->processedSourcesCount === $this->sourcesCount;

        if ($alreadyOnLineCount !== 0 && $isFinished) {
            $this->write(str_repeat(' ', self::SOURCES_PER_LINE - $alreadyOnLineCount));
        }

        if ($alreadyOnLineCount === 0 || $isFinished) {
            $this->writeln("    ({$this->processedSourcesCount} / {$this->sourcesCount})");
        }

        return $this;
    }

    /**
     * Write warnings/errors on output.
     */
    protected function writeErrors(): void
    {
        $this->sources->each(function (string $source) {
            $warning = $this->warnings->get($source);
            if ($warning !== null && $this->output->isDebug()) {
                $this->writeln()
                    ->writeln("Warning with {$source}: {$warning}", self::WARNING_FOREGROUND);

                return;
            }

            $error = $this->errors->get($source);
            if ($error !== null) {
                $this->writeln()
                    ->writeln("Error with {$source}: {$error->getMessage()}", self::ERROR_FOREGROUND);

                if ($this->output->isDebug()) {
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
        if (! $this->output->isVeryVerbose()) {
            $this->writeln()
                ->writeln('Increase verbosity to see warnings or errors details.');
        }
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
