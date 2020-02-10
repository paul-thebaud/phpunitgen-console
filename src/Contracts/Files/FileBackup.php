<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Contracts\Files;

/**
 * Interface FileBackup.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
interface FileBackup
{
    /**
     * Backup an existing file.
     *
     * @param string $filePath
     */
    public function backup(string $filePath): void;
}
