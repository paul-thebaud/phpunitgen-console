<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Contracts\Config;

use PhpUnitGen\Core\Contracts\Config\Config;
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
     * Resolve the configuration from its path. Throw an exception if the given
     * config path does not exists or is invalid. Return default configuration
     * if path is null or empty and no config can be found in current path.
     *
     * @param string|null $path
     *
     * @return Config
     *
     * @throws InvalidArgumentException
     */
    public function resolve(?string $path = null): Config;
}
