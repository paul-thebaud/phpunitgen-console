<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Config\ResolveStrategies;

use PhpUnitGen\Core\Config\Config;
use Symfony\Component\Yaml\Yaml;

/**
 * Class YamlResolveStrategy.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class YamlResolveStrategy extends AbstractResolveStrategy
{
    /**
     * {@inheritdoc}
     */
    public function resolve(string $content): Config
    {
        return $this->makeConfig(Yaml::parse($content));
    }
}
