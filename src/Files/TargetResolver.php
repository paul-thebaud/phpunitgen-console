<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Files;

use PhpUnitGen\Console\Contracts\Files\Filesystem;
use PhpUnitGen\Console\Contracts\Files\TargetResolver as TargetResolverContract;
use PhpUnitGen\Core\Helpers\Str;

/**
 * Class TargetResolver.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class TargetResolver implements TargetResolverContract
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * TargetResolver constructor.
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
    public function resolve(string $sourcePath, string $targetPath): string
    {
        if (strrpos($targetPath, '/') !== strlen($targetPath)) {
            $targetPath .= '/';
        }

        $compiledPath = Str::replaceFirst($this->filesystem->getRoot(), '', $sourcePath);
        $compiledPath = $targetPath.Str::afterFirst('/', $compiledPath);
        $compiledPath = Str::replaceLast('.php', 'Test.php', $compiledPath);

        return $compiledPath;
    }
}
