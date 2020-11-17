<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Contracts\Files;

use PhpUnitGen\Core\Exceptions\InvalidArgumentException;
use Tightenco\Collect\Support\Collection;

/**
 * Interface Filesystem.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
interface Filesystem
{
    /**
     * Check if a path corresponds to a file or a directory.
     *
     * @param string $path
     *
     * @return bool
     */
    public function has(string $path): bool;

    /**
     * Check if a path corresponds to a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function isFile(string $path): bool;

    /**
     * Check if a path corresponds to a directory.
     *
     * @param string $path
     *
     * @return bool
     */
    public function isDirectory(string $path): bool;

    /**
     * List all files' absolute path from a directory.
     *
     * Returned paths must be using the "/" char as dir separator.
     * For absolute paths, each must start with "/" too (no "C:\" like on Windows).
     *
     * @param string $directory
     *
     * @return Collection|string[]
     */
    public function listFiles(string $directory): Collection;

    /**
     * Read a file content. Throws an exception if the path
     * does not exists or is not a file.
     *
     * @param string $file
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function read(string $file): string;

    /**
     * Write a file content.
     *
     * @param string $file
     * @param string $content
     */
    public function write(string $file, string $content): void;

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newPath
     *
     * @throws InvalidArgumentException
     */
    public function rename(string $path, string $newPath): void;

    /**
     * Get the root path of filesystem.
     *
     * Returned paths must be using the "/" char as dir separator.
     * For absolute paths, each must start with "/" too (no "C:\" like on Windows).
     *
     * @return string
     */
    public function getRoot(): string;
}
