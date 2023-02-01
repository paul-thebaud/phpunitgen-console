<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Container;

use League\Container\ReflectionContainer;
use PhpUnitGen\Console\Config\ConfigResolver;
use PhpUnitGen\Console\Contracts\Config\ConfigResolver as ConfigResolverContract;
use PhpUnitGen\Console\Contracts\Execution\Runner as RunnerContract;
use PhpUnitGen\Console\Contracts\Files\FileBackup as FileBackupContract;
use PhpUnitGen\Console\Contracts\Files\Filesystem as FilesystemContract;
use PhpUnitGen\Console\Contracts\Files\SourcesResolver as SourcesResolverContract;
use PhpUnitGen\Console\Contracts\Files\TargetResolver as TargetResolverContract;
use PhpUnitGen\Console\Contracts\Reporters\ReporterFactory as ReporterFactoryContract;
use PhpUnitGen\Console\Execution\Runner;
use PhpUnitGen\Console\Files\FileBackup;
use PhpUnitGen\Console\Files\LeagueFilesystem;
use PhpUnitGen\Console\Files\SourcesResolver;
use PhpUnitGen\Console\Files\TargetResolver;
use PhpUnitGen\Console\Reporters\ReporterFactory;
use PhpUnitGen\Core\Container\ReflectionServiceProvider;

/**
 * Class ConsoleServiceProvider.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class ConsoleServiceProvider extends ReflectionServiceProvider
{
    /**
     * The default implementations of contracts.
     */
    protected const DEFAULT_IMPLEMENTATIONS = [
        ConfigResolverContract::class  => ConfigResolver::class,
        SourcesResolverContract::class => SourcesResolver::class,
        TargetResolverContract::class  => TargetResolver::class,
        FileBackupContract::class      => FileBackup::class,
        ReporterFactoryContract::class => ReporterFactory::class,
        RunnerContract::class          => Runner::class,
    ];

    /**
     * @var array The contracts that this service provider provides.
     */
    protected $provides = [
        FilesystemContract::class,
        ConfigResolverContract::class,
        SourcesResolverContract::class,
        TargetResolverContract::class,
        FileBackupContract::class,
        ReporterFactoryContract::class,
        RunnerContract::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->leagueContainer->delegate(new ReflectionContainer());

        $this->leagueContainer->add(FilesystemContract::class, LeagueFilesystem::make());

        foreach (self::DEFAULT_IMPLEMENTATIONS as $contract => $concrete) {
            $this->addDefinition($contract, $concrete);
        }
    }
}
