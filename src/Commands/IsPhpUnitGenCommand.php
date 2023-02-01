<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Commands;

use PhpUnitGen\Console\Contracts\Execution\Runner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait IsPhpUnitGenCommand.
 *
 * This trait can be used to configure a Symfony command for PhpUnitGen execution.
 *
 * @mixin Command
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
trait IsPhpUnitGenCommand
{
    /**
     * Configure the command properties, such as name, options, args, etc.
     */
    protected function configureCommand(): void
    {
        $this->setName('phpunitgen')
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
            ->addOption(
                'overwrite',
                'O',
                InputOption::VALUE_NONE,
                'Overwrite existing files with PhpUnitGen generated files'
            )
            ->addOption(
                'json',
                null,
                InputOption::VALUE_NONE,
                'Print a JSON report instead of the classical text report'
            )
            ->addArgument(
                'source',
                InputArgument::REQUIRED,
                'The source file/dir path to generate tests for'
            )
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                'The target dir path where tests will be generated',
                'tests'
            );
    }

    /**
     * Execute the command using the runner.
     *
     * @param Runner          $runner
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function executeCommand(Runner $runner, InputInterface $input, OutputInterface $output): int
    {
        return $runner->run($input, $output);
    }
}
