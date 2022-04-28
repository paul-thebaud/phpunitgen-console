<?php

declare(strict_types=1);

namespace Tests\PhpUnitGen\Console\Unit;

use Mockery;
use PackageVersions\Versions;
use PhpUnitGen\Console\Commands\RunCommand;
use PhpUnitGen\Console\ConsoleApplication;
use PhpUnitGen\Console\Contracts\Execution\Runner;
use PhpUnitGen\Core\Helpers\Str;
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

        $output->shouldReceive(['isQuiet' => false]);
        $output->shouldReceive('writeln')
            ->once()
            ->with("PhpUnitGen <info>3.0.1</info>\n");

        $runner->shouldReceive('run')
            ->with($input, $output)
            ->andReturn(0);

        $application = new ConsoleApplication($container);
        $application->setAutoExit(false);

        $this->assertSame(0, $application->run($input, $output));
    }

    protected function getVersions(): array
    {
        $coreVersion = Str::beforeLast('@', Versions::getVersion('phpunitgen/core'));
        $consoleVersion = Str::beforeLast('@', Versions::getVersion('phpunitgen/console'));

        return [$coreVersion, $consoleVersion];
    }
}
