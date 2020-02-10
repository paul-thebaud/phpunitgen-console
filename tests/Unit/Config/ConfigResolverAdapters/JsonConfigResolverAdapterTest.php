<?php

declare(strict_types=1);

namespace Tests\PhpUnitGen\Console\Unit\Config\ConfigResolverAdapters;

use PhpUnitGen\Console\Config\ConfigResolverAdapters\JsonConfigResolverAdapter;
use PhpUnitGen\Core\Exceptions\InvalidArgumentException;
use Tests\PhpUnitGen\Console\TestCase;

/**
 * Class JsonConfigResolverAdapterTest.
 *
 * @covers \PhpUnitGen\Console\Config\ConfigResolverAdapters\JsonConfigResolverAdapter
 */
class JsonConfigResolverAdapterTest extends TestCase
{
    public function testItResolvesConfig(): void
    {
        $adapter = new JsonConfigResolverAdapter();

        $this->assertSame([
            'backupFiles' => true,
        ], $adapter->resolve('{"backupFiles":true}'));
    }

    public function testItThrowsAnException(): void
    {
        $adapter = new JsonConfigResolverAdapter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid JSON configuration content');

        $adapter->resolve('');
    }
}
