<?php

declare(strict_types=1);

namespace Tests\PhpUnitGen\Console\Unit\Files;

use League\Flysystem\Adapter\Local;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use Mockery;
use Mockery\Mock;
use PhpUnitGen\Console\Files\LeagueFilesystem;
use PhpUnitGen\Core\Exceptions\InvalidArgumentException;
use Tests\PhpUnitGen\Console\TestCase;

/**
 * Class LeagueFilesystemTest.
 *
 * @covers \PhpUnitGen\Console\Files\LeagueFilesystem
 */
class LeagueFilesystemTest extends TestCase
{
    /**
     * @var FilesystemInterface|Mock
     */
    protected $filesystem;

    /**
     * @var LeagueFilesystem
     */
    protected $leagueFilesystem;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = Mockery::mock(FilesystemInterface::class);
        $this->leagueFilesystem = new LeagueFilesystem($this->filesystem, '/john');
    }

    public function testItMakesANewInstance(): void
    {
        $leagueFilesystem = LeagueFilesystem::make();

        $this->assertSame(getcwd().'/', $leagueFilesystem->getRoot());

        /** @var Filesystem $filesystem */
        $filesystem = $leagueFilesystem->getFilesystem();

        $this->assertInstanceOf(Filesystem::class, $filesystem);

        /** @var Local $adapter */
        $adapter = $filesystem->getAdapter();

        $this->assertInstanceOf(Local::class, $adapter);
        $this->assertSame('/', $adapter->getPathPrefix());
    }

    public function testItForwardsHas(): void
    {
        $this->filesystem->shouldReceive('has')->once()
            ->with('/john/app/file')
            ->andReturnTrue();

        $this->assertTrue($this->leagueFilesystem->has('app/file'));
    }

    public function testItChecksIsFile(): void
    {
        $this->filesystem->shouldReceive('getMetadata')->once()
            ->with('/john/app/file1')
            ->andReturn(['type' => 'file']);
        $this->filesystem->shouldReceive('getMetadata')->once()
            ->with('/john/app/file2')
            ->andReturn(['type' => 'dir']);
        $this->filesystem->shouldReceive('getMetadata')->once()
            ->with('/john/app/file3')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('getMetadata')->once()
            ->with('/john/app/file4')
            ->andReturn([]);
        $this->filesystem->shouldReceive('getMetadata')->once()
            ->with('/john/app/file5')
            ->andThrow(new FileNotFoundException('/john/app/file5'));

        $this->assertTrue($this->leagueFilesystem->isFile('app/file1'));
        $this->assertFalse($this->leagueFilesystem->isFile('app/file2'));
        $this->assertFalse($this->leagueFilesystem->isFile('app/file3'));
        $this->assertFalse($this->leagueFilesystem->isFile('app/file4'));
        $this->assertFalse($this->leagueFilesystem->isFile('app/file5'));
    }

    public function testItChecksIsDirectory(): void
    {
        $this->filesystem->shouldReceive('getMetadata')->once()
            ->with('/john/app/dir1')
            ->andReturn(['type' => 'dir']);
        $this->filesystem->shouldReceive('getMetadata')->once()
            ->with('/john/app/dir2')
            ->andReturn(['type' => 'file']);
        $this->filesystem->shouldReceive('getMetadata')->once()
            ->with('/john/app/dir3')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('getMetadata')->once()
            ->with('/john/app/dir4')
            ->andReturn([]);
        $this->filesystem->shouldReceive('getMetadata')->once()
            ->with('/john/app/dir5')
            ->andThrow(new FileNotFoundException('/john/app/dir5'));

        $this->assertTrue($this->leagueFilesystem->isDirectory('app/dir1'));
        $this->assertFalse($this->leagueFilesystem->isDirectory('app/dir2'));
        $this->assertFalse($this->leagueFilesystem->isDirectory('app/dir3'));
        $this->assertFalse($this->leagueFilesystem->isDirectory('app/dir4'));
        $this->assertFalse($this->leagueFilesystem->isDirectory('app/dir5'));
    }

    public function testItListsFiles(): void
    {
        $this->filesystem->shouldReceive('listContents')->once()
            ->with('/john/app/dir', true)
            ->andReturn([
                ['type' => 'dir', 'path' => 'john/app/services'],
                ['type' => 'file', 'path' => 'john/app/file1'],
                ['type' => 'file', 'path' => 'john/app/services/file2'],
            ]);

        $this->assertSame([
            '/john/app/file1',
            '/john/app/services/file2',
        ], $this->leagueFilesystem->listFiles('app/dir')->toArray());
    }

    public function testItForwardsReadAndHandleException(): void
    {
        $this->filesystem->shouldReceive('read')->once()
            ->with('/john/app/file')
            ->andReturn('content');

        $this->assertSame('content', $this->leagueFilesystem->read('app/file'));

        $this->filesystem->shouldReceive('read')->once()
            ->with('/john/app/file_not_found')
            ->andThrow(new FileNotFoundException('/john/app/file_not_found'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('file not found: app/file_not_found');

        $this->assertSame('content', $this->leagueFilesystem->read('app/file_not_found'));
    }

    public function testItForwardsWriteWithExistingFile(): void
    {
        $this->filesystem->shouldReceive('getMetadata')->once()
            ->with('/john/app/file')
            ->andReturn(['type' => 'file']);
        $this->filesystem->shouldReceive('has')->once()
            ->with('/john/app/file')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('update')->once()
            ->with('/john/app/file', 'content');

        $this->leagueFilesystem->write('app/file', 'content');
    }

    public function testItForwardsWriteWithNonExistingFile(): void
    {
        $this->filesystem->shouldReceive('getMetadata')->once()
            ->with('/john/app/file')
            ->andThrow(new FileNotFoundException('/john/app/file'));
        $this->filesystem->shouldReceive('has')->once()
            ->with('/john/app/file')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('write')->once()
            ->with('/john/app/file', 'content');

        $this->leagueFilesystem->write('app/file', 'content');
    }

    public function testItForwardsWriteWithExistingDirectory(): void
    {
        $this->filesystem->shouldReceive('getMetadata')->once()
            ->with('/john/app/file')
            ->andReturn(['type' => 'dir']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot write file because directory with same name exists: app/file');

        $this->leagueFilesystem->write('app/file', 'content');
    }

    public function testItForwardsRenameWithValidArguments(): void
    {
        $this->filesystem->shouldReceive('has')->once()
            ->with('/john/app/source')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('has')->once()
            ->with('/john/app/target')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('rename')->once()
            ->with('/john/app/source', '/john/app/target');

        $this->leagueFilesystem->rename('app/source', 'app/target');
    }

    public function testItForwardsRenameWithInvalidSource(): void
    {
        $this->filesystem->shouldReceive('has')->once()
            ->with('/john/app/source')
            ->andReturnFalse();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot rename not found app/source');

        $this->leagueFilesystem->rename('app/source', 'app/target');
    }

    public function testItForwardsRenameWithInvalidTarget(): void
    {
        $this->filesystem->shouldReceive('has')->once()
            ->with('/john/app/source')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('has')->once()
            ->with('/john/app/target')
            ->andReturnTrue();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot rename to existing app/target');

        $this->leagueFilesystem->rename('app/source', 'app/target');
    }

    public function testItComputeAbsolutePathCorrectly(): void
    {
        $this->filesystem->shouldReceive('has')->once()
            ->with('/john/app/file')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('has')->once()
            ->with('/app/file')
            ->andReturnFalse();

        $this->assertTrue($this->leagueFilesystem->has('app/file'));
        $this->assertFalse($this->leagueFilesystem->has('/app/file'));
    }
}
