<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Files;

use League\Flysystem\Adapter\Local;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use PhpUnitGen\Console\Contracts\Files\Filesystem as FilesystemContract;
use PhpUnitGen\Core\Exceptions\InvalidArgumentException;
use PhpUnitGen\Core\Helpers\Str;
use Tightenco\Collect\Support\Collection;

/**
 * Class LeagueFilesystem.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class LeagueFilesystem implements FilesystemContract
{
    use CleansWindowsPaths;

    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * @var string The current path in filesystem.
     */
    protected $currentWorkingDirectory;

    /**
     * LeagueFilesystem constructor.
     *
     * @param FilesystemInterface $filesystem
     * @param string              $currentWorkingDirectory
     */
    public function __construct(FilesystemInterface $filesystem, string $currentWorkingDirectory)
    {
        $this->filesystem = $filesystem;
        $this->currentWorkingDirectory = $this->getCleanedPath($currentWorkingDirectory);
    }

    /**
     * Create a filesystem for local environment.
     *
     * @return LeagueFilesystem
     */
    public static function make(): self
    {
        $localAdapter = new Local('/', LOCK_EX, Local::SKIP_LINKS);

        return new static(new Filesystem($localAdapter), getcwd());
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $path): bool
    {
        return $this->filesystem->has($this->getAbsolutePath($path));
    }

    /**
     * {@inheritdoc}
     */
    public function isFile(string $path): bool
    {
        $type = $this->getPathType($this->getAbsolutePath($path));

        return $type === 'file';
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory(string $path): bool
    {
        $type = $this->getPathType($this->getAbsolutePath($path));

        return $type === 'dir';
    }

    /**
     * {@inheritdoc}
     */
    public function listFiles(string $directory): Collection
    {
        $files = new Collection(
            $this->filesystem->listContents($this->getAbsolutePath($directory), true)
        );

        return $files
            ->reject(function (array $file) {
                return $file['type'] !== 'file';
            })
            ->map(function (array $file) {
                return '/'.$file['path'];
            })
            ->values();
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $file): string
    {
        try {
            return $this->filesystem->read($this->getAbsolutePath($file));
        } catch (FileNotFoundException $exception) {
            throw new InvalidArgumentException('file not found: '.$file);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $file, string $content): void
    {
        $path = $this->getAbsolutePath($file);

        if ($this->isDirectory($path)) {
            throw new InvalidArgumentException(
                'cannot write file because directory with same name exists: '.$file
            );
        }

        if ($this->has($path)) {
            $this->filesystem->update($path, $content);
        } else {
            $this->filesystem->write($path, $content);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rename(string $path, string $newPath): void
    {
        $absolutePath = $this->getAbsolutePath($path);
        $newAbsolutePath = $this->getAbsolutePath($newPath);

        if (! $this->has($absolutePath)) {
            throw new InvalidArgumentException("cannot rename not found {$path}");
        }

        if ($this->has($newAbsolutePath)) {
            throw new InvalidArgumentException("cannot rename to existing {$newPath}");
        }

        $this->filesystem->rename($absolutePath, $newAbsolutePath);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoot(): string
    {
        return $this->currentWorkingDirectory.'/';
    }

    /**
     * Retrieve the base League filesystem instance.
     *
     * @return FilesystemInterface
     */
    public function getFilesystem(): FilesystemInterface
    {
        return $this->filesystem;
    }

    /**
     * Get absolute path from given relative or absolute path.
     *
     * @param string $path
     *
     * @return string
     */
    protected function getAbsolutePath(string $path): string
    {
        $path = $this->getCleanedPath($path);

        if (Str::startsWith('/', $path)) {
            return $path;
        }

        return $this->getRoot().$path;
    }

    /**
     * Cleans a path by replacing "\" with "/" and Windows disks letter.
     *
     * @param string $path
     *
     * @return string
     */
    protected function getCleanedPath(string $path): string
    {
        return $this->convertPotentialWindowsPath($path);
    }

    /**
     * Get the type metadata of the given path if possible. Returns null if
     * not found.
     *
     * @param string $path
     *
     * @return string|null
     */
    protected function getPathType(string $path): ?string
    {
        try {
            $metadata = $this->filesystem->getMetadata($path);
        } catch (FileNotFoundException $exception) {
            return null;
        }

        if (! is_array($metadata) || ! isset($metadata['type'])) {
            return null;
        }

        return $metadata['type'];
    }
}
