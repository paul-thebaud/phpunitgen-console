<?php

namespace PhpUnitGen\Console\Contracts\Reporters;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface ReporterFactory.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
interface ReporterFactory
{
    /**
     * Make a reporter instance depending on input and output.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return Reporter
     */
    public function makeReporter(InputInterface $input, OutputInterface $output): Reporter;
}
