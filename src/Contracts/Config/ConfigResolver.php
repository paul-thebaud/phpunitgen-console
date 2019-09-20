<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Contracts\Config;

use PhpUnitGen\Core\Exceptions\InvalidArgumentException;

/**
 * Interface ConfigResolver.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
interface ConfigResolver
{
    /**
     * Try to resolve the given config from its path, or resolve defaults.
     *
     * @param string|null $path
     *
     * @return ConsoleConfig
     *
     * @throws InvalidArgumentException
     */
    public function resolve(?string $path): ConsoleConfig;
}
