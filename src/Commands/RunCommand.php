<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RunCommand.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class RunCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('run')
            ->setDescription('Generate unit tests\' skeletons for a file/directory')
            ->setHelp(
                'Use it to generate your unit tests skeletons. See documentation on '.
                'https://phpunitgen.io/doc/todo'
            )
            ->addOption(
                'config',
                'C',
                InputOption::VALUE_OPTIONAL,
                'Define a custom path to the PhpUnitGen config'
            )
            ->addArgument(
                'source',
                InputArgument::OPTIONAL,
                'The source file/dir path to generate tests for (default to "src" or "app")'
            )
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                'The target file/dir path to generate tests to (default to "tests")'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('TODO');
    }
}
