<?php

namespace PhpUnitGen\Console\Reporters;

use PhpUnitGen\Console\Contracts\Reporters\Reporter;
use PhpUnitGen\Console\Contracts\Reporters\ReporterFactory as ReporterFactoryContract;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ReporterFactory.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class ReporterFactory implements ReporterFactoryContract
{
    /**
     * {@inheritdoc}
     */
    public function makeReporter(InputInterface $input, OutputInterface $output): Reporter
    {
        if ($input->getOption('json')) {
            return new JsonReporter($output);
        }

        return new TextReporter($output);
    }
}
