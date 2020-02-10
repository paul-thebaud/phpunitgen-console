<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Contracts\Config;

use PhpUnitGen\Core\Exceptions\InvalidArgumentException;

/**
 * Interface ConfigResolverAdapter.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
interface ConfigResolverAdapter
{
    /**
     * Resolve the config instance from the given string content.
     *
     * @param string $content
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function resolve(string $content): array;
}
