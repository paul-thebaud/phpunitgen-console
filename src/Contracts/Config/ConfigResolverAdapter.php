<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Contracts\Config;

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
     */
    public function resolve(string $content): array;
}
