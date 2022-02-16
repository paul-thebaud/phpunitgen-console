<?php

declare(strict_types=1);

namespace Tests\PhpUnitGen\Console\Unit\Files;

use Mockery;
use Mockery\Mock;
use PhpUnitGen\Console\Config\ConsoleConfig;
use PhpUnitGen\Console\Contracts\Files\Filesystem;
use PhpUnitGen\Console\Files\SourcesResolver;
use PhpUnitGen\Core\Exceptions\InvalidArgumentException;
use Tests\PhpUnitGen\Console\TestCase;
use Tightenco\Collect\Support\Collection;

/**
 * Class SourcesResolverTest.
 *
 * @covers \PhpUnitGen\Console\Files\SourcesResolver
 */
class SourcesResolverTest extends TestCase
{
    /**
     * @var Filesystem|Mock
     */
    protected $filesystem;

    /**
     * @var SourcesResolver
     */
    protected $sourcesResolver;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = Mockery::mock(Filesystem::class);
        $this->sourcesResolver = new SourcesResolver($this->filesystem);
    }

    public function testItReturnsOneSourceWhenFileIsGiven(): void
    {
        $config = ConsoleConfig::make();

        $this->filesystem->shouldReceive('isFile')->once()
            ->with('source.php')
            ->andReturnTrue();

        $this->assertSame([
            'source.php',
        ], $this->sourcesResolver->resolve($config, 'source.php')->toArray());
    }

    public function testItReturnsOneCorrectSourceWhenWindowsFileIsGiven(): void
    {
        $config = ConsoleConfig::make();

        $this->filesystem->shouldReceive('isFile')->once()
            ->with('C:\path\to\project\source.php')
            ->andReturnTrue();

        $this->assertSame([
            'C:/path/to/project/source.php',
        ], $this->sourcesResolver->resolve($config, 'C:\path\to\project\source.php')->toArray());
    }

    public function testItReturnsFilteredSourcesWhenDirIsGiven(): void
    {
        $config = ConsoleConfig::make([
            'excludedFiles' => [
                'config',
                '.*\.blade\.php$',
            ],
            'includedFiles' => [
                'php',
                '.*\.phtml$',
            ],
        ]);

        $this->filesystem->shouldReceive('isFile')->once()
            ->with('app')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('listFiles')->once()
            ->with('app')
            ->andReturn(new Collection([
                'app/config/example.php',
                'app/views/example.blade.php',
                'app/services/example.php',
                'app/views/example.phtml',
                'app/bootstrap.php',
                'app/config.json',
            ]));

        $this->assertSame([
            'app/services/example.php',
            'app/views/example.phtml',
            'app/bootstrap.php',
        ], $this->sourcesResolver->resolve($config, 'app')->toArray());
    }

    public function testItThrowsWhenNoFilesMatchedInDir(): void
    {
        $config = ConsoleConfig::make([
            'excludedFiles' => [
                'config',
                '.*\.blade\.php$',
            ],
        ]);

        $this->filesystem->shouldReceive('isFile')->once()
            ->with('app')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('listFiles')->once()
            ->with('app')
            ->andReturn(new Collection([
                'app/config/example.php',
                'app/views/example.blade.php',
            ]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no source to generate tests for');

        $this->sourcesResolver->resolve($config, 'app');
    }
}
