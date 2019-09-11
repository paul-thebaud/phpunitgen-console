<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Config\ResolveStrategies;

use PhpUnitGen\Core\Config\Config;

/**
 * Class JsonResolveStrategy.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class JsonResolveStrategy extends AbstractResolveStrategy
{
    /**
     * {@inheritdoc}
     */
    public function resolve(string $content): Config
    {
        return $this->makeConfig(json_decode($content, true));
    }
}
