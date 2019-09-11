<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Commands;

use PhpUnitGen\Console\Contracts\Config\ConfigResolver;
use PhpUnitGen\Core\Application;
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
     * @var ConfigResolver
     */
    protected $configResolver;

    /**
     * RunCommand constructor.
     *
     * @param ConfigResolver $configResolver
     */
    public function __construct(ConfigResolver $configResolver)
    {
        parent::__construct();

        $this->configResolver = $configResolver;
    }

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
            ->addOption(
                'overwrite',
                'O',
                InputOption::VALUE_NONE,
                'Overwrite existing files with PhpUnitGen generated files'
            )
            ->addArgument(
                'source',
                InputArgument::REQUIRED,
                'The source file/dir path to generate tests for',
            )
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                'The target file/dir path to generate tests',
                'tests'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->configResolver->resolve($input->getOption('config'));

        Application::make($config->toArray());

        $output->writeln('DONE!');
    }
}
