<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Contracts\Execution;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface Runner.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
interface Runner
{
    /**
     * Execute all PhpUnitGen tasks for the input, output, and styled I/O.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function run(InputInterface $input, OutputInterface $output): int;
}
