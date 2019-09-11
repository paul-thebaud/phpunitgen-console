<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Config;

use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use PhpUnitGen\Console\Config\ResolveStrategies\JsonResolveStrategy;
use PhpUnitGen\Console\Config\ResolveStrategies\PhpResolveStrategy;
use PhpUnitGen\Console\Config\ResolveStrategies\YamlResolveStrategy;
use PhpUnitGen\Console\Contracts\Config\ConfigResolver as ConfigResolverContract;
use PhpUnitGen\Console\Contracts\Config\ResolveStrategy;
use PhpUnitGen\Core\Config\Config;
use PhpUnitGen\Core\Contracts\Config\Config as ConfigContract;
use PhpUnitGen\Core\Exceptions\InvalidArgumentException;
use PhpUnitGen\Core\Helpers\Str;

/**
 * Class ConfigResolver.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class ConfigResolver implements ConfigResolverContract
{
    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * @var PhpResolveStrategy
     */
    protected $phpResolveStrategy;

    /**
     * @var YamlResolveStrategy
     */
    protected $yamlResolveStrategy;

    /**
     * @var JsonResolveStrategy
     */
    protected $jsonResolveStrategy;

    /**
     * ConfigResolver constructor.
     *
     * @param FilesystemInterface $filesystem
     * @param PhpResolveStrategy  $phpResolveStrategy
     * @param YamlResolveStrategy $yamlResolveStrategy
     * @param JsonResolveStrategy $jsonResolveStrategy
     */
    public function __construct(
        FilesystemInterface $filesystem,
        PhpResolveStrategy $phpResolveStrategy,
        YamlResolveStrategy $yamlResolveStrategy,
        JsonResolveStrategy $jsonResolveStrategy
    ) {
        $this->filesystem = $filesystem;
        $this->phpResolveStrategy = $phpResolveStrategy;
        $this->yamlResolveStrategy = $yamlResolveStrategy;
        $this->jsonResolveStrategy = $jsonResolveStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(?string $path = null): ConfigContract
    {
        if ($path == null) {
            return $this->resolveDefaultConfig();
        }

        try {
            return $this->resolveConfigFromPath($path);
        } catch (FileNotFoundException $exception) {
            throw new InvalidArgumentException(
                'given config file does not exists or is not readable'
            );
        }
    }

    /**
     * Resolve the default config from current working directory.
     *
     * @return ConfigContract
     */
    protected function resolveDefaultConfig(): ConfigContract
    {
        foreach ($this->getDefaultConfigPaths() as $path) {
            try {
                return $this->resolveConfigFromPath($path);
            } catch (FileNotFoundException $exception) {
                continue;
            }
        }

        return Config::make();
    }

    /**
     * Resolve the given config from file path.
     *
     * @param string $path
     *
     * @return ConfigContract
     *
     * @throws FileNotFoundException
     * @throws InvalidArgumentException
     */
    protected function resolveConfigFromPath(string $path): ConfigContract
    {
        $resolveStrategy = $this->getResolveStrategy($path);

        $content = $this->filesystem->read($path);

        return $resolveStrategy->resolve($content);
    }

    /**
     * Get the correct resolve strategy for the given path.
     *
     * @param string $path
     *
     * @return ResolveStrategy
     *
     * @throws InvalidArgumentException
     */
    protected function getResolveStrategy(string $path): ResolveStrategy
    {
        $extension = Str::replaceLast('.dist', '', $path);
        $extension = Str::afterLast('.', $extension);

        $acceptedExtensions = $this->getAcceptedExtensions();
        if (! in_array($extension, $acceptedExtensions)) {
            throw new InvalidArgumentException(
                'config file must have one of the following extensions: '.implode(', ', $acceptedExtensions)
            );
        }

        return $this->getResolveStrategies()[$extension];
    }

    /**
     * Get the possible default config file paths.
     *
     * @return string[]
     */
    protected function getDefaultConfigPaths(): array
    {
        $paths = [];

        foreach ($this->getAcceptedExtensions() as $extension) {
            $path = 'phpunitgen.'.$extension;
            $paths[] = $path;
            $paths[] = $path.'.dist';
        }

        return $paths;
    }

    /**
     * Get the list of accepted extensions.
     *
     * @return string[]
     */
    protected function getAcceptedExtensions(): array
    {
        return array_keys($this->getResolveStrategies());
    }

    /**
     * Get mapping between file extension and corresponding resolve strategy.
     *
     * @return ResolveStrategy[]
     */
    protected function getResolveStrategies(): array
    {
        return [
            'php'  => $this->phpResolveStrategy,
            'yml'  => $this->yamlResolveStrategy,
            'json' => $this->jsonResolveStrategy,
        ];
    }
}
