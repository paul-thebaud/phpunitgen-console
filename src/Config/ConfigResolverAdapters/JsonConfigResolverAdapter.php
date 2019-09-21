<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Config\ConfigResolverAdapters;

use PhpUnitGen\Console\Contracts\Config\ConfigResolverAdapter;

/**
 * Class JsonConfigResolverAdapter.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class JsonConfigResolverAdapter implements ConfigResolverAdapter
{
    /**
     * {@inheritdoc}
     */
    public function resolve(string $content): array
    {
        return json_decode($content, true);
    }
}
