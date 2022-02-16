<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Files;

/**
 * Class SourcesResolver.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
trait CleansWindowsPaths
{
    /**
     * Cleans a path by replacing "\" with "/" and Windows disks letter.
     *
     * @param string $path
     *
     * @return string
     */
    protected function convertPotentialWindowsPath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
