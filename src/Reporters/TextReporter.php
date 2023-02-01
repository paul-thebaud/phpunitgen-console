<?php

namespace PhpUnitGen\Console\Reporters;

use PhpUnitGen\Console\ConsoleApplication;
use PhpUnitGen\Console\Contracts\Config\ConsoleConfig;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Symfony\Component\VarDumper\VarDumper;
use Throwable;
use Tightenco\Collect\Support\Collection;

/**
 * Class TextReporter.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
final class TextReporter extends AbstractReporter
{
    /**
     * The number of displayed sources generation for one output line.
     */
    private const SOURCES_PER_LINE = 50;

    /**
     * {@inheritdoc}
     */
    public function onStart(ConsoleConfig $config, Collection $sources): void
    {
        parent::onStart($config, $sources);

        $this->write('PhpUnitGen ')
            ->success(ConsoleApplication::VERSION)
            ->writeln();

        $startingMessage = 'Starting process using ';
        $configPath = $config->getPath();
        if ($configPath) {
            $startingMessage .= "config file at '{$configPath}'.";
        } else {
            $startingMessage .= 'default config.';
        }

        $this->writeln($startingMessage)
            ->writeln();
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess(string $absoluteSourcePath, string $absoluteTargetPath): void
    {
        parent::onSuccess($absoluteSourcePath, $absoluteTargetPath);

        $this->writeProgress('.');
    }

    /**
     * {@inheritdoc}
     */
    public function onWarning(string $absoluteSourcePath, string $warningMessage): void
    {
        parent::onWarning($absoluteSourcePath, $warningMessage);

        $this->writeProgress('W', self::$WARNING_FOREGROUND);
    }

    /**
     * {@inheritdoc}
     */
    public function onError(string $absoluteSourcePath, Throwable $exception): void
    {
        parent::onError($absoluteSourcePath, $exception);

        $this->writeProgress('E', self::$ERROR_FOREGROUND);
    }

    /**
     * {@inheritdoc}
     */
    public function terminate(): int
    {
        $event = $this->terminateReport();

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
            ->writeln("Execution time: {$this->getFormattedDuration($event)}")
            ->writeln("Memory usage: {$this->getFormattedMemory($event)}");

        return $this->determineExitCode();
    }

    /**
     * Write the progress with a char and a foreground on output.
     *
     * @param string      $char
     * @param string|null $foreground
     */
    private function writeProgress(string $char, ?string $foreground = null): void
    {
        $this->write($char, $foreground);

        $alreadyOnLineCount = $this->countProcessedSourcesCount() % self::SOURCES_PER_LINE;
        $isFinished = $this->countProcessedSourcesCount() === $this->sources->count();

        if ($alreadyOnLineCount !== 0 && $isFinished) {
            $this->write(str_repeat(' ', self::SOURCES_PER_LINE - $alreadyOnLineCount));
        }

        if ($alreadyOnLineCount === 0 || $isFinished) {
            $this->writeln("    ({$this->countProcessedSourcesCount()} / {$this->sources->count()})");
        }
    }

    /**
     * Count the number of already processed sources.
     *
     * @return int
     */
    private function countProcessedSourcesCount(): int
    {
        return $this->successes->count() + $this->warnings->count() + $this->errors->count();
    }

    /**
     * Write warnings/errors on output.
     */
    private function writeErrors(): void
    {
        $this->sources->each(function (string $source) {
            $warning = $this->warnings->get($source);
            if ($warning !== null) {
                $this->writeln()
                    ->warning("Warning with $source: $warning");

                return;
            }

            $error = $this->errors->get($source);
            if ($error !== null) {
                $this->writeln()
                    ->error("Error with $source: {$error->getMessage()}");

                if ($this->output->isVeryVerbose()) {
                    VarDumper::dump($error);
                }
            }
        });
    }

    /**
     * Write a tip about exception dumping.
     */
    private function writeErrorsDumpTip(): void
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
    private function getFormattedMemory(StopwatchEvent $stopwatchEvent): string
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
    private function getFormattedDuration(StopwatchEvent $stopwatchEvent): string
    {
        return number_format($stopwatchEvent->getDuration() / 1000, 3).' s';
    }
}
