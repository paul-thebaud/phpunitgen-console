<?php

declare(strict_types=1);

namespace Tests\PhpUnitGen\Console\Unit\Execution;

use Exception;
use Mockery;
use Mockery\Mock;
use PhpUnitGen\Console\Config\ConsoleConfig;
use PhpUnitGen\Console\Contracts\Config\ConfigResolver;
use PhpUnitGen\Console\Contracts\Execution\ProcessHandler;
use PhpUnitGen\Console\Contracts\Files\FileBackup;
use PhpUnitGen\Console\Contracts\Files\Filesystem;
use PhpUnitGen\Console\Contracts\Files\SourcesResolver;
use PhpUnitGen\Console\Contracts\Files\TargetResolver;
use PhpUnitGen\Console\Execution\Runner;
use PhpUnitGen\Core\Contracts\Generators\Factories\ClassFactory;
use PhpUnitGen\Core\Exceptions\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\PhpUnitGen\Console\TestCase;
use Tightenco\Collect\Support\Collection;

/**
 * Class RunnerTest.
 *
 * @covers \PhpUnitGen\Console\Execution\Runner
 */
class RunnerTest extends TestCase
{
    /**
     * @var ConfigResolver|Mock
     */
    protected $configResolver;

    /**
     * @var Filesystem|Mock
     */
    protected $filesystem;

    /**
     * @var SourcesResolver|Mock
     */
    protected $sourcesResolver;

    /**
     * @var TargetResolver|Mock
     */
    protected $targetResolver;

    /**
     * @var FileBackup|Mock
     */
    protected $fileBackup;

    /**
     * @var ProcessHandler|Mock
     */
    protected $processHandler;

    /**
     * @var InputInterface|Mock
     */
    protected $input;

    /**
     * @var OutputInterface|Mock
     */
    protected $output;

    /**
     * @var Runner
     */
    protected $runner;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->configResolver = Mockery::mock(ConfigResolver::class);
        $this->filesystem = Mockery::mock(Filesystem::class);
        $this->sourcesResolver = Mockery::mock(SourcesResolver::class);
        $this->targetResolver = Mockery::mock(TargetResolver::class);
        $this->fileBackup = Mockery::mock(FileBackup::class);
        $this->processHandler = Mockery::mock(ProcessHandler::class);
        $this->input = Mockery::mock(InputInterface::class);
        $this->output = Mockery::mock(OutputInterface::class);
        $this->runner = new Runner(
            $this->configResolver,
            $this->filesystem,
            $this->sourcesResolver,
            $this->targetResolver,
            $this->fileBackup,
            $this->processHandler
        );
    }

    public function testItRunsWithACriticalError(): void
    {
        $exception = new Exception('critical error');

        $this->processHandler->shouldReceive('initialize')->once()
            ->with($this->output);
        $this->processHandler->shouldReceive('handleCriticalError')->once()
            ->with($exception);

        $this->configResolver->shouldReceive('resolve')->once()
            ->with($this->input)
            ->andThrow($exception);

        $this->assertSame(1, $this->runner->run($this->input, $this->output));
    }

    public function testItRunsWithACriticalErrorWhenNoSource(): void
    {
        $this->processHandler->shouldReceive('initialize')->once()
            ->with($this->output);
        $this->processHandler->shouldReceive('handleCriticalError')->once()
            ->with(Mockery::on(function ($exception) {
                return $exception instanceof InvalidArgumentException
                    && $exception->getMessage() === 'no source to generate tests for';
            }));

        $config = ConsoleConfig::make();

        $this->input->shouldReceive('getArgument')->once()
            ->with('source')
            ->andReturn('foo');

        $this->configResolver->shouldReceive('resolve')->once()
            ->with($this->input)
            ->andReturn($config);
        $this->sourcesResolver->shouldReceive('resolve')->once()
            ->with($config, 'foo')
            ->andReturn(new Collection());

        $this->assertSame(1, $this->runner->run($this->input, $this->output));
    }

    public function testItRunsWithACriticalErrorWhenTargetIsAFile(): void
    {
        $this->processHandler->shouldReceive('initialize')->once()
            ->with($this->output);
        $this->processHandler->shouldReceive('handleCriticalError')->once()
            ->with(Mockery::on(function ($exception) {
                return $exception instanceof InvalidArgumentException
                    && $exception->getMessage() === 'target cannot be an existing file';
            }));

        $config = ConsoleConfig::make();

        $this->input->shouldReceive('getArgument')->once()
            ->with('source')
            ->andReturn('foo');
        $this->input->shouldReceive('getArgument')->once()
            ->with('target')
            ->andReturn('bar');

        $this->configResolver->shouldReceive('resolve')->once()
            ->with($this->input)
            ->andReturn($config);
        $this->sourcesResolver->shouldReceive('resolve')->once()
            ->with($config, 'foo')
            ->andReturn(new Collection(['foo', 'bar']));
        $this->filesystem->shouldReceive('isFile')->once()
            ->with('bar')
            ->andReturnTrue();

        $this->assertSame(1, $this->runner->run($this->input, $this->output));
    }

    public function testItRunsWithSuccessWarningAndErrorWithoutOverrideOrBackup(): void
    {
        $config = ConsoleConfig::make();
        $exception = new Exception('baz error');
        $sources = new Collection(['foo', 'bar', 'baz']);

        $this->processHandler->shouldReceive('initialize')->once()
            ->with($this->output);
        $this->processHandler->shouldReceive('handleStart')->once()
            ->with($config, $sources);
        $this->processHandler->shouldReceive('handleSuccess')->once()
            ->with('foo', 'tests/fooTest');
        $this->processHandler->shouldReceive('handleWarning')->once()
            ->with('bar', 'cannot generate tests to tests/barTest, file exists and overwriting is disabled');
        $this->processHandler->shouldReceive('handleError')->once()
            ->with('baz', $exception);
        $this->processHandler->shouldReceive('handleEnd')->once()
            ->withNoArgs();
        $this->processHandler->shouldReceive('hasErrors')
            ->andReturnTrue();

        $this->input->shouldReceive('getArgument')->once()
            ->with('source')
            ->andReturn('src');
        $this->input->shouldReceive('getArgument')->once()
            ->with('target')
            ->andReturn('tests');

        $this->targetResolver->shouldReceive('resolve')
            ->with(Mockery::type(ClassFactory::class), 'foo', 'tests')
            ->andReturn('tests/fooTest');
        $this->targetResolver->shouldReceive('resolve')
            ->with(Mockery::type(ClassFactory::class), 'bar', 'tests')
            ->andReturn('tests/barTest');

        $this->configResolver->shouldReceive('resolve')->once()
            ->with($this->input)
            ->andReturn($config);
        $this->sourcesResolver->shouldReceive('resolve')->once()
            ->with($config, 'src')
            ->andReturn($sources);
        $this->filesystem->shouldReceive('isFile')->once()
            ->with('tests')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('read')->once()
            ->with('foo')
            ->andReturn('<?php class Foo {}');
        $this->filesystem->shouldReceive('read')->once()
            ->with('bar')
            ->andReturn('<?php class Bar {}');
        $this->filesystem->shouldReceive('read')->once()
            ->with('baz')
            ->andThrow($exception);
        $this->filesystem->shouldReceive('has')->once()
            ->with('tests/fooTest')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('has')->once()
            ->with('tests/barTest')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('write')->once()
            ->with('tests/fooTest', Mockery::type('string'));

        $this->assertSame(100, $this->runner->run($this->input, $this->output));
    }

    public function testItRunsWithSuccessWithOverrideAndBackup(): void
    {
        $config = ConsoleConfig::make(['overwriteFiles' => true]);
        $sources = new Collection(['foo']);

        $this->processHandler->shouldReceive('initialize')->once()
            ->with($this->output);
        $this->processHandler->shouldReceive('handleStart')->once()
            ->with($config, $sources);
        $this->processHandler->shouldReceive('handleSuccess')->once()
            ->with('foo', 'tests/fooTest');
        $this->processHandler->shouldReceive('handleEnd')->once()
            ->withNoArgs();
        $this->processHandler->shouldReceive('hasErrors')
            ->andReturnFalse();
        $this->processHandler->shouldReceive('hasWarnings')
            ->andReturnFalse();

        $this->input->shouldReceive('getArgument')->once()
            ->with('source')
            ->andReturn('src');
        $this->input->shouldReceive('getArgument')->once()
            ->with('target')
            ->andReturn('tests');

        $this->targetResolver->shouldReceive('resolve')
            ->with(Mockery::type(ClassFactory::class), 'foo', 'tests')
            ->andReturn('tests/fooTest');

        $this->configResolver->shouldReceive('resolve')->once()
            ->with($this->input)
            ->andReturn($config);
        $this->sourcesResolver->shouldReceive('resolve')->once()
            ->with($config, 'src')
            ->andReturn($sources);
        $this->filesystem->shouldReceive('isFile')->once()
            ->with('tests')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('read')->once()
            ->with('foo')
            ->andReturn('<?php class Foo {}');
        $this->filesystem->shouldReceive('has')->once()
            ->with('tests/fooTest')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('write')->once()
            ->with('tests/fooTest', Mockery::type('string'));

        $this->fileBackup->shouldReceive('backup')->once()
            ->with('tests/fooTest');

        $this->assertSame(0, $this->runner->run($this->input, $this->output));
    }

    public function testItRunsWithWarning(): void
    {
        $config = ConsoleConfig::make();
        $sources = new Collection(['foo']);

        $this->processHandler->shouldReceive('initialize')->once()
            ->with($this->output);
        $this->processHandler->shouldReceive('handleStart')->once()
            ->with($config, $sources);
        $this->processHandler->shouldReceive('handleWarning')->once()
            ->with('foo', 'cannot generate tests to tests/fooTest, file exists and overwriting is disabled');
        $this->processHandler->shouldReceive('handleEnd')->once()
            ->withNoArgs();
        $this->processHandler->shouldReceive('hasErrors')
            ->andReturnFalse();
        $this->processHandler->shouldReceive('hasWarnings')
            ->andReturnTrue();

        $this->input->shouldReceive('getArgument')->once()
            ->with('source')
            ->andReturn('src');
        $this->input->shouldReceive('getArgument')->once()
            ->with('target')
            ->andReturn('tests');

        $this->targetResolver->shouldReceive('resolve')
            ->with(Mockery::type(ClassFactory::class), 'foo', 'tests')
            ->andReturn('tests/fooTest');

        $this->configResolver->shouldReceive('resolve')->once()
            ->with($this->input)
            ->andReturn($config);
        $this->sourcesResolver->shouldReceive('resolve')->once()
            ->with($config, 'src')
            ->andReturn($sources);
        $this->filesystem->shouldReceive('isFile')->once()
            ->with('tests')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('read')->once()
            ->with('foo')
            ->andReturn('<?php class Foo {}');
        $this->filesystem->shouldReceive('has')->once()
            ->with('tests/fooTest')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('write')->never();

        $this->assertSame(101, $this->runner->run($this->input, $this->output));
    }
}
