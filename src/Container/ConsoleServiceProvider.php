<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Container;

use League\Container\ReflectionContainer;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use PhpUnitGen\Console\Config\ConfigResolver;
use PhpUnitGen\Console\Contracts\Config\ConfigResolver as ConfigResolverContract;

/**
 * Class ConsoleServiceProvider.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class ConsoleServiceProvider extends AbstractServiceProvider
{
    /**
     * @var array The contracts that this service provider provides.
     */
    protected $provides = [
        ConfigResolverContract::class,
        FilesystemInterface::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->leagueContainer->delegate(new ReflectionContainer());

        $this->leagueContainer->add(FilesystemInterface::class, Filesystem::class)
            ->addArgument(new Local(getcwd()));
        $this->leagueContainer->add(ConfigResolverContract::class, ConfigResolver::class)
            ->addArgument(FilesystemInterface::class);
    }
}
