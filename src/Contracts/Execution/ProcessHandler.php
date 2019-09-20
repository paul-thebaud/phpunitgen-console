<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Contracts\Execution;

use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Tightenco\Collect\Support\Collection;

/**
 * Interface ProcessHandler.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
interface ProcessHandler
{
    /**
     * Initialize with the given output. Will start measuring time and memory.
     *
     * @param OutputInterface $output
     */
    public function initialize(OutputInterface $output): void;

    /**
     * Handle the process start for the given sources.
     *
     * @param Collection $sources
     */
    public function handleStart(Collection $sources): void;

    /**
     * Handle a successful generation.
     *
     * @param string $absoluteSourcePath
     * @param string $absoluteTargetPath
     */
    public function handleSuccess(string $absoluteSourcePath, string $absoluteTargetPath): void;

    /**
     * Handle a warning about generation.
     *
     * @param string $absoluteSourcePath
     * @param string $warningMessage
     */
    public function handleWarning(string $absoluteSourcePath, string $warningMessage): void;

    /**
     * Handle an error about generation.
     *
     * @param string    $absoluteSourcePath
     * @param Throwable $exception
     */
    public function handleError(string $absoluteSourcePath, Throwable $exception): void;

    /**
     * Handle a critical error (not an error about generation process).
     *
     * @param Throwable $exception
     */
    public function handleCriticalError(Throwable $exception): void;

    /**
     * Handle the process end and write the report on output.
     */
    public function handleEnd(): void;
}
