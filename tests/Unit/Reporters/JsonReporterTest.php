<?php

declare(strict_types=1);

namespace Tests\PhpUnitGen\Console\Unit\Reporters;

use Exception;
use Mockery;
use Mockery\Mock;
use PhpUnitGen\Console\Config\ConsoleConfig;
use PhpUnitGen\Console\Reporters\JsonReporter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Symfony\Component\VarDumper\VarDumper;
use Tests\PhpUnitGen\Console\TestCase;
use Tightenco\Collect\Support\Collection;

/**
 * Class JsonReporterTest.
 *
 * @covers \PhpUnitGen\Console\Reporters\JsonReporter
 * @covers \PhpUnitGen\Console\Reporters\AbstractReporter
 * @covers \PhpUnitGen\Console\Commands\HasOutput
 */
class JsonReporterTest extends TestCase
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
     * @var JsonReporter
     */
    protected $jsonReporter;

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

        $this->jsonReporter = new JsonReporter($this->output);
        $this->jsonReporter->withStopwatch($this->stopwatch);
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

        $this->jsonReporter->onCriticalError($exception);

        $this->assertTrue($dumped);
    }

    public function testItWriteJsonReport(): void
    {
        $this->output->shouldReceive('isQuiet')->withNoArgs()->andReturnFalse();
        $this->output->shouldReceive('isVeryVerbose')->withNoArgs()->andReturnFalse();

        $this->jsonReporter->onStart(ConsoleConfig::make(), new Collection([
            'foo',
            'bar',
            'baz',
        ]));

        $this->jsonReporter->onSuccess('foo', 'fooTest');
        $this->jsonReporter->onWarning('bar', 'bar warning');
        $this->jsonReporter->onError('baz', new Exception('baz exception'));

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
            ->with(json_encode([
                'summary' => [
                    'sources'   => 3,
                    'successes' => 1,
                    'warnings'  => 1,
                    'errors'    => 1,
                    'time'      => 3500,
                    'memory'    => 78643200,
                ],
                'results' => [
                    'foo' => [
                        'status'  => 'success',
                        'path'    => 'fooTest',
                        'message' => 'Successful generation',
                    ],
                    'bar' => [
                        'status'  => 'warning',
                        'message' => 'bar warning',
                    ],
                    'baz' => [
                        'status'  => 'error',
                        'message' => 'baz exception',
                    ],
                ],
            ], JSON_PRETTY_PRINT), true);

        $this->assertSame(100, $this->jsonReporter->terminate());
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

        $this->jsonReporter->onStart(ConsoleConfig::make(), new Collection());

        $this->assertSame(0, $this->jsonReporter->terminate());

        $this->jsonReporter->onWarning('foo', 'bar');

        $this->assertSame(101, $this->jsonReporter->terminate());

        $this->jsonReporter->onError('foo', new Exception('bar'));

        $this->assertSame(100, $this->jsonReporter->terminate());
    }
}
