<?php

declare(strict_types=1);

namespace Tests\PhpUnitGen\Console\Unit\Execution;

use Exception;
use Mockery;
use Mockery\Mock;
use PhpUnitGen\Console\Config\ConsoleConfig;
use PhpUnitGen\Console\Execution\ProcessHandler;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Symfony\Component\VarDumper\VarDumper;
use Tests\PhpUnitGen\Console\TestCase;
use Tightenco\Collect\Support\Collection;

/**
 * Class ProcessHandlerTest.
 *
 * @covers \PhpUnitGen\Console\Execution\ProcessHandler
 */
class ProcessHandlerTest extends TestCase
{
    /**
     * @var Stopwatch|Mock
     */
    protected $stopwatch;

    /**
     * @var OutputInterface|Mock
     */
    protected $output;

    /**
     * @var ProcessHandler
     */
    protected $processHandler;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->stopwatch = Mockery::mock(Stopwatch::class);
        $this->output = Mockery::mock(OutputInterface::class);

        $this->stopwatch->shouldReceive('start')->once()
            ->with('phpunitgen');

        $this->processHandler = new ProcessHandler($this->stopwatch);
        $this->processHandler->initialize($this->output);
    }

    public function testItStartsWithDefaultConfig(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();

        $this->output->shouldReceive('write')
            ->once()
            ->with('Starting process using ', false);
        $this->output->shouldReceive('write')
            ->once()
            ->with('default config.', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);

        $this->processHandler->handleStart(ConsoleConfig::make(), new Collection(['foo']));

        $this->assertSame(1, $this->processHandler->getSources()->count());
        $this->assertSame(0, $this->processHandler->getSuccesses()->count());
        $this->assertSame(0, $this->processHandler->getWarnings()->count());
        $this->assertSame(0, $this->processHandler->getErrors()->count());
    }

    public function testItStartsWithDetectedConfig(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();

        $this->output->shouldReceive('write')
            ->once()
            ->with('Starting process using ', false);
        $this->output->shouldReceive('write')
            ->once()
            ->with('config file at \'phpunitgen.php\'.', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);

        $this->processHandler->handleStart(ConsoleConfig::make()->setPath('phpunitgen.php'), new Collection(['foo']));

        $this->assertSame(1, $this->processHandler->getSources()->count());
        $this->assertSame(0, $this->processHandler->getSuccesses()->count());
        $this->assertSame(0, $this->processHandler->getWarnings()->count());
        $this->assertSame(0, $this->processHandler->getErrors()->count());
    }

    public function testItManagesQuietOutput(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnTrue();

        $this->output->shouldReceive('write')->never();

        $this->processHandler->handleStart(ConsoleConfig::make(), new Collection());
    }

    public function testItHandlesSuccessWithRemaining(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();

        $this->output->shouldReceive('write')
            ->once()
            ->with('Starting process using ', false);
        $this->output->shouldReceive('write')
            ->once()
            ->with('default config.', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('.', false);

        $this->processHandler->handleStart(ConsoleConfig::make(), new Collection(['foo', 'baz']));
        $this->processHandler->handleSuccess('foo', 'bar');

        $this->assertSame([
            'foo' => 'bar',
        ], $this->processHandler->getSuccesses()->toArray());
        $this->assertSame(0, $this->processHandler->getWarnings()->count());
        $this->assertSame(0, $this->processHandler->getErrors()->count());
    }

    public function testItHandlesWarningWithRemaining(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();

        $this->output->shouldReceive('write')
            ->once()
            ->with('Starting process using ', false);
        $this->output->shouldReceive('write')
            ->once()
            ->with('default config.', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('<fg=yellow>W</>', false);

        $this->processHandler->handleStart(ConsoleConfig::make(), new Collection(['foo', 'baz']));
        $this->processHandler->handleWarning('foo', 'bar');

        $this->assertSame(0, $this->processHandler->getSuccesses()->count());
        $this->assertSame([
            'foo' => 'bar',
        ], $this->processHandler->getWarnings()->toArray());
        $this->assertSame(0, $this->processHandler->getErrors()->count());
    }

    public function testItHandlesErrorWithRemaining(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();

        $this->output->shouldReceive('write')
            ->once()
            ->with('Starting process using ', false);
        $this->output->shouldReceive('write')
            ->once()
            ->with('default config.', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('<fg=red>E</>', false);

        $this->processHandler->handleStart(ConsoleConfig::make(), new Collection(['foo', 'baz']));
        $this->processHandler->handleError('foo', $exception = new Exception('message'));

        $this->assertSame(0, $this->processHandler->getSuccesses()->count());
        $this->assertSame(0, $this->processHandler->getWarnings()->count());
        $this->assertSame([
            'foo' => $exception,
        ], $this->processHandler->getErrors()->toArray());
    }

    public function testItHandlesSuccessWithoutRemainingAndEndOfLine(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();

        $this->output->shouldReceive('write')
            ->once()
            ->with('Starting process using ', false);
        $this->output->shouldReceive('write')
            ->once()
            ->with('default config.', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('.', false);
        $this->output->shouldReceive('write')
            ->once()
            ->with('    (50 / 50)', true);

        $sources = new Collection();
        for ($i = 0; $i < 50; $i++) {
            $sources->add('foo'.$i);
        }

        $this->processHandler->handleStart(ConsoleConfig::make(), $sources);

        for ($i = 0; $i < 49; $i++) {
            $this->processHandler->getSuccesses()->add('foo'.$i);
        }

        $this->processHandler->handleSuccess('foo', 'bar');
    }

    public function testItHandlesCriticalErrorWithVeryVerbose(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();
        $this->output->shouldReceive('isVeryVerbose')->withNoArgs()->andReturnTrue();

        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('<fg=red>Critical error during execution: foo bar</>', true);

        $dumped = false;
        $exception = new Exception('foo bar');

        VarDumper::setHandler(function ($var) use (&$dumped, $exception) {
            $dumped = $exception === $var;
        });

        $this->processHandler->handleCriticalError($exception);

        $this->assertTrue($dumped);
    }

    public function testItHandlesCriticalErrorWithoutVeryVerbose(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();
        $this->output->shouldReceive('isVeryVerbose')->withNoArgs()->andReturnFalse();

        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('<fg=red>Critical error during execution: foo bar</>', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('Increase verbosity to see errors dump.', true);

        $dumped = false;
        VarDumper::setHandler(function () use (&$dumped) {
            $dumped = true;
        });

        $this->processHandler->handleCriticalError(new Exception('foo bar'));

        $this->assertFalse($dumped);
    }

    public function testItHandlesSuccessWithoutRemainingAndNotEndOfLine(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();

        $this->output->shouldReceive('write')
            ->once()
            ->with('Starting process using ', false);
        $this->output->shouldReceive('write')
            ->once()
            ->with('default config.', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('.', false);
        $this->output->shouldReceive('write')
            ->once()
            ->with('     ', false);
        $this->output->shouldReceive('write')
            ->once()
            ->with('    (45 / 45)', true);

        $sources = new Collection();
        for ($i = 0; $i < 45; $i++) {
            $sources->add('foo'.$i);
        }

        $this->processHandler->handleStart(ConsoleConfig::make(), $sources);

        for ($i = 0; $i < 44; $i++) {
            $this->processHandler->getSuccesses()->add('foo'.$i);
        }

        $this->processHandler->handleSuccess('foo', 'bar');
    }

    public function testItHandlesEndWithoutWarningsOrErrors(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();

        $this->output->shouldReceive('write')
            ->once()
            ->with('Starting process using ', false);
        $this->output->shouldReceive('write')
            ->once()
            ->with('default config.', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);

        $this->processHandler->handleStart(ConsoleConfig::make(), new Collection());

        $stopWatchEvent = Mockery::mock(StopwatchEvent::class);

        $stopWatchEvent->shouldReceive([
            'getMemory'   => 78643200,
            'getDuration' => 3500,
        ]);

        $this->stopwatch->shouldReceive('stop')->once()
            ->with('phpunitgen')
            ->andReturn($stopWatchEvent);

        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('Generation is finished!', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('0 source(s) identified', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('<fg=green>0 success(es)</>', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('<fg=yellow>0 warning(s)</>', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('<fg=red>0 error(s)</>', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('Execution time: 3.500 s', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('Memory usage: 75.00 MB', true);

        $this->processHandler->handleEnd();
    }

    public function testItHandlesEndWithWarningsOrErrorsInNormalVerbosity(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();
        $this->output->shouldReceive('isVeryVerbose')->withNoArgs()->andReturnFalse();

        $this->output->shouldReceive('write')
            ->once()
            ->with('Starting process using ', false);
        $this->output->shouldReceive('write')
            ->once()
            ->with('default config.', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);

        $this->processHandler->handleStart(ConsoleConfig::make(), new Collection([
            'foo',
            'bar',
            'baz',
        ]));

        $this->processHandler->getSuccesses()->put('foo', 'fooTest');
        $this->processHandler->getWarnings()->put('foo', 'foo warning');
        $this->processHandler->getErrors()->put('baz', new Exception('baz exception'));

        $stopWatchEvent = Mockery::mock(StopwatchEvent::class);

        $stopWatchEvent->shouldReceive([
            'getMemory'   => 78643200,
            'getDuration' => 3500,
        ]);

        $this->stopwatch->shouldReceive('stop')->once()
            ->with('phpunitgen')
            ->andReturn($stopWatchEvent);

        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('<fg=yellow>Warning with foo: foo warning</>', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('<fg=red>Error with baz: baz exception</>', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('Increase verbosity to see errors dump.', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('Generation is finished!', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('3 source(s) identified', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('<fg=green>1 success(es)</>', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('<fg=yellow>1 warning(s)</>', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('<fg=red>1 error(s)</>', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('Execution time: 3.500 s', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('Memory usage: 75.00 MB', true);

        $dumped = false;
        VarDumper::setHandler(function () use (&$dumped) {
            $dumped = true;
        });

        $this->processHandler->handleEnd();

        $this->assertFalse($dumped);
    }

    public function testItHandlesEndWithWarningsOrErrorsInHighVerbosity(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();
        $this->output->shouldReceive('isVeryVerbose')->withNoArgs()->andReturnTrue();

        $this->output->shouldReceive('write')
            ->once()
            ->with('Starting process using ', false);
        $this->output->shouldReceive('write')
            ->once()
            ->with('default config.', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);

        $this->processHandler->handleStart(ConsoleConfig::make(), new Collection([
            'foo',
            'bar',
            'baz',
        ]));

        $exception = new Exception('baz exception');

        $this->processHandler->getSuccesses()->put('foo', 'fooTest');
        $this->processHandler->getWarnings()->put('foo', 'foo warning');
        $this->processHandler->getErrors()->put('baz', $exception);

        $stopWatchEvent = Mockery::mock(StopwatchEvent::class);

        $stopWatchEvent->shouldReceive([
            'getMemory'   => 78643200,
            'getDuration' => 3500,
        ]);

        $this->stopwatch->shouldReceive('stop')->once()
            ->with('phpunitgen')
            ->andReturn($stopWatchEvent);

        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('<fg=yellow>Warning with foo: foo warning</>', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('<fg=red>Error with baz: baz exception</>', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('Generation is finished!', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('3 source(s) identified', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('<fg=green>1 success(es)</>', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('<fg=yellow>1 warning(s)</>', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('<fg=red>1 error(s)</>', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('Execution time: 3.500 s', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('Memory usage: 75.00 MB', true);

        $dumped = false;
        VarDumper::setHandler(function ($var) use (&$dumped, $exception) {
            $dumped = $var === $exception;
        });

        $this->processHandler->handleEnd();

        $this->assertTrue($dumped);
    }
}
