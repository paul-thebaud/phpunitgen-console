<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Config\ResolveStrategies;

use PhpUnitGen\Console\Contracts\Config\ResolveStrategy;
use PhpUnitGen\Core\Config\Config;
use PhpUnitGen\Core\Exceptions\InvalidArgumentException;

/**
 * Class AbstractResolveStrategy.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
abstract class AbstractResolveStrategy implements ResolveStrategy
{
    /**
     * Check if config is an array and make the instance.
     *
     * @param mixed $config
     *
     * @return Config
     */
    protected function makeConfig($config): Config
    {
        if (! is_array($config)) {
            throw new InvalidArgumentException(
                'given config does not contains an associative array'
            );
        }

        return Config::make($config);
    }
}
