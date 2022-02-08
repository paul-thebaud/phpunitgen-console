<?php

declare(strict_types=1);

namespace Tests\PhpUnitGen\Console\Unit\Files;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use Mockery;
use Mockery\Mock;
use PhpUnitGen\Console\Files\CleansWindowsPaths;
use PhpUnitGen\Console\Files\LeagueFilesystem;
use PhpUnitGen\Core\Exceptions\InvalidArgumentException;
use Tests\PhpUnitGen\Console\TestCase;

/**
 * Class LeagueFilesystemTest.
 *
 * @covers \PhpUnitGen\Console\Files\CleansWindowsPaths
 * @covers \PhpUnitGen\Console\Files\LeagueFilesystem
 */
class LeagueFilesystemTest extends TestCase
{
    use CleansWindowsPaths;

    /**
     * @var FilesystemOperator|Mock
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

        $this->filesystem = Mockery::mock(FilesystemOperator::class);
        $this->leagueFilesystem = new LeagueFilesystem($this->filesystem, '/john');
    }

    public function testItMakesANewInstance(): void
    {
        $leagueFilesystem = LeagueFilesystem::make();

        $this->assertSame(
            $this->convertPotentialWindowsPath(getcwd()).'/',
            $leagueFilesystem->getRoot()
        );
    }

    public function testItCleansWindowsPaths(): void
    {
        $this->assertSame(
            $this->convertPotentialWindowsPath('C:\\Test\\Path\\To\\File'),
            '/Test/Path/To/File'
        );

        $this->assertSame(
            $this->convertPotentialWindowsPath('Path\\To\\File'),
            'Path/To/File'
        );
    }

    public function testItForwardsHas(): void
    {
        $this->filesystem->shouldReceive('has')->once()
            ->with('/john/app/file')
            ->andReturnTrue();

        $this->assertTrue($this->leagueFilesystem->has('app/file'));
    }

    public function testItForwardsFileExists(): void
    {
        $this->filesystem->shouldReceive('fileExists')->once()
            ->with('/john/app/file')
            ->andReturnTrue();

        $this->assertTrue($this->leagueFilesystem->isFile('app/file'));
    }

    public function testItForwardsDirectoryExists(): void
    {
        $this->filesystem->shouldReceive('directoryExists')->once()
            ->with('/john/app/file')
            ->andReturnTrue();

        $this->assertTrue($this->leagueFilesystem->isDirectory('app/file'));
    }

    public function testItListsFiles(): void
    {
        $this->filesystem->shouldReceive('listContents')->once()
            ->with('/john/app/dir', true)
            ->andReturn(new DirectoryListing([
                new DirectoryAttributes('john/app/services'),
                new FileAttributes('john/app/file1'),
                new FileAttributes('john/app/services/file2'),
            ]));

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
            ->andThrow(new UnableToReadFile('/john/app/file_not_found'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('file not found: app/file_not_found');

        $this->assertSame('content', $this->leagueFilesystem->read('app/file_not_found'));
    }

    public function testItForwardsWriteWithoutExistingDirectory(): void
    {
        $this->filesystem->shouldReceive('directoryExists')->once()
            ->with('/john/app/file')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('write')->once()
            ->with('/john/app/file', 'content');

        $this->leagueFilesystem->write('app/file', 'content');
    }

    public function testItForwardsWriteWithExistingDirectory(): void
    {
        $this->filesystem->shouldReceive('directoryExists')->once()
            ->with('/john/app/file')
            ->andReturnTrue();

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
        $this->filesystem->shouldReceive('move')->once()
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

    public function testItIsCompatibleWithWindowsPaths(): void
    {
        $windowsLeagueFilesystemC = new LeagueFilesystem($this->filesystem,
            'C:\Users\John Doe\Documents');

        $this->filesystem->shouldReceive('has')->once()
            ->with('/Users/John Doe/Documents/app/file')
            ->andReturnTrue();

        $this->assertTrue($windowsLeagueFilesystemC->has('app\file'));

        $windowsLeagueFilesystemD = new LeagueFilesystem(
            $this->filesystem,
            'D:\Users\John Doe\Documents'
        );

        $this->filesystem->shouldReceive('has')->once()
            ->with('/app/file')
            ->andReturnTrue();

        $this->assertTrue($windowsLeagueFilesystemD->has('C:\app\file'));
    }
}
