<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Commands;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait HasOutput.
 *
 * This trait can be used to output strings inside an output interface.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
trait HasOutput
{
    /**
     * The foreground used for success output.
     */
    protected static $SUCCESS_FOREGROUND = 'green';

    /**
     * The foreground used for warning output.
     */
    protected static $WARNING_FOREGROUND = 'yellow';

    /**
     * The foreground used for error output.
     */
    protected static $ERROR_FOREGROUND = 'red';

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * Write a success message.
     *
     * @param string $message
     * @param bool   $newLine
     *
     * @return static
     */
    protected function success(string $message, bool $newLine = true): self
    {
        return $this->write($message, self::$SUCCESS_FOREGROUND, $newLine);
    }

    /**
     * Write an error message.
     *
     * @param string $message
     * @param bool   $newLine
     *
     * @return static
     */
    protected function warning(string $message, bool $newLine = true): self
    {
        return $this->write($message, self::$WARNING_FOREGROUND, $newLine);
    }

    /**
     * Write an error message.
     *
     * @param string $message
     * @param bool   $newLine
     *
     * @return static
     */
    protected function error(string $message, bool $newLine = true): self
    {
        return $this->write($message, self::$ERROR_FOREGROUND, $newLine);
    }

    /**
     * Write the given string to output and line jump.
     *
     * @param string      $string
     * @param string|null $foreground
     *
     * @return static
     */
    protected function writeln(string $string = '', ?string $foreground = null): self
    {
        return $this->write($string, $foreground, true);
    }

    /**
     * Write the given string to output.
     *
     * @param string      $string
     * @param string|null $foreground
     * @param bool        $newLine
     *
     * @return static
     */
    protected function write(string $string = '', ?string $foreground = null, bool $newLine = false): self
    {
        if ($this->output->isQuiet()) {
            return $this;
        }

        if ($foreground !== null) {
            $string = "<fg={$foreground}>{$string}</>";
        }

        $this->output->write($string, $newLine);

        return $this;
    }
}
