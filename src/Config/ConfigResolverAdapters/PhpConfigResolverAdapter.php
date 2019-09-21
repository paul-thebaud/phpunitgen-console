<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Config\ConfigResolverAdapters;

use PhpUnitGen\Console\Contracts\Config\ConfigResolverAdapter;

/**
 * Class PhpConfigResolverAdapter.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class PhpConfigResolverAdapter implements ConfigResolverAdapter
{
    /**
     * {@inheritdoc}
     */
    public function resolve(string $content): array
    {
        $tempFile = tmpfile();
        fwrite($tempFile, $content);
        $config = include stream_get_meta_data($tempFile)['uri'];
        fclose($tempFile);

        return $config;
    }
}
