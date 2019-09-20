<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Adapters\Laravel;

use Illuminate\Console\Command;
use PhpUnitGen\Console\Commands\RunsPhpUnitGen;
use PhpUnitGen\Console\Contracts\Execution\Runner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PhpUnitGenCommand.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class PhpUnitGenCommand extends Command
{
    use RunsPhpUnitGen;

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
    protected function configure(): void
    {
        $this->configureCommand();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->runner->run($input, $output);
    }
}
