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
     * Tells if files should be overwritten by PhpUnitGen when already existing. "null" tells
     * that user should be asked for action to do on each file.
     *
     * @return bool
     */
    public function overwriteFiles(): ?bool;

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
}
