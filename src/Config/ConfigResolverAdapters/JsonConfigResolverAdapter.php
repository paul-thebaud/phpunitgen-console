<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Config\ConfigResolverAdapters;

use PhpUnitGen\Console\Contracts\Config\ConsoleConfig;

/**
 * Class JsonConfigResolverAdapter.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class JsonConfigResolverAdapter extends AbstractConfigResolverStrategy
{
    /**
     * {@inheritdoc}
     */
    public function resolve(string $content): ConsoleConfig
    {
        return $this->makeConfig(json_decode($content, true));
    }
}
