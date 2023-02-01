<?php

namespace PhpUnitGen\Console\Contracts\Reporters;

use PhpUnitGen\Console\Contracts\Config\ConsoleConfig;
use Throwable;
use Tightenco\Collect\Support\Collection;

/**
 * Interface Reporter.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
interface Reporter
{
    /**
     * Handle the process start for the given config and sources.
     *
     * @param ConsoleConfig $config
     * @param Collection    $sources
     */
    public function onStart(ConsoleConfig $config, Collection $sources): void;

    /**
     * Handle a successful generation.
     *
     * @param string $absoluteSourcePath
     * @param string $absoluteTargetPath
     */
    public function onSuccess(string $absoluteSourcePath, string $absoluteTargetPath): void;

    /**
     * Handle a warning about generation.
     *
     * @param string $absoluteSourcePath
     * @param string $warningMessage
     */
    public function onWarning(string $absoluteSourcePath, string $warningMessage): void;

    /**
     * Handle an error about generation.
     *
     * @param string    $absoluteSourcePath
     * @param Throwable $exception
     */
    public function onError(string $absoluteSourcePath, Throwable $exception): void;

    /**
     * Handle a critical error (not an error about generation process). Return an exit status code.
     *
     * @param Throwable $exception
     *
     * @return int
     */
    public function onCriticalError(Throwable $exception): int;

    /**
     * Terminate the reporting process. Return an exit status code.
     *
     * @return int
     */
    public function terminate(): int;
}
