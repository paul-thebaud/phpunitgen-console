<?php

declare(strict_types=1);

namespace Tests\PhpUnitGen\Console\Unit;

use Mockery;
use PhpUnitGen\Console\Commands\RunCommand;
use PhpUnitGen\Console\ConsoleApplication;
use PhpUnitGen\Console\Contracts\Execution\Runner;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\PhpUnitGen\Console\TestCase;

/**
 * Class ConsoleApplicationTest.
 *
 * @covers \PhpUnitGen\Console\ConsoleApplication
 */
class ConsoleApplicationTest extends TestCase
{
    public function testMake(): void
    {
        $application = ConsoleApplication::make();

        $this->assertInstanceOf(RunCommand::class, $application->get('phpunitgen'));
    }

    public function testRun(): void
    {
        $container = Mockery::mock(ContainerInterface::class);
        $runner = Mockery::mock(Runner::class);
        $command = Mockery::mock(new RunCommand($runner))->makePartial();
        $input = new ArrayInput(['source' => 'src'], $command->getDefinition());
        $output = Mockery::mock(OutputInterface::class);

        $container->shouldReceive('get')
            ->with(RunCommand::class)
            ->andReturn($command);

        $runner->shouldReceive('run')
            ->with($input, $output)
            ->andReturn(0);

        $application = new ConsoleApplication($container);
        $application->setAutoExit(false);

        $this->assertSame(0, $application->run($input, $output));
    }
}
