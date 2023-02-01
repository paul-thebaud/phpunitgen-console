<?php

declare(strict_types=1);

namespace Tests\PhpUnitGen\Console\Unit\Reporters;

use Mockery;
use Mockery\Mock;
use PhpUnitGen\Console\Reporters\JsonReporter;
use PhpUnitGen\Console\Reporters\ReporterFactory;
use PhpUnitGen\Console\Reporters\TextReporter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\PhpUnitGen\Console\TestCase;

/**
 * Class ReporterFactoryTest.
 *
 * @covers \PhpUnitGen\Console\Reporters\ReporterFactory
 */
class ReporterFactoryTest extends TestCase
{
    /**
     * @var InputInterface|Mock
     */
    protected $input;

    /**
     * @var OutputInterface|Mock
     */
    protected $output;

    /**
     * @var ReporterFactory
     */
    protected $reporterFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->input = Mockery::mock(InputInterface::class);
        $this->output = Mockery::mock(OutputInterface::class);

        $this->reporterFactory = new ReporterFactory();
    }

    public function testItUsesTextReporter(): void
    {
        $this->input->shouldReceive('getOption')
            ->once()
            ->with('json')
            ->andReturnFalse();

        $this->assertInstanceOf(
            TextReporter::class,
            $this->reporterFactory->makeReporter($this->input, $this->output),
        );
    }

    public function testItUsesJsonReporter(): void
    {
        $this->input->shouldReceive('getOption')
            ->once()
            ->with('json')
            ->andReturnTrue();

        $this->assertInstanceOf(
            JsonReporter::class,
            $this->reporterFactory->makeReporter($this->input, $this->output),
        );
    }
}
