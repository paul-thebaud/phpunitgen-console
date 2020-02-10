<?php

declare(strict_types=1);

namespace Tests\PhpUnitGen\Console\Unit\Config;

use Mockery;
use Mockery\Mock;
use PhpUnitGen\Console\Config\ConfigResolver;
use PhpUnitGen\Console\Config\ConfigResolverAdapters\JsonConfigResolverAdapter;
use PhpUnitGen\Console\Config\ConfigResolverAdapters\PhpConfigResolverAdapter;
use PhpUnitGen\Console\Config\ConfigResolverAdapters\YamlConfigResolverAdapter;
use PhpUnitGen\Console\Config\ConsoleConfig;
use PhpUnitGen\Console\Contracts\Files\Filesystem;
use PhpUnitGen\Core\Exceptions\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Tests\PhpUnitGen\Console\TestCase;

/**
 * Class ConfigResolverTest.
 *
 * @covers \PhpUnitGen\Console\Config\ConfigResolver
 */
class ConfigResolverTest extends TestCase
{
    /**
     * @var Filesystem|Mock
     */
    protected $filesystem;

    /**
     * @var PhpConfigResolverAdapter|Mock
     */
    protected $phpConfigResolverAdapter;

    /**
     * @var YamlConfigResolverAdapter|Mock
     */
    protected $yamlConfigResolverAdapter;

    /**
     * @var JsonConfigResolverAdapter|Mock
     */
    protected $jsonConfigResolverAdapter;

    /**
     * @var ConfigResolver
     */
    protected $configResolver;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = Mockery::mock(Filesystem::class);
        $this->phpConfigResolverAdapter = Mockery::mock(PhpConfigResolverAdapter::class);
        $this->yamlConfigResolverAdapter = Mockery::mock(YamlConfigResolverAdapter::class);
        $this->jsonConfigResolverAdapter = Mockery::mock(JsonConfigResolverAdapter::class);
        $this->configResolver = new ConfigResolver(
            $this->filesystem,
            $this->phpConfigResolverAdapter,
            $this->yamlConfigResolverAdapter,
            $this->jsonConfigResolverAdapter
        );
    }

    public function testItResolveDefaultWhenNoInputAndNotFound(): void
    {
        $this->filesystem->shouldReceive('has')->once()
            ->with('phpunitgen.php')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('has')->once()
            ->with('phpunitgen.php.dist')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('has')->once()
            ->with('phpunitgen.yml')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('has')->once()
            ->with('phpunitgen.yml.dist')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('has')->once()
            ->with('phpunitgen.json')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('has')->once()
            ->with('phpunitgen.json.dist')
            ->andReturnFalse();

        $config = $this->configResolver->resolve();

        $this->assertNull($config->getPath());
        $this->assertSame(ConsoleConfig::make()->toArray(), $config->toArray());
    }

    public function testItResolveDefaultWhenInputWithoutOptionAndNotFound(): void
    {
        $this->filesystem->shouldReceive('has')->once()
            ->with('phpunitgen.php')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('has')->once()
            ->with('phpunitgen.php.dist')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('has')->once()
            ->with('phpunitgen.yml')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('has')->once()
            ->with('phpunitgen.yml.dist')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('has')->once()
            ->with('phpunitgen.json')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('has')->once()
            ->with('phpunitgen.json.dist')
            ->andReturnFalse();

        $input = Mockery::mock(InputInterface::class);
        $input->shouldReceive('getOption')->once()
            ->with('config')
            ->andReturn(null);
        $input->shouldReceive('getOption')->once()
            ->with('overwrite')
            ->andReturn(null);

        $config = $this->configResolver->resolve($input);

        $this->assertNull($config->getPath());
        $this->assertSame(ConsoleConfig::make()->toArray(), $config->toArray());
    }

    public function testItResolveDefaultWhenNoInputWithDefaultFound(): void
    {
        $this->filesystem->shouldReceive('has')->once()
            ->with('phpunitgen.php')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('has')->once()
            ->with('phpunitgen.php.dist')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('has')->once()
            ->with('phpunitgen.yml')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('has')->once()
            ->with('phpunitgen.yml.dist')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('read')->once()
            ->with('phpunitgen.yml.dist')
            ->andReturn('valid file content');

        $this->yamlConfigResolverAdapter->shouldReceive('resolve')->once()
            ->with('valid file content')
            ->andReturn(['backupFiles' => false]);

        $config = $this->configResolver->resolve();

        $this->assertSame('phpunitgen.yml.dist', $config->getPath());
        $this->assertFalse($config->backupFiles());
    }

    public function testItResolveDefaultWhenInputWithPhpConfigAndOverwriteOption(): void
    {
        $this->filesystem->shouldReceive('read')->once()
            ->with('custom.php')
            ->andReturn('valid file content');

        $input = Mockery::mock(InputInterface::class);
        $input->shouldReceive('getOption')->once()
            ->with('config')
            ->andReturn('custom.php');
        $input->shouldReceive('getOption')->once()
            ->with('overwrite')
            ->andReturn(true);

        $this->phpConfigResolverAdapter->shouldReceive('resolve')->once()
            ->with('valid file content')
            ->andReturn(['overwriteFiles' => false, 'backupFiles' => false]);

        $config = $this->configResolver->resolve($input);

        $this->assertSame('custom.php', $config->getPath());
        $this->assertTrue($config->overwriteFiles());
        $this->assertFalse($config->backupFiles());
    }

    public function testItResolveDefaultWhenInputWithYamlConfigAndOverwriteOption(): void
    {
        $this->filesystem->shouldReceive('read')->once()
            ->with('custom.yml')
            ->andReturn('valid file content');

        $input = Mockery::mock(InputInterface::class);
        $input->shouldReceive('getOption')->once()
            ->with('config')
            ->andReturn('custom.yml');
        $input->shouldReceive('getOption')->once()
            ->with('overwrite')
            ->andReturn(true);

        $this->yamlConfigResolverAdapter->shouldReceive('resolve')->once()
            ->with('valid file content')
            ->andReturn(['overwriteFiles' => false, 'backupFiles' => false]);

        $config = $this->configResolver->resolve($input);

        $this->assertSame('custom.yml', $config->getPath());
        $this->assertTrue($config->overwriteFiles());
        $this->assertFalse($config->backupFiles());
    }

    public function testItResolveDefaultWhenInputWithJsonConfigAndOverwriteOption(): void
    {
        $this->filesystem->shouldReceive('read')->once()
            ->with('custom.json')
            ->andReturn('valid file content');

        $input = Mockery::mock(InputInterface::class);
        $input->shouldReceive('getOption')->once()
            ->with('config')
            ->andReturn('custom.json');
        $input->shouldReceive('getOption')->once()
            ->with('overwrite')
            ->andReturn(true);

        $this->jsonConfigResolverAdapter->shouldReceive('resolve')->once()
            ->with('valid file content')
            ->andReturn(['overwriteFiles' => false, 'backupFiles' => false]);

        $config = $this->configResolver->resolve($input);

        $this->assertSame('custom.json', $config->getPath());
        $this->assertTrue($config->overwriteFiles());
        $this->assertFalse($config->backupFiles());
    }

    public function testItResolveDefaultWhenInputWithPhpDistConfig(): void
    {
        $this->filesystem->shouldReceive('read')->once()
            ->with('custom.php.dist')
            ->andReturn('valid file content');

        $input = Mockery::mock(InputInterface::class);
        $input->shouldReceive('getOption')->once()
            ->with('config')
            ->andReturn('custom.php.dist');
        $input->shouldReceive('getOption')->once()
            ->with('overwrite')
            ->andReturn(false);

        $this->phpConfigResolverAdapter->shouldReceive('resolve')->once()
            ->with('valid file content')
            ->andReturn([]);

        $config = $this->configResolver->resolve($input);

        $this->assertSame('custom.php.dist', $config->getPath());
        $this->assertFalse($config->overwriteFiles());
        $this->assertTrue($config->backupFiles());
    }

    public function testItThrowsWhenInvalidExtensionIsGiven(): void
    {
        $input = Mockery::mock(InputInterface::class);
        $input->shouldReceive('getOption')->once()
            ->with('config')
            ->andReturn('custom.invalid_ext');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'config file must have one of the following extensions: php, yml, json'
        );

        $config = $this->configResolver->resolve($input);

        $this->assertSame('custom.json', $config->getPath());
        $this->assertTrue($config->overwriteFiles());
        $this->assertFalse($config->backupFiles());
    }
}
