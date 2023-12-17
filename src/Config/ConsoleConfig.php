<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Config;

use PhpUnitGen\Console\Contracts\Config\ConsoleConfig as ConsoleConfigContract;
use PhpUnitGen\Core\Config\Config;

/**
 * Class ConsoleConfig.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class ConsoleConfig extends Config implements ConsoleConfigContract
{
    /**
     * The properties of the config with there type hint.
     */
    protected const PROPERTIES = [
        'automaticGeneration'      => self::TYPE_BOOL,
        'backupFiles'              => self::TYPE_BOOL,
        'implementations'          => self::TYPE_ARRAY,
        'baseNamespace'            => self::TYPE_STRING,
        'baseTestNamespace'        => self::TYPE_STRING,
        'testCase'                 => self::TYPE_STRING,
        'testClassFinal'           => self::TYPE_BOOL,
        'testClassStrictTypes'     => self::TYPE_BOOL,
        'testClassTypedProperties' => self::TYPE_BOOL,
        'excludedMethods'          => self::TYPE_ARRAY,
        'mergedPhpDoc'             => self::TYPE_ARRAY,
        'phpDoc'                   => self::TYPE_ARRAY,
        'phpHeaderDoc'             => self::TYPE_STRING,
        'options'                  => self::TYPE_ARRAY,
        'overwriteFiles'           => self::TYPE_BOOL,
        'excludedFiles'            => self::TYPE_ARRAY,
        'includedFiles'            => self::TYPE_ARRAY,
        'generateOnMake'           => self::TYPE_BOOL,
    ];

    /**
     * @var string|null The config path in filesystem, null if it is default config.
     */
    protected $path;

    /**
     * {@inheritdoc}
     */
    protected static function getDefaultConfig(): array
    {
        return require __DIR__.'/../../config/phpunitgen.php';
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function setPath(?string $path): ConsoleConfigContract
    {
        $this->path = $path;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function overwriteFiles(): ?bool
    {
        return $this->config['overwriteFiles'];
    }

    /**
     * {@inheritdoc}
     */
    public function backupFiles(): ?bool
    {
        return $this->config['backupFiles'];
    }

    /**
     * {@inheritdoc}
     */
    public function excludedFiles(): array
    {
        return $this->config['excludedFiles'];
    }

    /**
     * {@inheritdoc}
     */
    public function includedFiles(): array
    {
        return $this->config['includedFiles'];
    }

    public function generateOnMake(): bool
    {
        return $this->config['generateOnMake'];
    }
}
