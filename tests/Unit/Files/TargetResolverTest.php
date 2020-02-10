<?php

declare(strict_types=1);

namespace Tests\PhpUnitGen\Console\Unit\Files;

use Mockery;
use Mockery\Mock;
use PhpUnitGen\Console\Contracts\Files\Filesystem;
use PhpUnitGen\Console\Files\TargetResolver;
use PhpUnitGen\Core\Generators\Factories\ClassFactory;
use Tests\PhpUnitGen\Console\TestCase;

/**
 * Class TargetResolverTest.
 *
 * @covers \PhpUnitGen\Console\Files\TargetResolver
 */
class TargetResolverTest extends TestCase
{
    /**
     * @var Filesystem|Mock
     */
    protected $filesystem;

    /**
     * @var TargetResolver
     */
    protected $targetResolver;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = Mockery::mock(Filesystem::class);
        $this->targetResolver = new TargetResolver($this->filesystem);
    }

    /**
     * @param string $expected
     * @param string $testSubNamespace
     * @param string $sourcePath
     * @param string $targetPath
     *
     * @dataProvider targetResolverDataProvider
     */
    public function testItResolvesTargetPath(
        string $expected,
        string $testSubNamespace,
        string $sourcePath,
        string $targetPath
    ): void {
        $this->filesystem->shouldReceive('getRoot')->once()
            ->withNoArgs()
            ->andReturn('john/');

        $classFactory = Mockery::mock(ClassFactory::class);
        $classFactory->shouldReceive('getTestSubNamespace')
            ->withNoArgs()
            ->andReturn($testSubNamespace);

        $this->assertSame($expected, $this->targetResolver->resolve($classFactory, $sourcePath, $targetPath));
    }

    public function targetResolverDataProvider(): array
    {
        return [
            ['tests/Models/PostTest.php', '', 'john/app/Models/Post.php', 'tests'],
            ['/my_tests/PostTest.php', '', '/john/Post.php', '/my_tests/'],
            ['tests/Unit/Models/PostTest.php', 'Unit', 'app/Models/Post.php', 'tests'],
        ];
    }
}
