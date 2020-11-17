<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Contracts\Files;

use PhpUnitGen\Console\Contracts\Config\ConsoleConfig;
use PhpUnitGen\Core\Exceptions\InvalidArgumentException;
use Tightenco\Collect\Support\Collection;

/**
 * Interface SourcesResolver.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
interface SourcesResolver
{
    /**
     * Resolve the collection of absolute source paths from the given one. Throw an
     * exception if there is no source in the given source path.
     *
     * Returned paths must be using the "/" char as dir separator.
     * For absolute paths, each must start with "/" too (no "C:\" like on Windows).
     *
     * @param ConsoleConfig $config
     * @param string        $sourcePath
     *
     * @return Collection
     *
     * @throws InvalidArgumentException
     */
    public function resolve(ConsoleConfig $config, string $sourcePath): Collection;
}
