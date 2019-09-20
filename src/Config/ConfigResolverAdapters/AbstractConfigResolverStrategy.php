<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Config\ConfigResolverAdapters;

use PhpUnitGen\Console\Config\ConsoleConfig;
use PhpUnitGen\Console\Contracts\Config\ConfigResolverAdapter;
use PhpUnitGen\Core\Exceptions\InvalidArgumentException;

/**
 * Class AbstractConfigResolverStrategy.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
abstract class AbstractConfigResolverStrategy implements ConfigResolverAdapter
{
    /**
     * Check if config is an array and make the instance.
     *
     * @param mixed $config
     *
     * @return ConsoleConfig
     *
     * @throws InvalidArgumentException
     */
    protected function makeConfig($config): ConsoleConfig
    {
        if (! is_array($config)) {
            throw new InvalidArgumentException(
                'given config does not contains an associative array'
            );
        }

        return ConsoleConfig::make($config);
    }
}
