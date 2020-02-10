<?php

declare(strict_types=1);

namespace Tests\PhpUnitGen\Console\Unit\Config\ConfigResolverAdapters;

use PhpUnitGen\Console\Config\ConfigResolverAdapters\YamlConfigResolverAdapter;
use PhpUnitGen\Core\Exceptions\InvalidArgumentException;
use Tests\PhpUnitGen\Console\TestCase;

/**
 * Class YamlConfigResolverAdapterTest.
 *
 * @covers \PhpUnitGen\Console\Config\ConfigResolverAdapters\YamlConfigResolverAdapter
 */
class YamlConfigResolverAdapterTest extends TestCase
{
    public function testItResolvesConfig(): void
    {
        $adapter = new YamlConfigResolverAdapter();

        $this->assertSame([
            'backupFiles' => true,
        ], $adapter->resolve('backupFiles: true'));
    }

    public function testItThrowsAnException(): void
    {
        $adapter = new YamlConfigResolverAdapter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid Yaml configuration content');

        $adapter->resolve('');
    }
}
