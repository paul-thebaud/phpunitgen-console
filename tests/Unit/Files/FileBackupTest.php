<?php

declare(strict_types=1);

namespace Tests\PhpUnitGen\Console\Unit\Files;

use Mockery;
use Mockery\Mock;
use PhpUnitGen\Console\Contracts\Files\Filesystem;
use PhpUnitGen\Console\Files\FileBackup;
use Tests\PhpUnitGen\Console\TestCase;

/**
 * Class FileBackupTest.
 *
 * @covers \PhpUnitGen\Console\Files\FileBackup
 */
class FileBackupTest extends TestCase
{
    /**
     * @var Filesystem|Mock
     */
    protected $filesystem;

    /**
     * @var FileBackup
     */
    protected $fileBackup;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = Mockery::mock(Filesystem::class);
        $this->fileBackup = new FileBackup($this->filesystem);
    }

    public function testItRenamesWithoutExisting(): void
    {
        $this->filesystem->shouldReceive('has')->once()
            ->with('test.php.bak')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('rename')->once()
            ->with('test.php', 'test.php.bak');

        $this->fileBackup->backup('test.php');
    }

    public function testItRenamesWithOneExisting(): void
    {
        $this->filesystem->shouldReceive('has')->once()
            ->with('test.php.bak')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('has')->once()
            ->with('test.php.1.bak')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('rename')->once()
            ->with('test.php', 'test.php.1.bak');

        $this->fileBackup->backup('test.php');
    }

    public function testItRenamesWithManyExisting(): void
    {
        $this->filesystem->shouldReceive('has')->once()
            ->with('test.php.bak')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('has')->once()
            ->with('test.php.1.bak')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('has')->once()
            ->with('test.php.2.bak')
            ->andReturnTrue();
        $this->filesystem->shouldReceive('has')->once()
            ->with('test.php.3.bak')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('rename')->once()
            ->with('test.php', 'test.php.3.bak');

        $this->fileBackup->backup('test.php');
    }
}
