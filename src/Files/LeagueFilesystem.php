<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Files;

use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToReadFile;
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
     * @var FilesystemOperator
     */
    protected $filesystem;

    /**
     * @var string The current path in filesystem.
     */
    protected $currentWorkingDirectory;

    /**
     * LeagueFilesystem constructor.
     *
     * @param FilesystemOperator $filesystem
     * @param string             $currentWorkingDirectory
     */
    public function __construct(FilesystemOperator $filesystem, string $currentWorkingDirectory)
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
        $localAdapter = new LocalFilesystemAdapter(
            '/',
            null,
            LOCK_EX,
            LocalFilesystemAdapter::SKIP_LINKS,
            null,
            true
        );

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
        return $this->filesystem->fileExists($this->getAbsolutePath($path));
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory(string $path): bool
    {
        return $this->filesystem->directoryExists($this->getAbsolutePath($path));
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
            ->filter(fn (StorageAttributes $attrs) => $attrs instanceof FileAttributes)
            ->map(fn (StorageAttributes $attrs) => '/'.$attrs->path())
            ->values();
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $file): string
    {
        try {
            return $this->filesystem->read($this->getAbsolutePath($file));
        } catch (UnableToReadFile $exception) {
            throw new InvalidArgumentException('file not found: '.$file, 0, $exception);
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

        $this->filesystem->write($path, $content);
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

        $this->filesystem->move($absolutePath, $newAbsolutePath);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoot(): string
    {
        return $this->currentWorkingDirectory.'/';
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

        if (Str::startsWith('/', $path) || Str::containsRegex('^[A-Z]:', $path)) {
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
}
