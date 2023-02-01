<?php

declare(strict_types=1);

namespace Tests\PhpUnitGen\Console\Unit\Reporters;

use Exception;
use Mockery;
use Mockery\Mock;
use PhpUnitGen\Console\Config\ConsoleConfig;
use PhpUnitGen\Console\Reporters\TextReporter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Symfony\Component\VarDumper\VarDumper;
use Tests\PhpUnitGen\Console\TestCase;
use Tightenco\Collect\Support\Collection;

/**
 * Class TextReporterTest.
 *
 * @covers \PhpUnitGen\Console\Reporters\TextReporter
 * @covers \PhpUnitGen\Console\Reporters\AbstractReporter
 * @covers \PhpUnitGen\Console\Commands\HasOutput
 */
class TextReporterTest extends TestCase
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
     * @var TextReporter
     */
    protected $textReporter;

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

        $this->textReporter = new TextReporter($this->output);
        $this->textReporter->withStopwatch($this->stopwatch);
    }

    public function assertApplicationIsWritten(): void
    {
        $this->output->shouldReceive('write')
            ->once()
            ->with('PhpUnitGen ', false);
        $this->output->shouldReceive('write')
            ->once()
            ->with('<fg=green>5.0.0</>', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
    }

    public function testItStartsWithDefaultConfig(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();

        $this->assertApplicationIsWritten();

        $this->output->shouldReceive('write')
            ->once()
            ->with('Starting process using default config.', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);

        $this->textReporter->onStart(ConsoleConfig::make(), new Collection(['foo']));

        $this->assertSame(1, $this->textReporter->getSources()->count());
        $this->assertSame(0, $this->textReporter->getSuccesses()->count());
        $this->assertSame(0, $this->textReporter->getWarnings()->count());
        $this->assertSame(0, $this->textReporter->getErrors()->count());
    }

    public function testItStartsWithDetectedConfig(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();

        $this->assertApplicationIsWritten();

        $this->output->shouldReceive('write')
            ->once()
            ->with('Starting process using config file at \'phpunitgen.php\'.', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);

        $this->textReporter->onStart(ConsoleConfig::make()->setPath('phpunitgen.php'), new Collection(['foo']));

        $this->assertSame(1, $this->textReporter->getSources()->count());
        $this->assertSame(0, $this->textReporter->getSuccesses()->count());
        $this->assertSame(0, $this->textReporter->getWarnings()->count());
        $this->assertSame(0, $this->textReporter->getErrors()->count());
    }

    public function testItManagesQuietOutput(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnTrue();

        $this->output->shouldReceive('write')->never();

        $this->textReporter->onStart(ConsoleConfig::make(), new Collection());
    }

    public function testItHandlesSuccessWithRemaining(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();

        $this->assertApplicationIsWritten();

        $this->output->shouldReceive('write')
            ->once()
            ->with('Starting process using default config.', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('.', false);

        $this->textReporter->onStart(ConsoleConfig::make(), new Collection(['foo', 'baz']));
        $this->textReporter->onSuccess('foo', 'bar');

        $this->assertSame([
            'foo' => 'bar',
        ], $this->textReporter->getSuccesses()->toArray());
        $this->assertSame(0, $this->textReporter->getWarnings()->count());
        $this->assertSame(0, $this->textReporter->getErrors()->count());
    }

    public function testItHandlesWarningWithRemaining(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();

        $this->assertApplicationIsWritten();

        $this->output->shouldReceive('write')
            ->once()
            ->with('Starting process using default config.', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('<fg=yellow>W</>', false);

        $this->textReporter->onStart(ConsoleConfig::make(), new Collection(['foo', 'baz']));
        $this->textReporter->onWarning('foo', 'bar');

        $this->assertSame(0, $this->textReporter->getSuccesses()->count());
        $this->assertSame([
            'foo' => 'bar',
        ], $this->textReporter->getWarnings()->toArray());
        $this->assertSame(0, $this->textReporter->getErrors()->count());
    }

    public function testItHandlesErrorWithRemaining(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();

        $this->assertApplicationIsWritten();

        $this->output->shouldReceive('write')
            ->once()
            ->with('Starting process using default config.', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('<fg=red>E</>', false);

        $this->textReporter->onStart(ConsoleConfig::make(), new Collection(['foo', 'baz']));
        $this->textReporter->onError('foo', $exception = new Exception('message'));

        $this->assertSame(0, $this->textReporter->getSuccesses()->count());
        $this->assertSame(0, $this->textReporter->getWarnings()->count());
        $this->assertSame([
            'foo' => $exception,
        ], $this->textReporter->getErrors()->toArray());
    }

    public function testItHandlesSuccessWithoutRemainingAndEndOfLine(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();

        $this->assertApplicationIsWritten();

        $this->output->shouldReceive('write')
            ->once()
            ->with('Starting process using default config.', true);
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
            $sources->add('foo' . $i);
        }

        $this->textReporter->onStart(ConsoleConfig::make(), $sources);

        for ($i = 0; $i < 49; $i++) {
            $this->textReporter->getSuccesses()->add('foo' . $i);
        }

        $this->textReporter->onSuccess('foo', 'bar');
    }

    public function testItHandlesCriticalError(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();
        $this->output->shouldReceive('isVeryVerbose')->withNoArgs()->andReturnTrue();

        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('<fg=red>Critical error during execution:</>', true);

        $dumped = false;
        $exception = new Exception('foo bar');

        VarDumper::setHandler(function ($var) use (&$dumped, $exception) {
            $dumped = $exception === $var;
        });

        $this->textReporter->onCriticalError($exception);

        $this->assertTrue($dumped);
    }

    public function testItHandlesSuccessWithoutRemainingAndNotEndOfLine(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();

        $this->assertApplicationIsWritten();

        $this->output->shouldReceive('write')
            ->once()
            ->with('Starting process using default config.', true);
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
            $sources->add('foo' . $i);
        }

        $this->textReporter->onStart(ConsoleConfig::make(), $sources);

        for ($i = 0; $i < 44; $i++) {
            $this->textReporter->getSuccesses()->add('foo' . $i);
        }

        $this->textReporter->onSuccess('foo', 'bar');
    }

    public function testItHandlesEndWithoutWarningsOrErrors(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();

        $this->assertApplicationIsWritten();

        $this->output->shouldReceive('write')
            ->once()
            ->with('Starting process using default config.', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);

        $this->textReporter->onStart(ConsoleConfig::make(), new Collection());

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

        $this->assertSame(0, $this->textReporter->terminate());
    }

    public function testItHandlesEndWithWarningsOrErrorsInNormalVerbosity(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();
        $this->output->shouldReceive('isVeryVerbose')->withNoArgs()->andReturnFalse();

        $this->assertApplicationIsWritten();

        $this->output->shouldReceive('write')
            ->once()
            ->with('Starting process using default config.', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);

        $this->textReporter->onStart(ConsoleConfig::make(), new Collection([
            'foo',
            'bar',
            'baz',
        ]));

        $this->textReporter->getSuccesses()->put('foo', 'fooTest');
        $this->textReporter->getWarnings()->put('foo', 'foo warning');
        $this->textReporter->getErrors()->put('baz', new Exception('baz exception'));

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

        $this->assertSame(100, $this->textReporter->terminate());

        $this->assertFalse($dumped);
    }

    public function testItHandlesEndWithWarningsOrErrorsInHighVerbosity(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();
        $this->output->shouldReceive('isVeryVerbose')->withNoArgs()->andReturnTrue();

        $this->assertApplicationIsWritten();

        $this->output->shouldReceive('write')
            ->once()
            ->with('Starting process using default config.', true);
        $this->output->shouldReceive('write')
            ->once()
            ->with('', true);

        $this->textReporter->onStart(ConsoleConfig::make(), new Collection([
            'foo',
            'bar',
            'baz',
        ]));

        $exception = new Exception('baz exception');

        $this->textReporter->getSuccesses()->put('foo', 'fooTest');
        $this->textReporter->getWarnings()->put('foo', 'foo warning');
        $this->textReporter->getErrors()->put('baz', $exception);

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

        $this->assertSame(100, $this->textReporter->terminate());
        $this->assertTrue($dumped);
    }

    public function testItDetermineExitCode(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();
        $this->output->shouldReceive('isVeryVerbose')->withNoArgs()->andReturnTrue();
        $this->output->shouldReceive('write')->withAnyArgs();

        $stopWatchEvent = Mockery::mock(StopwatchEvent::class);

        $stopWatchEvent->shouldReceive([
            'getMemory'   => 78643200,
            'getDuration' => 3500,
        ]);

        $this->stopwatch->shouldReceive('stop')
            ->times(3)
            ->with('phpunitgen')
            ->andReturn($stopWatchEvent);

        $this->textReporter->onStart(ConsoleConfig::make(), new Collection());

        $this->assertSame(0, $this->textReporter->terminate());

        $this->textReporter->onWarning('foo', 'bar');

        $this->assertSame(101, $this->textReporter->terminate());

        $this->textReporter->onError('foo', new Exception('bar'));

        $this->assertSame(100, $this->textReporter->terminate());
    }
}
