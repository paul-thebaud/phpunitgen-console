<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Config\ResolveStrategies;

use PhpUnitGen\Core\Config\Config;

/**
 * Class PhpResolveStrategy.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class PhpResolveStrategy extends AbstractResolveStrategy
{
    /**
     * {@inheritdoc}
     */
    public function resolve(string $content): Config
    {
        $tempFile = tmpfile();
        fwrite($tempFile, $content);
        $config = include stream_get_meta_data($tempFile)['uri'];
        fclose($tempFile);

        return $this->makeConfig($config);
    }
}
