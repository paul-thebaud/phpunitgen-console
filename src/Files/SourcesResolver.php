<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Files;

use PhpUnitGen\Console\Contracts\Config\ConsoleConfig;
use PhpUnitGen\Console\Contracts\Files\Filesystem;
use PhpUnitGen\Console\Contracts\Files\SourcesResolver as SourcesResolverContract;
use PhpUnitGen\Core\Exceptions\InvalidArgumentException;
use PhpUnitGen\Core\Helpers\Str;
use Tightenco\Collect\Support\Collection;

/**
 * Class SourcesResolver.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class SourcesResolver implements SourcesResolverContract
{
    use CleansWindowsPaths;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * SourcesResolver constructor.
     *
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ConsoleConfig $config, string $sourcePath): Collection
    {
        if ($this->filesystem->isFile($sourcePath)) {
            return new Collection([
                $this->convertPotentialWindowsPath($sourcePath),
            ]);
        }

        $sources = $this->filesystem->listFiles($sourcePath)
            ->reject(function (string $path) use ($config) {
                return Str::containsRegex($config->excludedFiles(), $path)
                    || ! Str::containsRegex($config->includedFiles(), $path);
            });

        if ($sources->count() === 0) {
            throw new InvalidArgumentException(
                'no source to generate tests for'
            );
        }

        return $sources->values();
    }
}
