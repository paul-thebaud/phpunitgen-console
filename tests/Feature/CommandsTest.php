<?php

declare(strict_types=1);

namespace Tests\PhpUnitGen\Console\Feature;

use Illuminate\Foundation\Application;
use League\Container\Container;
use Mockery;
use PhpUnitGen\Console\Adapters\Laravel\PhpUnitGenCommand;
use PhpUnitGen\Console\Commands\RunCommand;
use PhpUnitGen\Console\Container\ConsoleContainerFactory;
use PhpUnitGen\Console\Contracts\Files\Filesystem;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\PhpUnitGen\Console\TestCase;
use Tightenco\Collect\Support\Collection;

/**
 * Class CommandsTest.
 */
class CommandsTest extends TestCase
{
    /**
     * @param string $commandClass
     *
     * @dataProvider commandsDataProvider
     */
    public function testItRunsWithExistingConfig(string $commandClass): void
    {
        $filesystem = Mockery::mock(Filesystem::class);

        /** @var Container $container */
        $container = ConsoleContainerFactory::make();
        $container->add(Filesystem::class, $filesystem);

        $command = $container->get($commandClass);
        if ($command instanceof PhpUnitGenCommand) {
            $command->setLaravel(new Application());
        }

        $commandTester = new CommandTester($command);

        $filesystem->shouldReceive('has')->once()
            ->with('phpunitgen.php')
            ->andReturnTrue();
        $filesystem->shouldReceive('read')->once()
            ->with('phpunitgen.php')
            ->andReturn('<?php return [];');
        $filesystem->shouldReceive('isFile')->once()
            ->with('src')
            ->andReturnFalse();
        $filesystem->shouldReceive('listFiles')->once()
            ->with('src')
            ->andReturn(new Collection([
                'src/Foo.php',
                'src/Bar.php',
                'src/views/index.html',
            ]));
        $filesystem->shouldReceive('isFile')->once()
            ->with('tests')
            ->andReturnFalse();
        $filesystem->shouldReceive('read')->once()
            ->with('src/Foo.php')
            ->andReturn('<?php class Foo {}');
        $filesystem->shouldReceive('read')->once()
            ->with('src/Bar.php')
            ->andReturn('<?php return [];');
        $filesystem->shouldReceive('getRoot')->once()
            ->withNoArgs()
            ->andReturn('/root');
        $filesystem->shouldReceive('has')->once()
            ->with('tests/Unit/FooTest.php')
            ->andReturnFalse();
        $filesystem->shouldReceive('write')->once()
            ->with('tests/Unit/FooTest.php', Mockery::type('string'));

        $commandTester->execute([
            'source' => 'src',
        ]);

        $output = $commandTester->getDisplay();

        $this->assertSame(100, $commandTester->getStatusCode());
        $this->assertStringContainsString('Starting process using config file at \'phpunitgen.php\'.', $output);
        $this->assertStringContainsString('.E', $output);
        $this->assertStringContainsString('(2 / 2)', $output);
        $this->assertStringContainsString(
            'Error with src/Bar.php: code must contains exactly one class/interface/trait, found 0',
            $output
        );
        $this->assertStringContainsString('Increase verbosity to see errors dump.', $output);
        $this->assertStringContainsString('2 source(s) identified', $output);
        $this->assertStringContainsString('1 success(es)', $output);
        $this->assertStringContainsString('0 warning(s)', $output);
        $this->assertStringContainsString('1 error(s)', $output);
        $this->assertStringContainsString('Execution time:', $output);
        $this->assertStringContainsString('Memory usage:', $output);
    }

    public function commandsDataProvider(): array
    {
        return [
            [RunCommand::class],
            [PhpUnitGenCommand::class],
        ];
    }
}
