<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Commands;

use PhpUnitGen\Console\Contracts\Execution\Runner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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
    use IsPhpUnitGenCommand;

    /**
     * @var Runner
     */
    protected $runner;

    /**
     * RunCommand constructor.
     *
     * @param Runner $runner
     */
    public function __construct(Runner $runner)
    {
        parent::__construct();

        $this->runner = $runner;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->configureCommand();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->executeCommand($this->runner, $input, $output);
    }
}
