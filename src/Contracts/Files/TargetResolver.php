<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Contracts\Files;

/**
 * Interface TargetResolver.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
interface TargetResolver
{
    /**
     * Resolve the target path for the given source path.
     *
     * @param string $sourcePath
     * @param string $targetPath
     *
     * @return string
     */
    public function resolve(string $sourcePath, string $targetPath): string;
}
