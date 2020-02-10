<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Files;

use PhpUnitGen\Console\Contracts\Files\FileBackup as FileBackupContract;
use PhpUnitGen\Console\Contracts\Files\Filesystem;

/**
 * Class FileBackup.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class FileBackup implements FileBackupContract
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * TargetResolver constructor.
     *
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function backup(string $filePath): void
    {
        $existingCount = 1;
        $backupPath = $this->getBackupPath($filePath);
        while ($this->filesystem->has($backupPath)) {
            $backupPath = $this->getBackupPath($filePath.'.'.$existingCount);
            $existingCount++;
        }

        $this->filesystem->rename($filePath, $backupPath);
    }

    /**
     * Get the name to use for backup file version.
     *
     * @param string $filePath
     *
     * @return string
     */
    protected function getBackupPath(string $filePath): string
    {
        return $filePath.'.bak';
    }
}
