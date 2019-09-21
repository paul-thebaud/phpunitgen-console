<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Config\ConfigResolverAdapters;

use PhpUnitGen\Console\Contracts\Config\ConfigResolverAdapter;
use Symfony\Component\Yaml\Yaml;

/**
 * Class YamlConfigResolverAdapter.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class YamlConfigResolverAdapter implements ConfigResolverAdapter
{
    /**
     * {@inheritdoc}
     */
    public function resolve(string $content): array
    {
        return Yaml::parse($content);
    }
}
