<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Contracts\Config;

use PhpUnitGen\Core\Contracts\Config\Config;

/**
 * Interface ConsoleConfig.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
interface ConsoleConfig extends Config
{
    /**
     * Retrieve the config path or null if it is the default config.
     *
     * @return string|null
     */
    public function getPath(): ?string;

    /**
     * Define the path from which the config was retrieved. Null means it is a default config.
     *
     * @param string|null $path
     *
     * @return static
     */
    public function setPath(?string $path): self;

    /**
     * Tells if files should be overwritten by PhpUnitGen when already existing. "null" tells
     * that user should be asked for action to do on each file.
     *
     * @return bool
     */
    public function overwriteFiles(): ?bool;

    /**
     * Tells if the generator should backup the existing files which will be
     * overwritten (useful when "overwriteFile" is enabled).
     *
     * @return bool
     */
    public function backupFiles(): ?bool;

    /**
     * Get the case insensitive RegExp (without opening and closing "/") that files' path shouldn't match.
     *
     * @return array
     */
    public function excludedFiles(): array;

    /**
     * Get the case insensitive RegExp (without opening and closing "/") that files' path should match.
     *
     * @return array
     */
    public function includedFiles(): array;

    /**
     * Tells if PhpUnitGen should be triggered on file making. Only works with Laravel for the moment.
     *
     * @return bool
     */
    public function generateOnMake(): bool;
}
