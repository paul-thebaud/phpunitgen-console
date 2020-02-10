<?php

declare(strict_types=1);

namespace Tests\PhpUnitGen\Console\Unit\Config\ConfigResolverAdapters;

use PhpUnitGen\Console\Config\ConfigResolverAdapters\PhpConfigResolverAdapter;
use PhpUnitGen\Core\Exceptions\InvalidArgumentException;
use Tests\PhpUnitGen\Console\TestCase;

/**
 * Class PhpConfigResolverAdapterTest.
 *
 * @covers \PhpUnitGen\Console\Config\ConfigResolverAdapters\PhpConfigResolverAdapter
 */
class PhpConfigResolverAdapterTest extends TestCase
{
    public function testItResolvesConfig(): void
    {
        $adapter = new PhpConfigResolverAdapter();

        $this->assertSame([
            'backupFiles' => true,
        ], $adapter->resolve('<?php return ["backupFiles" => true];'));
    }

    public function testItThrowsAnException(): void
    {
        $adapter = new PhpConfigResolverAdapter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid PHP configuration content');

        $adapter->resolve('');
    }
}
