<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Config;

use PhpUnitGen\Console\Config\ConfigResolverAdapters\JsonConfigResolverAdapter;
use PhpUnitGen\Console\Config\ConfigResolverAdapters\PhpConfigResolverAdapter;
use PhpUnitGen\Console\Config\ConfigResolverAdapters\YamlConfigResolverAdapter;
use PhpUnitGen\Console\Contracts\Config\ConfigResolver as ConfigResolverContract;
use PhpUnitGen\Console\Contracts\Config\ConfigResolverAdapter;
use PhpUnitGen\Console\Contracts\Config\ConsoleConfig as ConsoleConfigContract;
use PhpUnitGen\Console\Contracts\Files\Filesystem;
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
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var ConfigResolverAdapter[]
     */
    protected $configResolverAdapters;

    /**
     * ConfigResolver constructor.
     *
     * @param Filesystem                $filesystem
     * @param PhpConfigResolverAdapter  $phpConfigResolverAdapter
     * @param YamlConfigResolverAdapter $yamlConfigResolverAdapter
     * @param JsonConfigResolverAdapter $jsonConfigResolverAdapter
     */
    public function __construct(
        Filesystem $filesystem,
        PhpConfigResolverAdapter $phpConfigResolverAdapter,
        YamlConfigResolverAdapter $yamlConfigResolverAdapter,
        JsonConfigResolverAdapter $jsonConfigResolverAdapter
    ) {
        $this->filesystem = $filesystem;

        $this->setConfigResolverAdapters([
            'php'  => $phpConfigResolverAdapter,
            'yml'  => $yamlConfigResolverAdapter,
            'json' => $jsonConfigResolverAdapter,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(?string $path): ConsoleConfigContract
    {
        if ($path === null) {
            return $this->resolveDefaultConfig();
        }

        return $this->resolveConfigFromPath($path);
    }

    /**
     * Resolve the default config from current working directory.
     *
     * @return ConsoleConfig
     *
     * @throws InvalidArgumentException
     */
    protected function resolveDefaultConfig(): ConsoleConfig
    {
        foreach ($this->getDefaultConfigPaths() as $path) {
            if (! $this->filesystem->has($path)) {
                continue;
            }

            return $this->resolveConfigFromPath($path);
        }

        return ConsoleConfig::make();
    }

    /**
     * Resolve the given config from file path.
     *
     * @param string $path
     *
     * @return ConsoleConfig
     *
     * @throws InvalidArgumentException
     */
    protected function resolveConfigFromPath(string $path): ConsoleConfigContract
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
     * @return ConfigResolverAdapter
     *
     * @throws InvalidArgumentException
     */
    protected function getResolveStrategy(string $path): ConfigResolverAdapter
    {
        $pathLength = mb_strlen($path);
        if (mb_strrpos($path, '.dist') === ($pathLength - 5)) {
            $path = mb_substr($path, -5);
        }

        $extension = Str::afterLast('.', $path);
        $acceptedExtensions = $this->getAcceptedExtensions();

        if (! in_array($extension, $acceptedExtensions)) {
            throw new InvalidArgumentException(
                'config file must have one of the following extensions: '.implode(', ', $acceptedExtensions)
            );
        }

        return $this->getConfigResolverAdapters()[$extension];
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
        return array_keys($this->getConfigResolverAdapters());
    }

    /**
     * @return ConfigResolverAdapter[]
     */
    public function getConfigResolverAdapters(): array
    {
        return $this->configResolverAdapters;
    }

    /**
     * @param ConfigResolverAdapter[] $configResolverAdapters
     */
    public function setConfigResolverAdapters(array $configResolverAdapters): void
    {
        $this->configResolverAdapters = $configResolverAdapters;
    }
}
