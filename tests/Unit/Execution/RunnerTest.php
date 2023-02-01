<?php

declare(strict_types=1);

namespace Tests\PhpUnitGen\Console\Unit\Execution;

use Exception;
use Mockery;
use Mockery\Mock;
use PhpUnitGen\Console\Config\ConsoleConfig;
use PhpUnitGen\Console\Contracts\Config\ConfigResolver;
use PhpUnitGen\Console\Contracts\Files\FileBackup;
use PhpUnitGen\Console\Contracts\Files\Filesystem;
use PhpUnitGen\Console\Contracts\Files\SourcesResolver;
use PhpUnitGen\Console\Contracts\Files\TargetResolver;
use PhpUnitGen\Console\Contracts\Reporters\Reporter;
use PhpUnitGen\Console\Contracts\Reporters\ReporterFactory;
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
     * @var Reporter|Mock
     */
    protected $reporter;

    /**
     * @var ReporterFactory|Mock
     */
    protected $reporterFactory;

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
        $this->reporter = Mockery::mock(Reporter::class);
        $this->reporterFactory = Mockery::mock(ReporterFactory::class);
        $this->input = Mockery::mock(InputInterface::class);
        $this->output = Mockery::mock(OutputInterface::class);
        $this->runner = new Runner(
            $this->configResolver,
            $this->filesystem,
            $this->sourcesResolver,
            $this->targetResolver,
            $this->fileBackup,
            $this->reporterFactory
        );

        $this->reporterFactory->shouldReceive('makeReporter')
            ->once()
            ->with($this->input, $this->output)
            ->andReturn($this->reporter);
    }

    public function testItRunsWithACriticalError(): void
    {
        $exception = new Exception('critical error');

        $this->reporter->shouldReceive('onCriticalError')->once()
            ->with($exception)
            ->andReturn(1);

        $this->configResolver->shouldReceive('resolve')->once()
            ->with($this->input)
            ->andThrow($exception);

        $this->assertSame(1, $this->runner->run($this->input, $this->output));
    }

    public function testItRunsWithACriticalErrorWhenNoSource(): void
    {
        $this->reporter->shouldReceive('onCriticalError')->once()
            ->with(Mockery::on(function ($exception) {
                return $exception instanceof InvalidArgumentException
                    && $exception->getMessage() === 'no source to generate tests for';
            }))
            ->andReturn(1);

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
        $this->reporter->shouldReceive('onCriticalError')->once()
            ->with(Mockery::on(function ($exception) {
                return $exception instanceof InvalidArgumentException
                    && $exception->getMessage() === 'target cannot be an existing file';
            }))
            ->andReturn(1);

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

        $this->reporter->shouldReceive('onStart')->once()
            ->with($config, $sources);
        $this->reporter->shouldReceive('onSuccess')->once()
            ->with('foo', 'tests/fooTest');
        $this->reporter->shouldReceive('onWarning')->once()
            ->with('bar', 'cannot generate tests to tests/barTest, file exists and overwriting is disabled');
        $this->reporter->shouldReceive('onError')->once()
            ->with('baz', $exception);
        $this->reporter->shouldReceive('terminate')->once()
            ->withNoArgs()
            ->andReturn(100);

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
            ->andReturn('<?php class Foo { public function dummy() {} }');
        $this->filesystem->shouldReceive('read')->once()
            ->with('bar')
            ->andReturn('<?php class Bar { public function dummy() {} }');
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

        $this->reporter->shouldReceive('onStart')->once()
            ->with($config, $sources);
        $this->reporter->shouldReceive('onSuccess')->once()
            ->with('foo', 'tests/fooTest');
        $this->reporter->shouldReceive('terminate')->once()
            ->withNoArgs()
            ->andReturn(0);

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
            ->andReturn('<?php class Foo { public function dummy() {} }');
        $this->filesystem->shouldReceive('has')->once()
            ->with('tests/fooTest')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('write')->once()
            ->with('tests/fooTest', Mockery::type('string'));

        $this->fileBackup->shouldReceive('backup')->once()
            ->with('tests/fooTest');

        $this->assertSame(0, $this->runner->run($this->input, $this->output));
    }

    public function testItRunsWithWarningWhenExistsAndOverwriteDisabled(): void
    {
        $config = ConsoleConfig::make();
        $sources = new Collection(['foo']);

        $this->reporter->shouldReceive('onStart')->once()
            ->with($config, $sources);
        $this->reporter->shouldReceive('onWarning')->once()
            ->with('foo', 'cannot generate tests to tests/fooTest, file exists and overwriting is disabled');
        $this->reporter->shouldReceive('terminate')->once()
            ->withNoArgs()
            ->andReturn(101);

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
            ->andReturn('<?php class Foo { public function dummy() {} }');
        $this->filesystem->shouldReceive('has')->once()
            ->with('tests/fooTest')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('write')->never();

        $this->assertSame(101, $this->runner->run($this->input, $this->output));
    }

    public function testItRunsWithWarningWhenInterface(): void
    {
        $config = ConsoleConfig::make();
        $sources = new Collection(['foo']);

        $this->reporter->shouldReceive('onStart')->once()
            ->with($config, $sources);
        $this->reporter->shouldReceive('onWarning')->once()
            ->with('foo',
                'cannot generate tests, file is an interface/anonymous class or does not contain any public method');
        $this->reporter->shouldReceive('terminate')->once()
            ->withNoArgs()
            ->andReturn(101);

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
            ->andReturn('<?php interface Foo { public function dummy() {} }');
        $this->filesystem->shouldReceive('has')->never();
        $this->filesystem->shouldReceive('write')->never();

        $this->assertSame(101, $this->runner->run($this->input, $this->output));
    }

    public function testItRunsWithWarningWhenNoMethods(): void
    {
        $config = ConsoleConfig::make();
        $sources = new Collection(['foo']);

        $this->reporter->shouldReceive('onStart')->once()
            ->with($config, $sources);
        $this->reporter->shouldReceive('onWarning')->once()
            ->with('foo',
                'cannot generate tests, file is an interface/anonymous class or does not contain any public method');
        $this->reporter->shouldReceive('terminate')->once()
            ->withNoArgs()
            ->andReturn(101);

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
            ->andReturn('<?php class Foo { }');
        $this->filesystem->shouldReceive('has')->never();
        $this->filesystem->shouldReceive('write')->never();

        $this->assertSame(101, $this->runner->run($this->input, $this->output));
    }
}
